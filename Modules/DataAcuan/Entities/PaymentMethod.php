<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;

class PaymentMethod extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PaymentMethodFactory::new ();
    }

    /**
     * scope payment method to display only cash method
     * if dealer has order that has not been
     * settle before
     *
     * @return void
     */
    public function scopeDealerPaymentCheck($QQQ, $dealer_id)
    {
        $order = SalesOrder::query()
            ->where("store_id", $dealer_id)
            ->whereHas("invoice", function ($QQQ) {
                return $QQQ->where("payment_status", "!=", "settle");
            })
            ->orderBy("created_at", "desc")
            ->first();

        if ($order) {
            return $QQQ->where("name", "Cash");
        } else {
            return $QQQ;
        }
    }

    public function scopePaymentAccordingGradeAndDealer($query, $dealer_id, $sales_order_id)
    {
        /**
         * unsettle proforma count dealer
         */
        $proforma = DB::table('invoices as i')
            ->join("sales_orders as s", "s.id", "i.sales_order_id")
            ->where("s.store_id", $dealer_id)
            ->whereIn("s.status", considered_orders())
            ->where("i.payment_status", "!=", "settle")
            ->orderBy("i.created_at", "desc");

        $dealer = DealerV2::query()
            ->with([
                "dealerWithPayment",
                "grading",
            ])
            ->withTrashed()
            ->findOrFail($dealer_id);

        $dealer_payment = $dealer->dealerWithPayment->pluck("payment_method_id")->toArray();
        $maximum_payment_days = $dealer?->grading?->maximum_payment_days;
        $maximum_unsettle_proforma = $dealer?->grading?->max_unsettle_proformas;
        $remaining_credit_limit = ($dealer->custom_credit_limit > 0 ? $dealer->custom_credit_limit : $dealer->grading->credit_limit) - $proforma->sum("s.total");

        return $query

        /* depend on proforma count */
            ->when(gettype($maximum_unsettle_proforma) == "integer", function ($QQQ) use ($maximum_unsettle_proforma, $proforma) {
                return $QQQ->when($proforma->count() >= $maximum_unsettle_proforma, function ($QQQ) {
                    return $QQQ->cashOnly();
                });
            })

            /* depend on credit limit */
            ->when($remaining_credit_limit >= 0, function ($QQQ) use ($sales_order_id, $remaining_credit_limit) {
                $total_amount = DB::table('sales_order_details')->whereNull("deleted_at")->where("sales_order_id", $sales_order_id)->sum("total");
                return $QQQ->when($total_amount > $remaining_credit_limit, function ($QQQ) {
                    return $QQQ->cashOnly();
                });
            })

            ->when($remaining_credit_limit < 0, function ($QQQ) {
                return $QQQ->cashOnly();
            })

            /* depend on max payment days */
            ->when(gettype($maximum_payment_days) == "integer", function ($QQQ) use ($maximum_payment_days) {
                return $QQQ->when($maximum_payment_days >= 0, function ($QQQ) use ($maximum_payment_days) {
                    return $QQQ
                        ->whereNotNull("days")
                        ->where("days", "<=", $maximum_payment_days);
                });
            })

            /* depend on dealer payment */
            ->when(count($dealer_payment) > 0, function ($QQQ) use ($dealer_payment) {
                return $QQQ->whereIn("id", $dealer_payment);
            })
            ->when(count($dealer_payment) == 0, function ($QQQ) {
                return $QQQ;
            });
    }

    public function scopeCashOnly($query)
    {
        return $query->whereIn("name", ["cash"]);
    }

    public function scopePaymentMethodMarketing($query)
    {
        return $query->where("is_for_marketing", true);
    }
}

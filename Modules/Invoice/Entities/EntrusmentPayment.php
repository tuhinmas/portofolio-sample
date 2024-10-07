<?php

namespace Modules\Invoice\Entities;

use App\Traits\MarketingArea;
use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\Invoice\Entities\Invoice;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Entities\User;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\SalesOrder\Entities\SalesOrder;

class EntrusmentPayment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use LogsActivity;
    use SuperVisorCheckV2;
    use MarketingArea;

    protected $guarded = [];
    protected $casts = [
        "id" => "string",
    ];
    public $incrementing = false;

    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\EntrusmentPaymentFactory::new ();
    }

    /**
     * activity logs set causer
     *
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    /**
     * activity logs
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }
    

    public function invoice()
    {
        return $this->hasOne(Invoice::class, "id", "invoice_id")->with("payment");
    }

    public function personel()
    {
        return $this->hasOne(User::class, "id", "user_id")->with("personel", "personel.position")->withTrashed();
    }

    public function paymentMethod()
    {
        return $this->hasOne(PaymentMethod::class, "id", "payment_method_id");
    }

    public function scopeByDealer($QQQ, $dealer_id)
    {
        $entrusment_payment_id = self::query()
            ->whereHas("invoice", function ($QQQ) use ($dealer_id) {
                return $QQQ->whereHas("salesOrder", function ($QQQ) use ($dealer_id) {
                    return $QQQ->where("store_id", $dealer_id);
                });
            })
            ->get()
            ->pluck("id");

        return $QQQ->whereIn("id", $entrusment_payment_id);
    }

    public function scopeByProformaNumber($QQQ, $proforma_number){
        $proforma_id = DB::table('invoices')->whereNull("deleted_at")->where("invoice", "like", "%".$proforma_number."%")->get()->pluck("id");
        return $QQQ->whereIn("invoice_id", $proforma_id);
    }

    /**
     * except settle invoice
     */
    public function scopeUnsettlePayment($QQQ){
        return $QQQ->whereHas("invoice", function($QQQ){
            return $QQQ->where("payment_status", "!=", "settle");
        });
    }

    public function scopeSupervisor($query)
    {
        $personels_id = $this->getPersonel();
        $users = DB::table('users')->whereNull('deleted_at')->whereIn('personel_id', $personels_id)->get()->pluck('id');
        return $query->whereIn("entrusment_payments.user_id", $users);
    }

    public function scopeRegion($query, $region_id)
    {
        $marketing_list = $this->marketingListByAreaId($region_id);
        $users = DB::table('users')->whereNull('deleted_at')->whereIn('personel_id', $marketing_list)->get()->pluck('id');
        return $query->whereIn("user_id", $users);
    }

    public function scopeWhereMarketing($query, $marketing)
    {
        return $query->whereHas('personel.personel', function($QQQ) use ($marketing) {
            return $QQQ->where('id', $marketing) ;
        });
    }

    /**
     * scope for entrusment payment, specialy on dc or support with spesific area
     *
     * @param [type] $query
     * @return void
     */
    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        $user_list = DB::table('users')->whereNull("deleted_at")->whereIn("personel_id", $marketing_on_branch)->pluck("id");
        return $query->whereIn("user_id", $user_list);
    }

    public function salesOrder()
    {
        return $this->hasOneThrough(
            SalesOrder::class,
            Invoice::class,
            "id",
            "id",
            "invoice_id",
            "sales_order_id"
        );
    }
}

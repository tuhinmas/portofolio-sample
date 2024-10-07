<?php

namespace Modules\Invoice\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Payment;
use Modules\Invoice\Events\CreditMemoCanceledEvent;
use Modules\SalesOrder\Actions\Order\Origin\GenerateSalesOrderOriginFromMemoAction;

class CreditMemoCanceledJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $credit_memo;
    protected $user;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 700;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 25;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;
    public $uniqueFor = 1800;

    public function uniqueId()
    {
        return $this->credit_memo->id;
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($credit_memo, $user)
    {
        $this->credit_memo = $credit_memo;
        $this->user = $user;
        $this->onQueue('order');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->credit_memo->load([
            "destination.salesOrderOnly",
            "creditMemoDetail",
            "origin.salesOrderOnly.salesOrderDetail",
        ]);

        /**
         * --------------------------------------------------------------------
         * order detail and order total update
         * ------------------------------------------------------------
         *
         */
        self::orderTotalUpdateAfterCancel($this->credit_memo, $this->user);

        /**
         * --------------------------------------------------------------------
         * payment update
         * ------------------------------------------------------------
         */
        self::paymentAfterCancel($this->credit_memo);

        /**
         * --------------------------------------------------------------------
         * Order as destination rollback if meet the requirement
         * ------------------------------------------------------------
         */
        self::orderRollbackAfterCancel($this->credit_memo, $this->user);

        /**
         * --------------------------------------------------------------------
         * fee / point marketing recalculation if meet requirement
         * ------------------------------------------------------------
         */
        self::feePointMarketingAfterCancel($this->credit_memo, $this->user);

        /**
         * --------------------------------------------------------------------
         * generate origin as new stock, value of stock is according
         * quantity return, in memo store, it's value has reduced
         * if canceled than it's stock need to returned
         * ------------------------------------------------------------
         */
        app(GenerateSalesOrderOriginFromMemoAction::class)($this->credit_memo, true);
    }

    public function middleware()
    {
        return [new WithoutOverlapping($this->credit_memo->id)];
    }

    /**
     * order total update after cancel
     *
     * @param [type] $credit_memo
     * @return void
     */
    public static function orderTotalUpdateAfterCancel($credit_memo)
    {

        $credit_memo_detail = DB::table('credit_memo_details as cmd')
            ->join("credit_memos as cm", "cm.id", "cmd.credit_memo_id")
            ->where("cm.origin_id", $credit_memo->origin_id)
            ->whereNull("cmd.deleted_at")
            ->whereNull("cm.deleted_at")
            ->where("cm.status", "accepted")
            ->select("cmd.*")
            ->get();

        /* origin product return update */
        $nominal_return_accumulation = 0;
        $sales_order_detail = $credit_memo->origin->salesOrderOnly->salesOrderDetail;
        $sales_order_detail
            ->each(function ($order_detail) use ($credit_memo_detail, &$nominal_return_accumulation) {
                $new_return_quantity = $credit_memo_detail->filter(fn($memo_detail) => $memo_detail->product_id == $order_detail->product_id)->sum("quantity_return");
                $order_detail->returned_quantity = $new_return_quantity;
                $order_detail->save();

                $nominal_return = $order_detail->updateTotal();
                $nominal_return_accumulation += $nominal_return;
            });

        $sales_order = $credit_memo->origin->salesOrderOnly;
        $sales_order->total = $nominal_return_accumulation;
        $sales_order->save();
    }

    /**
     * payment of memo destination will updated with sone rules
     * 1. memo_status set to canceled
     * 2. payment_remaining will update according total memo has cancel
     *
     * @param [type] $credit_memo
     * @return void
     */
    public static function paymentAfterCancel($credit_memo)
    {
        /**
         * if there origin payment, delete it on cancel
         */
        $payment_origin = Payment::query()
            ->where("invoice_id", $credit_memo->origin_id)
            ->where("credit_memo_id", $credit_memo->id)
            ->where("is_credit_memo", false)
            ->whereNull("deleted_at")
            ->first();

        if ($payment_origin) {
            $payment_origin->delete();
        }

        /* payment as destination and original payment */
        $payments = Payment::query()
            ->where("invoice_id", $credit_memo->destination_id)
            ->where(function ($QQQ) {
                return $QQQ

                /* payment memo */
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->whereNotNull("credit_memo_id")
                            ->where("is_credit_memo", true);
                    })

                    /* payment as origin */
                    ->orWhere(function ($QQQ) {
                        return $QQQ
                            ->whereNotNull("credit_memo_id")
                            ->where("is_credit_memo", false);
                    })

                    /* original payment */
                    ->orWhereNull("credit_memo_id");
            })
            ->whereNull("deleted_at")
            ->get();

        $payment_memo = $payments
            ->filter(function ($payment) use ($credit_memo) {
                return $payment->credit_memo_id == $credit_memo->id && $payment->is_credit_memo;
            })
            ->first();

        $payment_before_memo = $payments
            ->sortByDesc("created_at")
            ->filter(fn($payment) => $payment->remaining_payment > 0)
            ->filter(function ($payment) use ($credit_memo) {
                return $payment->credit_memo_id != $credit_memo->id;
            })
            ->filter(function ($payment) use ($credit_memo) {
                return $payment->created_at < $credit_memo->created_at;
            })
            ->first();

        $proforma_destination_total = $credit_memo->destination->total + $credit_memo->destination->ppn;
        $payment_memo->nominal = 0;
        $payment_memo->memo_status = "canceled";
        $payment_memo->remaining_payment = ($payment_before_memo ? $payment_before_memo->remaining_payment : $proforma_destination_total);
        $payment_memo->save();

        /**
         * UPDATE REMAINING PAYMENT
         */
        $payments
            ->sortBy("created_at")
            ->filter(function ($payment) use ($credit_memo) {
                return $payment->created_at > $credit_memo->created_at && $payment->credit_memo_id != $credit_memo->id;
            })
            ->each(function ($payment) use ($credit_memo) {
                $payment->remaining_payment = $payment->remaining_payment + $credit_memo->total;
                $payment->save();
            });

        switch (true) {
            case $payments->sum("nominal") > 0 && $payments->sum("nominal") < $proforma_destination_total:
                $credit_memo->destination->payment_status = "paid";
                $credit_memo->destination->save();
                break;

            case $payments->sum("nominal") <= 0:
                $credit_memo->destination->payment_status = "unpaid";
                $credit_memo->destination->save();
                break;

            case ($credit_memo->destination->total + $credit_memo->destination->ppn) <= $payments->sum("nominal"):
                $credit_memo->destination->payment_status = "settle";
                $credit_memo->destination->save();
                break;

            default:
                break;
        }
    }

    /**
     * credit memo destination status which mean it is sales order
     * will rollback to "confirmed", if all credit memo
     * associated with it has canceled. for your
     * purpose knowledge, that one order
     * could be in several credit
     * memo as destination
     *
     * @param [type] $credit_memo
     * @return void
     */
    public static function orderRollbackAfterCancel($credit_memo, $user)
    {
        $is_rollback = DB::table('credit_memos')
            ->whereNull("deleted_at")
            ->where("destination_id", $credit_memo->destination_id)
            ->where("status", "accepted")
            ->first() ? false : true;

        if ($is_rollback) {
            $sales_order = $credit_memo->destination->salesOrder;
            $sales_order->status = "confirmed";
            $sales_order->return = null;
            $sales_order->returned_by = null;
            $sales_order->save();
            $sales_order->salesOrderHistoryChangeStatus()->create([
                "sales_order_id" => $sales_order->id,
                "type" => $sales_order->type,
                "status" => "confirmed",
                "personel_id" => $user->personel_id,
                "note" => " return dari kredit memo dibatalkan",
            ]);
        }
    }

    /**
     * -------------------------------------------------------
     * need to know that all order (direct or indirect) with same dealer in one quarter will not
     * consider to get fee / point because of credit memo a.k.a return. this credit memo
     * origin and destination will consider to get fee / point again if all credit
     * memo from this dealer was canceled (quarter of destination cause return
     * consider to it). it is mean fee sharing, order origin are
     * rollback to consider get fee
     * ---------------------------------------------
     */
    public static function feePointMarketingAfterCancel($credit_memo, $user)
    {
        $is_recalculated = DB::table('credit_memos as cm')
            ->join("invoices as i", "i.id", "cm.destination_id")
            ->join("sales_orders as s", "s.id", "i.sales_order_id")
            ->whereNull("cm.deleted_at")
            ->whereNull("i.deleted_at")
            ->whereNull("s.deleted_at")
            ->where("s.store_id", $credit_memo->destination->salesOrderOnly->store_id)
            ->where("cm.status", "accepted")
            ->first() ? false : true;

        if ($is_recalculated) {
            CreditMemoCanceledEvent::dispatch($credit_memo->destination->salesOrderOnly, $user);
        }
    }
}

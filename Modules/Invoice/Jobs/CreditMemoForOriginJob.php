<?php

namespace Modules\Invoice\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Payment;
use Modules\SalesOrder\Actions\Order\Origin\GenerateSalesOrderOriginFromMemoAction;

class CreditMemoForOriginJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deleteWhenMissingModels = true;
    protected $sales_order, $credit_memo;

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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($credit_memo, $sales_order, $user = null)
    {
        $this->credit_memo = $credit_memo;
        $this->sales_order = $sales_order;
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
        /* total order update ater memo */
        self::orderTotalUpdate();

        /* make a minus payment for origin */
        self::makePaymentAsMinus();

        /* update sales order origin stock */
        app(GenerateSalesOrderOriginFromMemoAction::class)($this->credit_memo);
    }

    public function orderTotalUpdate(): void
    {
        $credit_memo_detail = DB::table('credit_memo_details as cmd')
            ->join("credit_memos as cm", "cm.id", "cmd.credit_memo_id")
            ->where("cm.origin_id", $this->credit_memo->origin_id)
            ->where("cm.status", "accepted")
            ->whereNull("cmd.deleted_at")
            ->whereNull("cm.deleted_at")
            ->select("cmd.*")
            ->get();

        /* origin product return update */
        $nominal_return_accumulation = 0;
        $this->sales_order->load("salesOrderDetail");
        $this->sales_order
            ->salesOrderDetail
            ->each(function ($order_detail) use ($credit_memo_detail, &$nominal_return_accumulation) {
                $new_return_quantity = $credit_memo_detail->filter(fn($memo_detail) => $memo_detail->product_id == $order_detail->product_id)->sum("quantity_return");
                $order_detail->returned_quantity = $new_return_quantity;
                $order_detail->save();

                $nominal_return = $order_detail->updateTotal();
                $nominal_return_accumulation += $nominal_return;
            });

        $this->sales_order->total = $nominal_return_accumulation;
        $this->sales_order->save();
    }

    public function makePaymentAsMinus(): void
    {
        /**
         * payment for origin if destination is not it self,
         * it need financial rebalancing
         */
        if ($this->credit_memo->origin_id != $this->credit_memo->destination_id) {
            Payment::create([
                "invoice_id" => $this->credit_memo->origin->id,
                "nominal" => -($this->credit_memo->total),
                "reference_number" => $this->credit_memo->number,
                "remaining_payment" => 0,
                "user_id" => $this->user?->id,
                "payment_date" => $this->credit_memo->date,
                "is_credit_memo" => false,
                "credit_memo_id" => $this->credit_memo->id,
                "memo_status" => "accepted",
            ]);
        }
    }

    public function middleware()
    {
        return [new WithoutOverlapping($this->credit_memo->id)];
    }
}

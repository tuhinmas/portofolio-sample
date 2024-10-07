<?php

namespace Modules\SalesOrder\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\SalesOrder\Actions\Order\GetReturnOrderByStoreAction;
use Modules\SalesOrder\Entities\SalesOrder;

class ReturnedOrderInQuarterCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sales_order;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 7;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 5;

    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sales_order)
    {
        $this->sales_order = $sales_order;
        $this->onQueue('order');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GetReturnOrderByStoreAction $get_return_history_action)
    {
        $return_history = $get_return_history_action($this->sales_order->store_id, confirmation_time($this->sales_order));

        if ($return_history) {

            /**
             * mark all store order in quarter as affected from retrun
             */
            $sales_order = SalesOrder::query()
                ->where("store_id", $this->sales_order->store_id)
                ->whereNull("afftected_by_return")
                ->consideredOrderForReturn()
                ->quarterOrderByDate(confirmation_time($this->sales_order))
                ->get()
                ->each(function ($order) use ($return_history) {
                    $order->afftected_by_return = $return_history->id;
                    $order->save();
                });
        }
    }
}

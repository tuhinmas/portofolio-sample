<?php

namespace Modules\Invoice\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\SalesOrderV2\Actions\CalculateMarketingFeeByOrderAction;

class CalculateMarketingFeeOnPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sales_order;
    protected $save_log = false;

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
    public function __construct($sales_order, $save_log = false)
    {
        $this->sales_order = $sales_order;
        $this->save_log = $save_log;
        $this->onQueue('order');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CalculateMarketingFeeByOrderAction $calculate_marketing_fee_by_order_action)
    {
        return $calculate_marketing_fee_by_order_action($this->sales_order, $this->save_log);
    }
}

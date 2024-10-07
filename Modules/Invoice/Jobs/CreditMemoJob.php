<?php

namespace Modules\Invoice\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\SalesOrderV2\Events\SalesOrderv2InReturnEvent;

class CreditMemoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $deleteWhenMissingModels = true;
    protected $sales_order;

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
    public function handle()
    {
        SalesOrderv2InReturnEvent::dispatch($this->sales_order);
    }
}

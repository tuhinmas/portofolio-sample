<?php

namespace Modules\SalesOrder\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\SalesOrder\Entities\SalesOrder;

class CreateProformaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sales_order_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sales_order_id)
    {
        $this->sales_order_id = $sales_order_id;
        $this->onQueue('test');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        echo "order confirmed";
    }
}

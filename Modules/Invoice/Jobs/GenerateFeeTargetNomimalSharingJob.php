<?php

namespace Modules\Invoice\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\SalesOrderV2\Actions\GenerateFeeTargetNominalSharingAction;

class GenerateFeeTargetNomimalSharingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sales_order;

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
    public function handle(
        GenerateFeeTargetNominalSharingAction $generate_fee_target_nominal_sharing_action
    ) {
        $payload = [
            "personel_id" => $this->sales_order->personel_id,
            "year" => confirmation_time($this->sales_order)->format("Y"),
            "quarter" => confirmation_time($this->sales_order)->quarter,
        ];
        return $generate_fee_target_nominal_sharing_action($payload);
    }
}

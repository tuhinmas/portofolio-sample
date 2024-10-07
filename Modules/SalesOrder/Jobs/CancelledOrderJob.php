<?php

namespace Modules\SalesOrder\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Distributor\Actions\GetDistributorActiveContractAction;
use Modules\Invoice\Entities\Invoice;
use Modules\Personel\Actions\Fee\RecalculteAllFeeMarketingAction;
use Modules\SalesOrder\Actions\CanceledDirectSaleAction;
use Modules\SalesOrder\Actions\DeleteSalesOrderOriginByStoreAndDateAction;
use Modules\SalesOrder\Actions\FeeSharingOrigin\DeleteFeeSharingByOrderAction;
use Modules\SalesOrder\Actions\FeeTargetSharingOrigin\DeleteFeeTargetSharingByOrderAction;
use Modules\SalesOrder\Actions\FeeTargetSharingOrigin\DeleteFeeTargetSharingOriginByOrderAction;
use Modules\SalesOrder\Actions\GenerateSalesOrderOriginAction;

class CancelledOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sales_order, $invoice, $user, $canceled_at;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 900;

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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sales_order, $user, $canceled_at = null)
    {
        $this->sales_order = $sales_order;
        $this->user = $user;
        $this->canceled_at = $canceled_at;
        $this->onQueue('order');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        DeleteFeeTargetSharingOriginByOrderAction $delete_fee_target_sharing_origin_action,
        DeleteSalesOrderOriginByStoreAndDateAction $delete_sales_ordre_origin_action,
        DeleteFeeTargetSharingByOrderAction $delete_fee_target_sharing_action,
        GenerateSalesOrderOriginAction $generate_sales_order_origin_action,
        RecalculteAllFeeMarketingAction $recalculate_all_fee_action,
        DeleteFeeSharingByOrderAction $delete_fee_sharing_action,
        GetDistributorActiveContractAction $get_active_contract,
        CanceledDirectSaleAction $cancellation_action,
    ) {
        
        $invoice = Invoice::query()
            ->with([
                "salesOrder",
            ])
            ->where("sales_order_id", $this->sales_order->id)
            ->first();

        if ($invoice) {
            $active_contract = $get_active_contract($this->sales_order->store_id, confirmation_time($invoice));

            /* delete proforma */
            $cancellation_action($this->sales_order, $invoice, $this->user, $this->canceled_at, $create_log = true);

            /* recalculate fee marketing */
            $test = $recalculate_all_fee_action($this->sales_order, $active_contract);

            /* canceled order was from distributor */
            if ($active_contract) {

                /* delete sales order origin first */
                $delete_sales_ordre_origin_action($active_contract, $invoice->created_at);

                /* generate sales order origin */
                $generate_sales_order_origin_action($active_contract, $invoice->created_at);
            }

            /* delete fee sharing */
            $delete_fee_sharing_action($this->sales_order);
            $delete_fee_target_sharing_origin_action($this->sales_order);
            $delete_fee_target_sharing_action($this->sales_order);
        }

    }
}

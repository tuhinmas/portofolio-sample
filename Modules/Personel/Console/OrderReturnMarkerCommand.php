<?php

namespace Modules\Personel\Console;

use App\Traits\ChildrenList;
use App\Traits\MarketingFeeTrait;
use Illuminate\Console\Command;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\Personel\Traits\OrderReturnTrait;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

class OrderReturnMarkerCommand extends Command
{
    use FeeMarketingTrait;
    use MarketingFeeTrait;
    use ChildrenList;
    use OrderReturnTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'order:marking_returned_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cek ordre was returned (status returned) and mark affected order';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        LogWorkerPointMarketingActive $log_worker_point_marketing_active,
        LogWorkerPointMarketing $log_worker_point_marketing,
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderOrigin $sales_order_origin,
        MarketingFee $marketing_fee,
        SalesOrder $sales_order,

    ) {
        $this->log_worker_point_marketing_active = $log_worker_point_marketing_active;
        $this->log_worker_point_marketing = $log_worker_point_marketing;
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->sales_order_origin = $sales_order_origin;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->sales_order = $sales_order;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $nomor = 1;
        $sales_orders = $this->sales_order->query()
            ->with([
                "invoice",
                "salesOrderDetail",
            ])
            ->where("status", "returned")
            ->orderBy("return")
            ->get()
            ->each(function ($order) use (&$nomor) {

                /* marking order affected from retrun */
                $this->orderReturnMarker($order);
                dump([
                    "nomor" => $nomor,
                    "sales_order_id" => $order->id,
                    "confirmed_at" => confirmation_time($order)->format("Y-m-d"),
                    "return" => $sales_order->return
                ]);
                $nomor++;
            });
    }
}

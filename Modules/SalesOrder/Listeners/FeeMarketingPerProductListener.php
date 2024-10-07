<?php

namespace Modules\SalesOrder\Listeners;

use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use Modules\Contest\Traits\ContestPointTrait;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\DataAcuan\Entities\PointProduct;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogFeeTargetSharing;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Events\FeeMarketingPerProductEvent;

class FeeMarketingPerProductListener
{
    use ChildrenList;
    use DistributorStock;
    use FeeMarketingTrait;
    use ContestPointTrait;
    use PointMarketingTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogWorkerSalesPoint $log_worker_sales_point,
        LogFeeTargetSharing $log_fee_target_sharing,
        LogWorkerSalesFee $log_worker_sales_fee,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        MarketingFee $marketing_fee,
        PointProduct $point_product,
        FeePosition $fee_position,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_fee_target_sharing = $log_fee_target_sharing;
        $this->log_worker_sales_point = $log_worker_sales_point;
        $this->log_worker_sales_fee = $log_worker_sales_fee;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->point_product = $point_product;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeMarketingPerProductEvent $event)
    {

        /**
         * update total product
         */
        collect($event->sales_order->sales_order_detail)->each(function ($order_detail) {
            $order_detail->total = ($order_detail->quantity - $order_detail->returned_quantity) * $order_detail->unit_price;
            $order_detail->save();
        });

        /**
         * update fee reguler sharing  if there any update
         * quantity product
         */
        $this->feeMarketingPerProductCalculator($event->sales_order);

        /**
         * update fee target sharing origin if there any update
         * quantity product
         */
        $this->feeTargetSharingOriginGenerator($event->sales_order);

        /**
         * update fee reguler sharing  if there any update
         * quantity product
         */
        $this->marketingPointPerProductCalculator($event->sales_order);

        /**
         * contest point origin recalculate if there any update
         * quantity product, check active contest contract
         * first to validate is product get point
         * in contest
         */
        if (confirmation_time($event->sales_order)) {
            $active_contract_contest = $this->activeContractStoreByDate($event->sales_order->store_id, confirmation_time($event->sales_order)->format("Y-m-d"));
            return $this->contestPointOriginGenerator($active_contract_contest, $event->sales_order, $event->sales_order->salesOrderDetail);
        }
        
        return "fee per product recalculate";

        /**
         * distributor check, distributor does not
         * get fee at all. Only distributor
         * which has contrack active
         */
        if ($event->sales_order->dealer) {
            if (collect($event->sales_order->dealer->ditributorContract)->count() > 0) {
                $active_contract = $this->distributorActiveContract($event->sales_order->store_id);

                if ($active_contract) {
                    $sales_order_detail = $this->sales_order_detail
                        ->where([
                            "sales_order_id" => $event->sales_order->id,
                        ])
                        ->update([
                            "marketing_fee" => 0,
                            "marketing_fee_reguler" => 0,
                        ]);
                }
            } else {
                if (collect($event->sales_order->sales_order_detail)->count() > 0) {
                    collect($event->sales_order->sales_order_detail)->each(function ($detail, $event) {
                        $quantity = $detail->quantity;
                        $fee = 0;
                        $fee_reguler = 0;
                        $fee_product_reguler = collect($detail->feeProduct)->where('type', 1)->first();

                        if (!empty($fee_product_reguler)) {
                            $fee_reguler = $fee_product_reguler->fee * $quantity;
                            $this->sales_order_detail->where("id", $detail->id)->update(["marketing_fee_reguler" => $fee_reguler]);
                        }
                    });
                }
            }
        }

        $log = $this->log_worker_sales_fee->firstOrCreate([
            "sales_order_id" => $event->sales_order->id,
        ], [
            "type" => $event->sales_order->type,
            "checked_at" => now(),
        ]);

        return "fee per product calculated";
    }
}

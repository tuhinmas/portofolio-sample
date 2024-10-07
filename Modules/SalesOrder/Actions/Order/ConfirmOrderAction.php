<?php

namespace Modules\SalesOrder\Actions\Order;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Modules\SalesOrderV2\Jobs\FollowUpOrderJob;
use Modules\Invoice\Jobs\InvoiceNotificationJob;
use Modules\Invoice\Jobs\InvoiceMobileNotificationJob;
use Modules\Contest\Jobs\ContestPointCalculationByOrderJob;
use Modules\SalesOrder\Jobs\ReturnedOrderInQuarterCheckJob;
use Modules\SalesOrderV2\Jobs\CalculateMarketingFeeByOrderJob;
use Modules\SalesOrderV2\Jobs\UpdateStatusFeeOnConfirmOrderJob;
use Modules\SalesOrderV2\Jobs\GenerateFeeTargetSharingOriginJob;
use Modules\SalesOrderV2\Jobs\CalculateFeeMarketingPerProductJob;
use Modules\SalesOrderV2\Jobs\GenerateFeeRegulerSharingOriginJob;
use Modules\SalesOrderV2\Jobs\CalculateFeeRegulerSharingOriginJob;
use Modules\SalesOrderV2\Jobs\MarketingPointCalculationByOrderJob;
use Modules\SalesOrderV2\Jobs\CalculateMarketingFeeTargetByOrderJob;
use Modules\SalesOrderV2\Jobs\Indirect\IndirectTotalAmountSetterJob;
use Modules\SalesOrderV2\Jobs\Origin\SalesOrderOriginIndirectGeneratorJob;

class ConfirmOrderAction
{
    public function __invoke($sales_order)
    {
        if ($sales_order->status == "confirmed") {

            $sales_order->load("invoice");
            InvoiceNotificationJob::dispatch($sales_order->invoice);
            InvoiceMobileNotificationJob::dispatch($sales_order->invoice);

            /**
             * default grading order
             */
            $grading_id = null;
            if ($sales_order->model == "1") {
                $grading_id = DB::table('dealers')->where("id", $sales_order->store_id)->first()?->grading_id;
            } else {
                $grading_id = DB::table('sub_dealers')->where("id", $sales_order->store_id)->first()?->grading_id;
            }

            $sales_order->grading_id = $grading_id;
            $sales_order->save();

            /*
            |-------------------------------------------------
            | SALES ORDER ORIGIN
            |-----------------------------------------
             */
            Bus::chain([
                new SalesOrderOriginIndirectGeneratorJob($sales_order),
                new IndirectTotalAmountSetterJob($sales_order),
            ])->dispatch();

            /*
            |-------------------------------------------------
            | FEE MARKETING COUNTER
            |-----------------------------------------
            |
            | fee reguler marketing
            | and target
             *
             */
            Bus::chain([
                new ReturnedOrderInQuarterCheckJob($sales_order),
                new FollowUpOrderJob($sales_order),
                new UpdateStatusFeeOnConfirmOrderJob($sales_order),
                new CalculateFeeMarketingPerProductJob($sales_order),
                new GenerateFeeRegulerSharingOriginJob($sales_order),
                new CalculateFeeRegulerSharingOriginJob($sales_order),
                new CalculateMarketingFeeByOrderJob($sales_order),

                /* fee target marketing job */
                new GenerateFeeTargetSharingOriginJob($sales_order),
                new CalculateMarketingFeeTargetByOrderJob($sales_order),

                /* marketing point calculation */
                new MarketingPointCalculationByOrderJob($sales_order),

                /* contest point calculation */
                new ContestPointCalculationByOrderJob($sales_order),
            ])->dispatch();
        }
    }
}

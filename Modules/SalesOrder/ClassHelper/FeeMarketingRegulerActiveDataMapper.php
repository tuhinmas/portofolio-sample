<?php

namespace Modules\SalesOrder\ClassHelper;

use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\SalesOrder\ClassHelper\FeeSharingOriginActiveMapper;
use Modules\SalesOrder\ClassHelper\FeeSharingSoOriginHandoverDataMapper;
use Modules\SalesOrder\Traits\SalesOrderTrait;

class FeeMarketingRegulerActiveDataMapper
{
    use SalesOrderTrait;

    public function __invoke($fee_sharing_origins, $status = ["confirmed"], $save_log = false)
    {
        $fee_sharing_handnover_mapper = new FeeSharingSoOriginHandoverDataMapper();
        $fee_sharing_origin_active = new FeeSharingOriginActiveMapper();

        $fee_sharings = $fee_sharing_origin_active($fee_sharing_origins, $status);
        $fee_sharings->when($save_log, function ($fee_sharings) use ($save_log){
            return $fee_sharings->each(function ($origin) use ($save_log) {
                $log = LogMarketingFeeCounter::updateOrCreate([
                    "sales_order_id" => $origin->sales_order_id,
                    "personel_id" => $origin->personel_id,
                ], [
                    "type" => "reguler",
                    "fee_sharing_origin_id" => $origin->id,
                    "is_settle" => true,
                ]);
            });
        });

        $fee_sharing_doesthave_origin = $fee_sharings
            ->whereNull("sales_order_origin_id")
            ->groupBy("sales_order_detail_id");

        $fee_sharing_has_origin = $fee_sharings
            ->whereNotNull("sales_order_origin_id")
            ->groupBy("sales_order_origin_id");

        return
        $fee_sharing_handnover_mapper($fee_sharing_doesthave_origin)->sum("fee_shared")
         +
        $fee_sharing_handnover_mapper($fee_sharing_has_origin)->sum("fee_shared");
    }
}

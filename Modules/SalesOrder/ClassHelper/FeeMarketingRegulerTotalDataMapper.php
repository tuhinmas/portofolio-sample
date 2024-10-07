<?php

namespace Modules\SalesOrder\ClassHelper;

use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\SalesOrder\ClassHelper\FeeSharingSoOriginHandoverDataMapper;

class FeeMarketingRegulerTotalDataMapper
{
    public function __invoke($fee_sharing_origins, bool $save_log = false)
    {        
        $fee_sharing_handnover_mapper = new FeeSharingSoOriginHandoverDataMapper();

        $fee_sharing_origins->when($save_log, function ($fee_sharing_origins) use($save_log) {
            return $fee_sharing_origins
                ->each(function ($origin) {
                    $log = LogMarketingFeeCounter::updateOrCreate([
                        "sales_order_id" => $origin->sales_order_id,
                        "personel_id" => $origin->personel_id,
                    ], [
                        "type" => "reguler",
                        "fee_sharing_origin_id" => $origin->id,
                    ]);
                });
        });

        $fee_sharing_doesthave_origin = $fee_sharing_origins
            ->whereNull("sales_order_origin_id")
            ->groupBy("sales_order_detail_id");

        $fee_sharing_has_origin = $fee_sharing_origins
            ->whereNotNull("sales_order_origin_id")
            ->groupBy("sales_order_origin_id");

        return
        $fee_sharing_handnover_mapper($fee_sharing_doesthave_origin)->sum("fee_shared")
         +
        $fee_sharing_handnover_mapper($fee_sharing_has_origin)->sum("fee_shared");
    }
}

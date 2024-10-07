<?php

namespace Modules\SalesOrder\ClassHelper;

class FeeSharingSoOriginHandoverDataMapper
{
    public function __invoke($fee_sharing_grouped)
    {

        /**
         * grouped fee sharing by sales_order_detail_id
         * or by sales_order_origin_id, possible
         * two type of this grouping
         */
        return $fee_sharing_grouped
            ->map(function ($origin_per_order_detail, $sales_order_detail_id) {
                $origin_non_purchser = $origin_per_order_detail
                    ->where("fee_status", "!=", "purchaser")
                    ->where("handover_status", true)
                    ->first();

                if ($origin_non_purchser) {
                    $origin_per_order_detail
                        ->where("fee_status", "=", "purchaser")
                        ->map(function ($origin_purchser) {
                            $origin_purchser->fee_shared = 0;
                            return $origin_purchser;
                        });
                }

                return $origin_per_order_detail;
            })
            ->flatten();
    }
}

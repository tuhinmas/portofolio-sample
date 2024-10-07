<?php

namespace Modules\SalesOrder\ClassHelper;

use Modules\SalesOrder\Traits\SalesOrderTrait;

class FeeSharingOriginActiveMapper
{
    use SalesOrderTrait;

    public function __invoke($fee_sharing_origins, array $status = ["confirmed"])
    {
        return $fee_sharing_origins
            ->groupBy("sales_order_id")

            /* pending and confirmed order will separeted */
            ->filter(fn($origin_per_order) => in_array($origin_per_order[0]->salesOrder->status, $status))

            /* reject order if store has return  in quarter */
            ->reject(fn($origin_per_order) => is_affected_from_return($origin_per_order[0]->salesOrder))

            /**
             * reject if order was direct and settle beyond maturity,
             * and indirect sales considerd as settle
             */
            ->reject(fn($origin_per_order, $sales_order_id) => !$this->isSettleBeforeMaturity($origin_per_order[0]->salesOrder))
            ->flatten()
            ->groupBy("sales_order_origin_id")
            ->reject(function ($origin_so_origin, $sales_order_origin_id) {

                /**
                 * indirect sale will counted as active if direct origin
                 * from it indirect sale also counted as active
                 * (rule: settle < maximum_settle_days)
                 */
                if ($sales_order_origin_id) {

                    if ($origin_so_origin[0]->salesOrder->type == "2" && $origin_so_origin[0]->salesOrderOrigin) {
                        if ($origin_so_origin[0]->salesOrderOrigin->direct) {
                            if (!$this->isSettleBeforeMaturity($origin_so_origin[0]->salesOrderOrigin->direct)) {
                                return $origin_so_origin;
                            }
                        }
                    }
                }
            })
            ->flatten();
    }
}

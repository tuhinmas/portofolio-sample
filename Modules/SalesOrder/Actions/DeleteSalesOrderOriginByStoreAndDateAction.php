<?php
namespace Modules\SalesOrder\Actions;

use Modules\Distributor\Entities\DistributorContract;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

class DeleteSalesOrderOriginByStoreAndDateAction
{
    public function __invoke(DistributorContract $active_contract, $date = null)
    {
        if ($active_contract) {

            /**
             * delete sales order according distributor and date
             * exclude origin from first stock product
             */
            SalesOrderOrigin::query()
                ->where(function ($QQQ) use ($active_contract) {
                    return $QQQ
                        ->where(function ($QQQ) use ($active_contract) {
                            return $QQQ
                                ->where("store_id", $active_contract->dealer_id)
                                ->whereNotNull("sales_order_id");
                        })
                        ->orWhere("distributor_id", $active_contract->dealer_id);
                })
                ->whereDate("confirmed_at", ">=", ($date ? $date : $active_contract->contract_start))
                ->whereDate("confirmed_at", "<=", $active_contract->contract_end)
                ->delete();
        }

        return "distributor does not have active contract";
    }
}

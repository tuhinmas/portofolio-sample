<?php
namespace App\Traits;

use Modules\SalesOrderV2\Entities\FeeSharingOrigin;

/**
 * recalculte marketing fee in spesific quarter
 */
trait MarketingFeeTrait
{
    public function recalculateMarketingFeeRegulerTotal($personel_id, $year, $quarter)
    {
        $fee_sharing_origin = FeeSharingOrigin::query()
            ->where("personel_id", $personel_id)
            ->where("is_checked", true)
            ->where("sales_order_id", $event->invoice->sales_order_id)
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereDoesntHave("salesOrderOrigin")
                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                        return $QQQ->where("is_fee_counted", true);
                    });
            })
            ->where("is_returned", "0")
            ->get();

            return $fee_sharing_origin;
    }
}

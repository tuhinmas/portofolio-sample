<?php

namespace Modules\Personel\Actions\Fee;

use Carbon\CarbonPeriod;
use Modules\Personel\Jobs\RecalculateFeeMarketingPerQuartalJob;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;

class RecalculteAllFeeMarketingAction
{
    public function __invoke($sales_order = null, $active_contract = null)
    {
        FeeSharingSoOrigin::query()
            ->when($active_contract, function ($QQQ) use ($active_contract) {
                return $QQQ->whereHas("salesOrder", function ($QQQ) use ($active_contract) {
                    return $QQQ->where("distributor_id", $active_contract->dealer_id);
                });
            })
            ->when($sales_order && !$active_contract, function ($QQQ) use ($sales_order) {
                return $QQQ->where("sales_order_id", $sales_order->id);
            })
            ->get()
            ->pluck("personel_id")
            ->reject(fn($personel_id) => !$personel_id)
            ->unique()
            ->each(function ($personel_id) {

                $interval = CarbonPeriod::create(now()->startOfYear(), '3 month', now()->endOfYear());
                collect($interval)->map(fn($date) => $date)
                    ->each(function ($date) use ($personel_id) {
                        RecalculateFeeMarketingPerQuartalJob::dispatch($personel_id, $date->year, $date->quarter);
                    });
            });

        return "all recalculte job created";
    }
}

<?php

namespace Modules\SalesOrder\Builder;

use Illuminate\Database\Eloquent\Builder;

class FeeMarketingBuilder extends Builder
{
    public function feeMarketing($year, $quarter): self
    {
        return $this->where(function ($QQQ) use ($year, $quarter) {
            return $QQQ
                ->consideredOrder()
                ->where(function ($QQQ) {
                    return $QQQ
                        ->whereDoesntHave("salesOrderOrigin")
                        ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                            return $QQQ->where("is_fee_counted", true);
                        });
                })
                ->where(function ($QQQ) use ($year, $quarter) {
                    return $QQQ
                        ->where(function ($QQQ) use ($year, $quarter) {
                            return $QQQ
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) use ($year, $quarter) {
                                    return $QQQ
                                        ->whereYear("created_at", $year)
                                        ->whereRaw("quarter(created_at) = ?", $quarter);
                                });
                        })
                        ->orWhere(function ($QQQ) use ($year, $quarter) {
                            return $QQQ
                                ->where("type", "2")
                                ->whereYear("date", $year)
                                ->whereRaw("quarter(date) = ?", $quarter);
                        });
                });
        });
    }
}

<?php

namespace Modules\KiosDealer\Traits;

trait ScopeDealer
{
    public function scopeConsideredSalesOrderCurrentYear($query)
    {
        return $query
            ->whereHas("consideredSalesOrder", function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where("type", "2")
                            ->yearOfNota(now()->year);
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) {
                                return $QQQ->yearOfProforma(now()->year);
                            });
                    });
            });
    }
}

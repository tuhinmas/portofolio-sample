<?php

namespace Modules\Invoice\Traits;
use Carbon\Carbon;

trait ScopeInvoice
{
    public function scopeYearOfProforma($query, $year)
    {
        return $query->whereYear("created_at", $year);
    }
    
    public function scopeDeliveryStatus($query, array $delivery_statuses)
    {
        return $query
            ->when(in_array("done", $delivery_statuses) && count($delivery_statuses) == 1, function ($QQQ) {
                return $QQQ->deliveryStatusDone();
            })
            ->when(in_array("issued", $delivery_statuses) && count($delivery_statuses) == 1, function ($QQQ) {
                return $QQQ->deliveryStatusIssued();
            })
            ->when(in_array("planned", $delivery_statuses) && count($delivery_statuses) == 1, function ($QQQ) {
                return $QQQ->deliveryStatusPlanned();
            })
            ->when(in_array("done", $delivery_statuses) && in_array("issued", $delivery_statuses) && count($delivery_statuses) == 2, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusDone()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusIssued();
                    });
            })
            ->when(in_array("done", $delivery_statuses) && in_array("planned", $delivery_statuses) && count($delivery_statuses) == 2, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusDone()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusPlanned();
                    });
            })
            ->when(in_array("issued", $delivery_statuses) && in_array("planned", $delivery_statuses) && count($delivery_statuses) == 2, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusIssued()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusPlanned();
                    });
            })
            ->when(in_array("done", $delivery_statuses) && in_array("issued", $delivery_statuses) && in_array("planned", $delivery_statuses) && collect($delivery_statuses)->unique()->count() == 3, function ($QQQ) {
                return $QQQ
                    ->deliveryStatusDone()
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusIssued();
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ->deliveryStatusPlanned();
                    });
            });
    }

    public function scopeDeliveryStatusDone($query)
    {
        return $query->whereIn("delivery_status", [1, 3]);
    }

    public function scopeDeliveryStatusIssued($query)
    {
        return $query
            ->where(function ($QQQ) {
                return $QQQ
                    ->where("date_delivery", "<=", Carbon::now()->format('Y-m-d'))
                    ->orWhereNull("date_delivery");
            })
            ->whereNotIn("delivery_status", [1, 3]);
    }

    public function scopeDeliveryStatusPlanned($query)
    {
        return $query
            ->whereNotIn("delivery_status", [1, 3])
            ->where("date_delivery", ">", Carbon::now()->format('Y-m-d'));
    }

    public function scopeProformaAccordingDate($query, $operator = "=", $date_time){
        return $query->whereDate("created_at", $operator, $date_time);
    }

    public function scopeQuarterOfProforma($query, $quarter)
    {
        return $query->whereRaw("quarter(created_at) = ?", $quarter);
    }
}

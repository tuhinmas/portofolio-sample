<?php

namespace Modules\DistributionChannel\Traits;

trait ScopeDeliveryOrder
{
    /**
     * 12,3,4 is not value but condition
     * 1 => send but not received
     * 2 => send and received
     * 3 => status canceled
     * 4 => status failed
     *
     * @param [type] $query
     * @param array $statuses
     * @return void
     */
    public function scopeStatusAndReceiving($query, $statuses = [1, 2, 3, 4])
    {
        return $query
            ->when(in_array(1, $statuses), function ($QQQ) {
                $QQQ->where(function ($QQQ) {
                    $QQQ
                        ->where("status", "send")
                        ->doesntHave("receivingGoodHasReceived");
                });
            })
            ->when(in_array(2, $statuses), function ($QQQ) {
                $QQQ->orWhere(function ($QQQ) {
                    $QQQ
                        ->where("status", "send")
                        ->has("receivingGoodHasReceived");
                });
            })
            ->when(in_array(3, $statuses), function ($QQQ) {
                $QQQ->orWhere(function ($QQQ) {
                    $QQQ->where("status", "canceled");
                });
            })
            ->when(in_array(4, $statuses), function ($QQQ) {
                $QQQ->orWhere(function ($QQQ) {
                    $QQQ->where("status", "failed");
                });
            });
    }
}

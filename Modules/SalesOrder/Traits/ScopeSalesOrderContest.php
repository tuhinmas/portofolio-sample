<?php

namespace Modules\SalesOrder\Traits;

use Modules\Contest\Entities\ContestParticipant;

/**
 *
 */
trait ScopeSalesOrderContest
{

    public function scopeConsideredOrderForContest($query, ContestParticipant $contest_participant, $admitted_date, $order_date_until = null, array $status = ["confirmed", "pending"])
    {
        return $query
            ->whereIn("status", $status)
            ->where(function ($QQQ) use ($contest_participant, $admitted_date, $order_date_until) {
                return $QQQ
                    ->where(function ($QQQ) use ($contest_participant, $admitted_date, $order_date_until) {
                        return $QQQ
                            ->where("type", "1")
                            ->whereHas("invoice", function ($QQQ) use ($contest_participant, $admitted_date, $order_date_until) {
                                return $QQQ
                                    ->proformaAccordingDate(">=", $admitted_date)
                                    ->proformaAccordingDate("<=", ($order_date_until ?: $contest_participant->contest->period_date_end));
                            });
                    })
                    ->orWhere(function ($QQQ) use ($contest_participant, $admitted_date, $order_date_until) {
                        return $QQQ
                            ->where("type", "2")
                            ->indirectAccordingDate(">=", $admitted_date)
                            ->indirectAccordingDate("<=", ($order_date_until ?: $contest_participant->contest->period_date_end));
                    });
            });
    }
}

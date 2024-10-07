<?php

namespace Modules\DataAcuan\Actions\DealerGradeBlock;

use Modules\DataAcuan\Entities\GradingBlock;

class GetBlockedGradeByDateAction
{
    public function __invoke($grading_id, $date_time = null)
    {
        return GradingBlock::query()
            ->withTrashed()
            ->where("grading_id", $grading_id)
            ->where(function ($QQQ) use ($date_time) {
                return $QQQ
                    ->where(function ($QQQ) use ($date_time) {
                        return $QQQ
                            ->whereNull("deleted_at")
                            ->where("created_at", "<=", $date_time);
                    })
                    ->orWhere(function ($QQQ) use ($date_time) {
                        return $QQQ
                            ->whereNotNull("deleted_at")
                            ->where("created_at", "<=", $date_time)
                            ->where("deleted_at", ">=", $date_time);
                    });
            })
            ->first();
    }
}

<?php

namespace Modules\DataAcuan\Actions\DealerGradeSuggestion;

use Carbon\Carbon;
use Modules\DataAcuan\Entities\DealerGradeSuggestion;

class GetDealerGradeByAttribueAction
{
    /**
     * payload include
     * payment_method_id
     * maximum_settle_days
     * proforma_last_minimum_amount
     * proforma_sequential
     * proforma_total_amount
     * proforma_count
     *
     * @param [type] $payload
     * @return void
     */
    public function __invoke(array $payload, $grade_suggest_id = null)
    {
        extract($payload);

        $dealer_grade_suggestion = DealerGradeSuggestion::query()
            ->when($grade_suggest_id, function ($QQQ) use ($grade_suggest_id) {
                return $QQQ->where("id", "!=", $grade_suggest_id);
            })
            ->where("grading_id", $grading_id)
            ->where("maximum_settle_days", $maximum_settle_days)
            ->where("is_infinite_settle_days", $is_infinite_settle_days)
            ->whereDate("valid_from", Carbon::parse($valid_from)->format("Y-m-d"))
            ->where(function ($QQQ) use ($payload) {
                return $QQQ
                    ->when(collect($payload)->has(["proforma_last_minimum_amount", "proforma_sequential"]) && !collect($payload)->has(["proforma_total_amount", "proforma_count"]), function ($QQQ) use ($payload) {
                        return $QQQ
                            ->where(function ($QQQ) use ($payload) {
                                extract($payload);
                                return $QQQ
                                    ->where("proforma_last_minimum_amount", $proforma_last_minimum_amount)
                                    ->where("proforma_sequential", $proforma_sequential);
                            });
                    })
                    ->when(collect($payload)->has(["proforma_total_amount", "proforma_count"]) && !collect($payload)->has(["proforma_last_minimum_amount", "proforma_sequential"]), function ($QQQ) use ($payload) {
                        return $QQQ
                            ->where(function ($QQQ) use ($payload) {
                                extract($payload);
                                return $QQQ
                                    ->where("proforma_total_amount", $proforma_total_amount)
                                    ->where("proforma_count", $proforma_count);
                            });
                    })
                    ->when(collect($payload)->has(["proforma_last_minimum_amount", "proforma_sequential", "proforma_total_amount", "proforma_count"]), function ($QQQ) use ($payload) {
                        extract($payload);
                        return $QQQ
                            ->where(function ($QQQ) use ($proforma_last_minimum_amount, $proforma_sequential) {
                                return $QQQ
                                    ->where("proforma_last_minimum_amount", $proforma_last_minimum_amount)
                                    ->where("proforma_sequential", $proforma_sequential);
                            })
                            ->orWhere(function ($QQQ) use ($proforma_total_amount, $proforma_count) {
                                return $QQQ
                                    ->where("proforma_total_amount", $proforma_total_amount)
                                    ->where("proforma_count", $proforma_count);
                            });
                    });
            })
            ->first();

        return $dealer_grade_suggestion;
    }
}

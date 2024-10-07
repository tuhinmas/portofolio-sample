<?php

namespace Modules\DataAcuan\Actions\DealerGradeSuggestion;

use Modules\DataAcuan\Entities\DealerGradeSuggestion;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealerV2\Entities\DealerV2;

class SyncAllDealerGradeSuggestionAction
{
    public function __invoke()
    {
        $suggested_grade = new GetDealerGradeSuggestedAction();

        $deault_grade = Grading::query()
            ->where("default", true)
            ->first();

        $dealer_grade_suggestions = DealerGradeSuggestion::query()
            ->with([
                "paymentMethod",
                "grading",
            ])
            ->get();

        $dealers = DealerV2::query()
            ->with([
                "confirmedDirectSales" => function ($QQQ) {
                    return $QQQ->with([
                        "paymentMethod",
                        "invoice",
                    ]);
                },
                "grading",
            ])
            ->orderBy("dealer_id")
            ->withTrashed()
            ->get()
            ->each(function ($dealer) use (&$suggested_grade, $deault_grade, $dealer_grade_suggestions) {
                if ($dealer->confirmedDirectSales->count() > 0) {
                    $dealer->suggested_grading_id = $suggested_grade($dealer, $dealer->confirmedDirectSales, $deault_grade, $dealer_grade_suggestions);
                } else {
                    $dealer->suggested_grading_id = $deault_grade->id;
                }
                $dealer->save();
            });

        return "sync done";
    }
}

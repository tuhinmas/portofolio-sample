<?php

namespace Modules\DataAcuan\Actions\DealerGradeSuggestion;

class GetDealerGradeSuggestedAction
{
    public function __invoke($dealer, $direct_settle_order, $deault_grade, $dealer_grade_suggestions)
    {
        $dealer_grade_suggestions = $dealer_grade_suggestions
            ->sortByDesc(function ($dealer_grade_suggestion) {
                if ($dealer_grade_suggestion->proforma_sequential < $dealer_grade_suggestion->proforma_count) {
                    return $dealer_grade_suggestion->proforma_count;
                }

                return $dealer_grade_suggestion->proforma_sequential;
            });

        $maximum_proforma_count = max($dealer_grade_suggestions->first()->proforma_sequential, $dealer_grade_suggestions->first()->proforma_sequential);

        // $achievement = Invoice::query()
        //     ->with([
        //         "salesOrder" => function ($QQQ) {
        //             return $QQQ->with([
        //                 "paymentMethod",
        //             ]);
        //         },
        //     ])
        //     ->whereHas("salesOrder", function ($QQQ) use ($dealer) {
        //         return $QQQ
        //             ->where("store_id", $dealer->id)
        //             ->where("model", "1")
        //             ->whereHas("paymentMethod")
        //             ->consideredOrder();
        //     })
        //     ->where("payment_status", "settle")
        //     ->orderBy("created_at", "desc")
        //     ->limit($maximum_proforma_count)
        //     ->get();

        $achievement = $direct_settle_order
            ->sortByDesc("invoice.created_at")
            ->filter(fn($order) => $order->paymentMethod)
            ->take($maximum_proforma_count);

            $suggested_grade = $deault_grade->id;
        if ($achievement->count() > 0) {

            /* proforma_total_amount check */
            $suggested_grade_according_accumulated_proforma = $dealer_grade_suggestions
                ->filter(fn($suggest) => $suggest->proforma_total_amount > 0)
                ->filter(function ($suggest) use ($achievement) {
                    $fixed_achievement = $achievement
                        ->take($suggest->proforma_count);

                    $proforma_total_amount = $fixed_achievement->sum("invoice.total");
                    $proforma_count = $fixed_achievement->count();
                    $is_settle_day_match = $fixed_achievement
                        ->filter(function ($order) use ($suggest) {
                            return $order->invoice->payment_time <= $suggest->maximum_settle_days;
                        })
                        ->count() >= $suggest->proforma_count;

                    $is_spayment_method_match = $fixed_achievement
                        ->filter(function ($order) use ($suggest) {
                            return $order->paymentMethod->days <= $suggest->paymentMethod->days;
                        })
                        ->count() >= $suggest->proforma_count;

                    return
                        ($proforma_total_amount >= $suggest->proforma_total_amount)
                        &&
                        ($proforma_count >= $suggest->proforma_count)
                        &&
                        $is_settle_day_match
                        &&
                        $is_spayment_method_match;
                })
                ->sortByDesc("proforma_total_amount");

            $suggested_grade_according_last_proforma = $dealer_grade_suggestions
                ->filter(function ($suggest) use ($suggested_grade_according_accumulated_proforma) {
                    return !in_array($suggest->id, $suggested_grade_according_accumulated_proforma->pluck("id")->toArray());
                })
                ->filter(fn($suggest) => $suggest->proforma_last_minimum_amount > 0)
                ->filter(function ($suggest) use ($achievement) {
                    $fixed_achievement = $achievement
                        ->take($suggest->proforma_sequential);

                    /* proforma should met with minimum last proforma */
                    $is_proforma_amount_match = $fixed_achievement
                        ->filter(function ($order) use ($suggest) {
                            return $order->invoice->total >= $suggest->proforma_last_minimum_amount;
                        })
                        ->count() >= $suggest->proforma_sequential;

                    $proforma_sequential = $fixed_achievement->count();

                    $is_settle_day_match = $fixed_achievement
                        ->filter(function ($order) use ($suggest) {
                            return $order->invoice->payment_time <= $suggest->maximum_settle_days;
                        })
                        ->count() >= $suggest->proforma_sequential;

                    $is_spayment_method_match = $fixed_achievement
                        ->filter(function ($order) use ($suggest) {
                            return $order->paymentMethod->days <= $suggest->paymentMethod->days;
                        })
                        ->count() >= $suggest->proforma_sequential;

                    return
                        $is_proforma_amount_match
                        &&
                        ($proforma_sequential >= $suggest->proforma_sequential)
                        &&
                        $is_settle_day_match
                        &&
                        $is_spayment_method_match;
                })
                ->sortByDesc("proforma_last_minimum_amount")
                ->first();

            $suggested_grade = $suggested_grade_according_accumulated_proforma->first() ? $suggested_grade_according_accumulated_proforma->first()->grading_id : ($suggested_grade_according_last_proforma ? $suggested_grade_according_last_proforma->grading_id : ($deault_grade->id));
        }
        return $suggested_grade;
    }
}

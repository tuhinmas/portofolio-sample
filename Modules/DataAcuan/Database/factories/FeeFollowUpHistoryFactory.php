<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeFollowUpHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\FeeFollowUpHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $fee_follow_up = DB::table('fee_follow_ups')
            ->whereNull("deleted_at")
            ->get()
            ->map(function ($fee) {
                return collect($fee)
                    ->except([
                        "id",
                        "created_at",
                        "updated_at",
                        "deleted_at",
                    ]);
            })
            ->toArray();

        return [
            "date_start" => now()->addDays(10),
            "fee_follow_up" => $fee_follow_up,
            "is_checked" => false
        ];
    }
}

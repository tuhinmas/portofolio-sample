<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class StatusFeeHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\StatusFeeHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status_fee = DB::table('status_fee')
            ->whereNull("deleted_at")
            ->get()
            ->map(function ($fee) {
                return collect($fee)
                    ->except([
                        "id",
                        "created_at",
                        "updated_at",
                        "deleted_at"
                    ]);
            })
            ->toArray();
            
        return [
            "date_start" => now()->addDays(5),
            "status_fee" => $status_fee,
            "is_checked" => false
        ];
    }
}

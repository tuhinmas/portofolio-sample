<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Grading;

class DealerGradeSuggestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\DealerGradeSuggestion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $grading = Grading::query()
            ->where("default", true)
            ->first();
            
        $grading_suggest = Grading::query()
            ->inRandomOrder()
            ->where("id", "!=", $grading->id)
            ->first();

        return [
            "grading_id" => $grading->id,
            "suggested_grading_id" => $grading_suggest->id,
            "valid_from" => now()->subDay()->format("Y-m-d H:i:s"),
            "is_infinite_settle_days" => false,
            "payment_methods" => [
                "cash",
                "kredit",
                "bilyet giro",
            ],
            "maximum_settle_days" => $this->faker->numberBetween(1, 999999),
            "proforma_last_minimum_amount" => $this->faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 99999999999),
            "proforma_sequential" => $this->faker->numberBetween(1, 999999),
            "proforma_total_amount" => $this->faker->randomFloat($nbMaxDecimals = 2, $min = 0, $max = 99999999999),
            "proforma_count" => $this->faker->numberBetween(1, 999999),
        ];
    }
}

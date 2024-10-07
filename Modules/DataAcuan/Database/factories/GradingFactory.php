<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GradingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Grading::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "name" => "grade". $this->faker->word,
            "bg_color" => "#ffffff",
            "fore_color" => "#000000",
            "bg_gradien" => "0",
            "credit_limit" => "100000000",
            "description" => $this->faker->sentence($nbWords = 6, $variableNbWords = true),
            "action" => null,
            "default" => false,
            "maximum_payment_days" => 10,
            "max_unsettle_proformas" => 1
        ];
    }
}



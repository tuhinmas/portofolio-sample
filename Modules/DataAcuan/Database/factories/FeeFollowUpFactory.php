<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FeeFollowUpFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\FeeFollowUp::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "follow_up_days" => 61,
            "fee" => "5",
            "settle_days" => 40,
        ];
    }
}

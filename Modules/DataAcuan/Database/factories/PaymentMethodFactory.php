<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "name" => "kredit",
            "days" => 5,
            "is_for_marketing" => true
        ];
    }
}

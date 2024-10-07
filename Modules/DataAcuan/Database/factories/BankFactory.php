<?php
namespace Modules\DataAcuan\Database\factories;

use Modules\DataAcuan\Entities\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Bank::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid,
            'name' => $this->faker->name,
            'code' => $this->faker->randomDigit,
            'country_id' =>Country::inRandomOrder()->first()->id,
            'IBAN' => $this->faker->randomDigit,
            'swift_code' => $this->faker->numberBetween($min = 000, $max = 999),
        ];
    }
}

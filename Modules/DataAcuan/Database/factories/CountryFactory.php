<?php
namespace Modules\DataAcuan\Database\factories;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Country::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid,
            'code' => Str::random(2),
            'code3' => Str::random(3),
            'codeNumeric' => $this->faker->numberBetween($min = 1, $max = 999),
            'domain' => Str::random(2),
            'label_nl' => $this->faker->word,
            'label_en' => $this->faker->word,
            'label_de' => $this->faker->word,
            'label_es' => $this->faker->word,
            'label_fr' => $this->faker->word,
            'postCode' => $this->faker->numberBetween($min = 1, $max = 999),
            'active' => true 
        ];
    }
}


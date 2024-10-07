<?php
namespace Modules\Organisation\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HoldingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Organisation\Entities\Holding::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'date_standing' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
            'note' => $this->faker->word,
        ];
    }
}


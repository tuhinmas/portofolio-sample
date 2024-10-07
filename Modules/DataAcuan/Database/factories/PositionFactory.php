<?php
namespace Modules\DataAcuan\Database\factories;

use Modules\DataAcuan\Entities\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Position::class;

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
            'division_id' => Division::factory()->create()->id,
            'job_description' => $this->faker->name,
            'job_definition' => $this->faker->name,
            'job_specification' => $this->faker->name,
        ];
    }
}


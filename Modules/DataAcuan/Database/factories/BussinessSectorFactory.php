<?php
namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\BussinessSectorCategory;

class BussinessSectorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\BussinessSector::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid,
            'name' => 'dodolan',
            'category_id' => BussinessSectorCategory::factory()->create()->id,
        ];
    }
}


<?php
namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id'   => $this->faker->uuid,
            'name' => 'pupuk kandang cair ampuh',
            'size' => '200 ml',
            'unit' => 'botol',
            'type' => 'liquid',
            'weight' => 0.5
        ];
    }
}


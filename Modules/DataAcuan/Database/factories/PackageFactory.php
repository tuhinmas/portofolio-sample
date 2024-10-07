<?php
namespace Modules\DataAcuan\Database\factories;

use Modules\DataAcuan\Entities\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Package::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $product = Product::factory()->create();
        return [
            'id' => $this->faker->uuid,
            'product_id' => $product->id,
            'packaging' => "Dus",
            'quantity_per_package' => 12,
            'weight' => 12,
            'unit' => "botol",
        ];
    }
}


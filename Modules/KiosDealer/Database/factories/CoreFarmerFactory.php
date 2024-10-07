<?php
namespace Modules\KiosDealer\Database\factories;

use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoreFarmerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\CoreFarmer::class;

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
            'telephone' => $this->faker->e164PhoneNumber,
            'address' => $this->faker->address,
            'store_id' => Store::factory()->create()->id,
        ];
    }
}


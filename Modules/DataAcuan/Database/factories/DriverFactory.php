<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Driver::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();
        return [
            "transportation_type" => "mini van",
            "police_number" => "AB 89776 BG",
            "id_driver" => $personel->id,
            "driver_phone_number" => "085956289255",
            "capacity" => "800",
        ];
    }
}

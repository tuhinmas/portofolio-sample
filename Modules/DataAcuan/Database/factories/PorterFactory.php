<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class PorterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Porter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();
        $warehouese = Warehouse::factory()->create();

        return [
            "personel_id" => $personel,
            "warehouse_id" => $warehouese->id,
            "updated_by" => null,
        ];
    }
}

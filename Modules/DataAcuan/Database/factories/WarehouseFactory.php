<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organisation\Entities\Organisation;
use Modules\Personel\Entities\Personel;

class WarehouseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();
        $organisation = Organisation::factory()->create();

        return [
            "code" => "TEST",
            "name" => "TEST13/12/22",
            "id_organisation" => $organisation->id,
            "telp" => "2131234124",
            "personel_id" => $personel->id,
            "address" => "Jl. A",
            "province_id" => "34",
            "city_id" => "3404",
            "district_id" => "3404170",
        ];
    }
}

<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Region::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $mdm_position = Position::firstOrCreate([
            "name" => "Marketing District Manager (MDM)"
        ]);

        $mdm = Personel::factory()->create([
            "position_id" => $mdm_position->id
        ]);

        return [
            "name" => "region-test",
            "personel_id" => $mdm->id,
            "target" => "100000000",
        ];
    }
}

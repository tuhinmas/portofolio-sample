<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Region;
use Modules\Personel\Entities\Personel;

class SubRegionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\SubRegion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $region = Region::factory()->create();
        $rmc_position = Position::firstOrCreate([
            "name" => "Regional Marketing Coordinator (RMC)",
        ]);

        $rmc = Personel::factory()->create([
            "position_id" => $rmc_position->id,
            "supervisor_id" => $region->personel_id,
        ]);

        return [
            "name" => "subregion-test",
            "region_id" => $region->id,
            "personel_id" => $rmc->id,
            "target" => "100000000",
        ];
    }

    public function marketingMDM()
    {
        return $this->state(function (array $attributes) {
            $region = Region::findOrFail($attributes["region_id"]);
            return [
                'personel_id' => $region->personel_id,
            ];
        });
    }
}

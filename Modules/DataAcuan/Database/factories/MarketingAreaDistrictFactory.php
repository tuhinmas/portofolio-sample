<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Entities\Personel;

class MarketingAreaDistrictFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\MarketingAreaDistrict::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $sub_region = SubRegion::factory()->create();
        $applicator_position = Position::factory()->create([
            "name" => "Aplikator",
        ]);

        $rm_position = Position::firstOrCreate([
            "name" => "Regional Marketing (RM)",
        ]);

        $marketing = Personel::factory()->create([
            "position_id" => $rm_position->id,
            "status" => "1",
            "supervisor_id" => $sub_region->personel_id,
        ]);

        $applicator = Personel::factory()->create([
            "supervisor_id" => $marketing->id,
            "position_id" => $applicator_position->id,
            "name" => "applicator-test",
            "status" => "1",
        ]);

        return [
            "province_id" => "94",
            "city_id" => "9471",
            "district_id" => "9471010",
            "personel_id" => $marketing->id,
            "applicator_id" => function () use ($applicator) {
                return $applicator->id;
            },
            "sub_region_id" => $sub_region->id,
        ];
    }

    public function applicator()
    {
        return $this->state(function (array $attributes) {
            $rm_position = Position::firstOrCreate([
                "name" => "Regional Marketing (RM)",
            ]);
            
            $applicator_position = Position::firstOrCreate([
                "name" => "Aplikator",
            ]);

            $marketing = Personel::factory()->create([
                "position_id" => $rm_position->id,
                "status" => "1",
            ]);
            
            $applicator = Personel::factory()->create([
                "position_id" => $applicator_position->id,
                "supervisor_id" => $marketing->id,
                "status" => "1",
            ]);

            return [
                'personel_id' => $marketing->id,
                "applicator_id" => $applicator->id
            ];
        });
    }
    public function marketingRM()
    {
        return $this->state(function (array $attributes) {
            $rm_position = Position::firstOrCreate([
                "name" => "Regional Marketing (RM)",
            ]);

            $marketing = Personel::factory()->create([
                "position_id" => $rm_position->id,
                "status" => "1",
            ]);

            return [
                'personel_id' => $marketing->id,
            ];
        });
    }

    public function marketingRmUnderMDM()
    {
        return $this->state(function (array $attributes) {
            $rm_position = Position::firstOrCreate([
                "name" => "Regional Marketing (RM)",
            ]);

            $marketing = Personel::factory()->create([
                "position_id" => $rm_position->id,
                "status" => "1",
            ]);

            $sub_region = SubRegion::findOrFail($attributes["sub_region_id"]);
            $region = Region::findOrFail($sub_region->region_id);
            $sub_region->personel_id = $region->personel_id;
            $sub_region->save();

            $marketing->supervisor_id = $region->personel_id;
            $marketing->save();

            return [
                'personel_id' => $marketing->id,
                "province_id" => "94",
                "city_id" => "9401",
                "district_id" => "9401021",
            ];
        });
    }

    public function marketingRMC()
    {
        return $this->state(function (array $attributes) {
            $sub_region = SubRegion::findOrFail($attributes["sub_region_id"]);
            return [
                'personel_id' => $sub_region->personel_id,
                "province_id" => "94",
                "city_id" => "9471",
                "district_id" => "9471020",
            ];
        });
    }

    public function marketingMDM()
    {
        return $this->state(function (array $attributes) {
            $sub_region = SubRegion::findOrFail($attributes["sub_region_id"]);
            $region = Region::findOrFail($sub_region->region_id);
            $sub_region->personel_id = $region->personel_id;
            $sub_region->save();

            return [
                'personel_id' => $region->personel_id,
                "province_id" => "94",
                "city_id" => "9401",
                "district_id" => "9401013",
            ];
        });
    }

    public function marketingMM()
    {
        return $this->state(function (array $attributes) {

            $marketing_MM = Personel::query()
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->marketingManager();
                })
                ->where("status", "1")
                ->first();

            if (!$marketing_MM) {
                $marketing_MM = Personel::factory()->marketingMM()->create();
            }

            $sub_region = SubRegion::findOrFail($attributes["sub_region_id"]);
            $sub_region->personel_id = $marketing_MM->id;
            $sub_region->save();

            $region = Region::findOrFail($sub_region->region_id);
            $region->personel_id = $marketing_MM->id;
            $region->save();

            return [
                'personel_id' => $marketing_MM->id,
                "province_id" => "94",
                "city_id" => "9435",
                "district_id" => "9435040",
            ];
        });
    }
}

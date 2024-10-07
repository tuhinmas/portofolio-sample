<?php

namespace Modules\KiosDealer\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class DealerTempFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\DealerTemp::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $entity = DB::table('entities')->whereNull("deleted_at")->first();
        $agency_level_id = DB::table('agency_levels')->whereNull("deleted_at")->where("name", "R3")->first();
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();

        return [
            "address" => $this->faker->address,
            "email" => $this->faker->email,
            "entity_id" => $entity?->id,
            "gmaps_link" => $this->faker->url,
            "name" => $this->faker->name,
            "owner" => $this->faker->name,
            "owner_address" => $this->faker->address,
            "owner_ktp" => $this->faker->numberBetween(100000, 999999),
            "owner_npwp" => $this->faker->numberBetween(100000, 999999),
            "owner_telephone" => $this->faker->numberBetween(100000, 999999),
            "prefix" => $this->faker->word,
            "sufix" => $this->faker->word,
            "telephone" => $this->faker->numberBetween(100000, 999999),
            "latitude" => $this->faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
            "longitude" => $this->faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = 360.00),
            "status" => "draft",
            "status_color" => "000000",
            "agency_level_id" => $agency_level_id->id,
            "status_fee" => $status_fee->id,
        ];
    }
}

<?php

namespace Modules\KiosDealer\Database\factories;

use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubDealerTempFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\SubDealerTemp::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $entity = DB::table('entities')->whereNull("deleted_at")->first();

        return [
            "personel_id" =>  Personel::factory(),
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
            "latitude" => "-7.4541444",
            "longitude" => "110.4412777",
            "status" => "draft",
            "status_color" => "000000",
        ];
    }
}

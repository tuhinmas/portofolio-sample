<?php

namespace Modules\KiosDealer\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class SubDealerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\Subdealer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $entity = DB::table('entities')->whereNull("deleted_at")->first();
        $cust_sub = DB::table('sub_dealers')
            ->whereNull("deleted_at")
            ->orderBy("sub_dealer_id", "desc")
            ->first();

        return [
            "sub_dealer_id" => $cust_sub ? $cust_sub->sub_dealer_id + 1 : 1,
            "prefix" => $this->faker->word,
            "name" => $this->faker->name,
            "sufix" => $this->faker->word,
            "address" => $this->faker->address,
            "email" => $this->faker->email,
            "entity_id" => $entity?->id,
            "gmaps_link" => $this->faker->url,
            "owner" => $this->faker->name,
            "owner_address" => $this->faker->address,
            "owner_ktp" => $this->faker->numberBetween(100000, 999999),
            "owner_npwp" => $this->faker->numberBetween(100000, 999999),
            "owner_telephone" => $this->faker->numberBetween(100000, 999999),
            "telephone" => $this->faker->numberBetween(100000, 999999),
            "latitude" => -7.4541444,
            "longitude" => 110.4412777,
            "status" => "accepted",
            "status_color" => "000000",
        ];
    }
}

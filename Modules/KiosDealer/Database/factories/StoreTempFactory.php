<?php

namespace Modules\KiosDealer\Database\factories;

use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreTempFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\StoreTemp::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->first();

        return [
            'id' => $this->faker->uuid,
            'personel_id' => $personel->id,
            'name' => $this->faker->name,
            'telephone' => $this->faker->numberBetween($min = 1000000, $max = 9999999),
            'status' => 'filed',
            'address' => 'Jl. Banjar no. 34, Krapyak, Caturtunggal, Depok, Sleman, DIY',
            'gmaps_link' => 'https://www.google.co.id/maps/place/Widiyantoro/@-7.7583097,111.277026,17.91z/data=!4m5!3m4!1s0x2e79850679c4ca05:0x44eb6ae1a3b7d5b0!8m2!3d-7.7585268!4d111.2760339',
            "province_id" => "34",
            "city_id" => "3403",
            "district_id" => "3403090"
        ];
    }
}


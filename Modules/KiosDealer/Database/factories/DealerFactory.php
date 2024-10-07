<?php

namespace Modules\KiosDealer\Database\factories;

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Grading;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\Dealer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $entity = DB::table('entities')->whereNull("deleted_at")->first();
        $agency_level = DB::table('agency_levels')->whereNull("deleted_at")->first();
        $personel = Personel::factory()->create();

        return [
            "dealer_id" => self::dealerIdGeneartor(),
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
            "latitude" => $this->faker->randomFloat($nbMaxDecimals = NULL, $min = 0, $max = 360.00),
            "longitude" => $this->faker->randomFloat($nbMaxDecimals = NULL, $min = 0, $max = 360.00),
            "status" => "draft",
            "status_color" => "000000",
            "agency_level_id" => $agency_level->id,
            "personel_id" => $personel->id,
            "grading_id" => Grading::factory()->create(["name" => "grade-test"])->id
        ];
    }

    public static function dealerIdGeneartor()
    {
        $dealer = DB::table('dealers')
            ->whereNull("deleted_at")
            ->orderBy('dealer_id', 'desc')
            ->first();

        return (int) ($dealer?->dealer_id + 1);
    }
}


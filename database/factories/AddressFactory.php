<?php

namespace Database\Factories;

use App\Models\Address;
use Modules\DataAcuan\Entities\Country;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $organisation = Organisation::factory()->create();
        $country = Country::inRandomOrder()->first();
        return [
            'parent_id' => $organisation->id,
            'type' => $this->faker->word,
            'detail_address' => 'jakal, Depok, Sleman, DIY, 40825',
            'gmaps_link' => $this->faker->word,
            'country_id' => $country->id,
            "district_id" => "1107061",
            "city_id" => "1107",
            "province_id" => "11"
        ];
    }
}

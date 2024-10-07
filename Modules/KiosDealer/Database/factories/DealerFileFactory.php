<?php

namespace Modules\KiosDealer\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\KiosDealer\Entities\Dealer;

class DealerFileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\DealerFile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dealer = Dealer::factory()->create();
        return [

            "dealer_id" => $dealer->id,
            "file_type" => "KTP",
            "data" => "-KTP.png",
        ];
    }
}

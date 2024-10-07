<?php

namespace Modules\KiosDealer\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\KiosDealer\Entities\DealerTemp;

class DealerFileTempFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\DealerFileTemp::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dealer_temp = DealerTemp::factory()->create();
        return [

            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "KTP.png",
        ];
    }
}

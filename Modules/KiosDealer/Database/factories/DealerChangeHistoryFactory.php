<?php

namespace Modules\KiosDealer\Database\factories;

use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealerChangeHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\DealerChangeHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dealer = Dealer::factory()->create();
        $dealer_temp = DealerTemp::factory()->create([
            "dealer_id" => $dealer->id,
        ]);

        return [
            "dealer_id" => $dealer,
            "dealer_temp_id" => $dealer_temp->id,
            "submited_at" => now(),
            "submited_by" => $dealer->personel_id,
            "confirmed_by" => $dealer->personel_id,
            "confirmed_at" => now(),
            "approved_at" => now(),
            "approved_by" => $dealer->personel_id,
        ];
    }
}

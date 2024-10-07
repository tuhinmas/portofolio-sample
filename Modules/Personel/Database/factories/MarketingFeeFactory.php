<?php

namespace Modules\Personel\Database\factories;

use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketingFeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Personel\Entities\MarketingFee::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();

        return [
            "personel_id" => $personel->id,
            "fee_reguler_total" => 10000,
            "fee_reguler_settle" => 10000,
            "fee_reguler_settle_pending" => 10000,
            "fee_target_total" => 10000,
            "fee_target_settle" => 10000,
            "fee_target_settle_pending" => 10000,
            "year" => now()->year,
            "quarter" => now()->quarter,
        ];
    }
}

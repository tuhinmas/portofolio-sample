<?php

namespace Modules\Personel\Database\factories;

use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\MarketingFee;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketingFeePaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Personel\Entities\MarketingFeePayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();
        $marketing_fee = MarketingFee::factory()->create();
        return [
            "personel_id" => $personel->id,
            "marketing_fee_id" => $marketing_fee->id,
            "amount" => 0,
            "reference_number" => $this->faker->word,
            "date" => now()->format("Y-m-d H:i:s"),
            "note" => "pembayaran fee",
        ];
    }
}

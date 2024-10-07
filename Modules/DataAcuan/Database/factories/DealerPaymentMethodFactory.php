<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\KiosDealer\Entities\Dealer;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealerPaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\DealerPaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dealer = Dealer::factory()->create();
        $payment_method = PaymentMethod::first();
        
        return [
            "dealer_id" => $dealer->id,
            "payment_method_id" => $payment_method->id,
        ];
    }
}

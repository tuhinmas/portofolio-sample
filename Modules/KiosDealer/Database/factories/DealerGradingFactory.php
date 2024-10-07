<?php

namespace Modules\KiosDealer\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\Grading;
use Modules\KiosDealer\Entities\Dealer;

class DealerGradingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\KiosDealer\Entities\DealerGrading::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dealer = Dealer::factory()->create();
        $grading = Grading::factory()->create();
        $user = User::query()
            ->whereNotNull("personel_id")
            ->first();
        return [
            "dealer_id" => $dealer->id,
            "grading_id" => $grading->id,
            "custom_credit_limit" => 1000000,
            "user_id" => $user->id,
        ];
    }
}

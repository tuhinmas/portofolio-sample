<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealerBenefitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\DealerBenefit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        $grading = Grading::query()
            ->where("default", false)
            ->first();

        $payment_method = PaymentMethod::query()
            ->where("name", "Cash")
            ->first();
            
        return [
            "grading_id" => $grading->id,
            "payment_method_id" => $payment_method->id,
            "agency_level_id" => null,
            "old_price_usage" => true,
            "old_price_usage_limit" => 1,
            "old_price_days_limit" => 10,
            "benefit_discount" => [
                [
                    "type" => "always",
                    "stage" => 1,
                    "discount" => [
                        [
                            "discount" => 2.5,
                            "minimum_order" => 0,
                            "maximum_discount" => 0,
                        ],
                    ],
                    "product_category" => [
                        1,
                    ],
                ],
                [
                    "type" => "threshold",
                    "stage" => 2,
                    "discount" => [
                        [
                            "discount" => 0.5,
                            "minimum_order" => 300000000,
                            "maximum_discount" => 0,
                        ],
                    ],
                    "product_category" => [
                        1,
                    ],
                ],
            ],
        ];
    }
}

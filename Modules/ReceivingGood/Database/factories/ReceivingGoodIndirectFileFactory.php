<?php

namespace Modules\ReceivingGood\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale;

class ReceivingGoodIndirectFileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\ReceivingGood\Entities\ReceivingGoodIndirectFile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $receiving_good = ReceivingGoodIndirectSale::factory()->create();
        return [
            "receiving_good_id" => $receiving_good->id,
            "caption" => $this->faker->word,
            "attachment" => "xxx.jpg",
            "attachment_status" => "confirm",
        ];
    }
}

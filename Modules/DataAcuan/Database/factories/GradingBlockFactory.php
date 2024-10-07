<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Grading;
use Modules\Personel\Entities\Personel;

class GradingBlockFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\GradingBlock::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $grading = Grading::query()
            ->where("default", true)
            ->first();

        $personel = Personel::factory()->create();

        return [
            "grading_id" => $grading->id,
            "personel_id" => $personel->id,
            "is_active" => true,
        ];
    }
}

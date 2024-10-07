<?php

namespace Modules\Personel\Database\factories;

use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonelStatusHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Personel\Entities\PersonelStatusHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();
        $support = Personel::factory()->create();
        return [
            "start_date" => now()->startOfYear()->startOfDay(),
            "end_date" =>  now()->endOfYear()->endOfDay(),
            "status" => "1",
            "personel_id" => $personel->id,
            "change_by" => $support->id,
            "is_new" => 0,
            "is_checked" => 1,
        ];
    }
}

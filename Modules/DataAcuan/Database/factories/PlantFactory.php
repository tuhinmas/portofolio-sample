<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\DataAcuan\Entities\PlantCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Plant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $category = PlantCategory::factory()->create();
        return [
            "plant_category_id" => $category->id,
            "name" => "Jeruk Bayi",
            "varieties" => "bayi gede",
            "scientific_name" => "jeruk purutius kecutius",
        ];
    }
}

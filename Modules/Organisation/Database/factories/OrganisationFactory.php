<?php
namespace Modules\Organisation\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organisation\Entities\Entity;
use Modules\Organisation\Entities\Holding;

class OrganisationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Organisation\Entities\Organisation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $holding = Holding::factory()->create();
        $entity = Entity::factory()->create();
        return [
            'id' => $this->faker->uuid,
            'entity_id' => $entity->id,
            'holding_id' => $holding->id,
            'name' => $this->faker->name,
            'npwp' => $this->faker->numberBetween($min = 1, $max = 999),
            'note' => $this->faker->word,
        ];
    }
}

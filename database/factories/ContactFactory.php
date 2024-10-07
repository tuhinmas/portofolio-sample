<?php

namespace Database\Factories;

use App\Models\Contact;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => $this->faker->uuid,
            'parent_id' => Organisation::factory()->create()->id,
            'contact_type' => 'telephone',
            'data' => '123123123123'
        ];
    }
}

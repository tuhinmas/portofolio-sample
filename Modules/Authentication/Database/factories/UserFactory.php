<?php

namespace Modules\Authentication\Database\factories;

use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Authentication\Entities\User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $personel = Personel::factory()->create();

        return [
            "name" => $this->faker->name,
            "email" => $this->faker->email,
            "email_verified_at" => null,
            "password" => bcrypt("password"),
            "last_login_at" => null,
            "personel_id" => $personel->id,
            "username" => $this->faker->username,
        ];
    }
}

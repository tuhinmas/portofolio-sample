<?php

namespace Modules\Notification\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationMarketingGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Notification\Entities\NotificationMarketingGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "menu" => "Direct Sales",
            "role" => "Marketing",
        ];
    }
}

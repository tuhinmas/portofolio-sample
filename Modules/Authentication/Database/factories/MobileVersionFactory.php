<?php

namespace Modules\Authentication\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MobileVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Authentication\Entities\MobileVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "version" => "00.00.00",
            "environment" => "staging",
            "note" => "test link",
            "link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/mobile/apk/staging/APK+Share.pdf",
        ];
    }
}

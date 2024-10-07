<?php

use Modules\Authentication\Entities\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("login support for web", function () {
    $user = User::factory()->create([
        "email" =>  "support@mail.com",
        "password" => bcrypt("password")
    ]);
    $response = $this->postJson("/api/auth/v2/login", [
        "login" => "support@mail.com",
        "password" => "password",
        "is_mobile" => false,
    ]);

    $response->assertStatus(200);
});

test("login support for mobile", function () {
    $user = User::factory()->create([
        "email" =>  "support@mail.com",
        "password" => bcrypt("password")
    ]);
    $response = $this->postJson("/api/auth/v2/login", [
        "login" => "support@mail.com",
        "password" => "password",
        "is_mobile" => true,
        "device_id" => "1212",
        "latitude" => "-7.719638",
        "longitude" => "110.383942",
        "manufacture" => "Xiomei",
        "model" => "Poco-poco",
        "version_app" => "A10",
        "version_os" => "Android10",
    ]);

    $response->assertStatus(200);
});

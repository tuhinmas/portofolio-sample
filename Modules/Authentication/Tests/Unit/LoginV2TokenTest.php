<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\Personel;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can get me: password has change", function () {
    $personel = Personel::factory()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
        "password_change_at" => now()->addDay(),
    ]);

    $token = JWTAuth::fromUser($user);

    $response = actingAsMarketing(null, $personel->id)
        ->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])
        ->json("GET", "/api/auth/v2/me", [
            "record_access" => [
                "user_id" => $user->id,
                "device_id" => "device",
                "latitude" => "-7.785981",
                "longitude" => "110.3653802",
                "manufacture" => "manufacture",
                "model" => "model-test",
                "version_app" => "app",
                "version_os" => "os-test",
            ],
        ]);

    $response->assertStatus(403);
    expect($response->getData()->response_message)->toEqual("unauthorized action");
    expect($response->getData()->data->message)->toEqual("invalid token, password has change");
});

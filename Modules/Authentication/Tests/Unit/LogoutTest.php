<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Authentication\Entities\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can logout from valid token", function () {

    $user = User::factory()->create([
        "email" => "support@mail.com",
        "password" => bcrypt("password"),
    ]);
    $token = JWTAuth::fromUser($user);
    $response = actingAsSupport()->postJson("/api/auth/v2/logout",
        [],
        [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ]
    );

    $response->assertStatus(200);
    expect($response->getData()->response_code)->toBe("00");
    expect($response->getData()->response_message)->toBe("logout success");
    expect($response->getData()->data)->toBeObject();
});

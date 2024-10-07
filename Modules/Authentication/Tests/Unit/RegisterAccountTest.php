<?php

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\Personel;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can register new account from non existing email", function () {
    $faker = Faker::create('id_ID');
    $personel = Personel::factory()->create();
    $response = actingAsSupport()->postJson("/api/auth/v2/register", [
        "name" => $faker->name,
        "email" => $faker->email,
        "username" => $faker->userName,
        "password" => "password",
        "password_confirmation" => "password",
        "personel_id" => $personel->id,
    ]);

    expect($response->getData()->response_code)->toEqual("00");
    expect($response->getData()->data->roles)->toBeArray();
    expect(count($response->getData()->data->roles))->toBeGreaterThanOrEqual(1);
    $response->assertStatus(200);
});

test("can not register new account from existing email", function () {
    $faker = Faker::create('id_ID');

    $personel = Personel::factory()->create();
    $personel_2 = Personel::factory()->create();
    $user = User::factory()->create([
        "email" => "support@mail.com",
        "password" => bcrypt("password"),
        "personel_id" => $personel_2->id,
    ]);

    $response = actingAsSupport()->postJson("/api/auth/v2/register", [
        "name" => $user->name,
        "email" => $user->email,
        "username" => $user->username,
        "password" => "password",
        "password_confirmation" => "password",
        "personel_id" => $personel->id,
    ]);

    expect($response->getData()->response_code)->toEqual("02");
    $response->assertStatus(200);
});

test("can update password from existing user", function () {
    $personel_2 = Personel::factory()->create();
    $user = User::factory()->create([
        "email" => "support@mail.com",
        "password" => bcrypt("password"),
        "personel_id" => $personel_2->id,
    ]);

    $response = actingAsSupport()->putJson("/api/auth/v2/register-update/" . $user->id, [
        "password" => "new password",
        "password_confirmation" => "new password",
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data->password_change_at)->not->toBeNull();
});

test("can update password from existing user, pasword confirmation needed", function () {
    $personel_2 = Personel::factory()->create();
    $user = User::factory()->create([
        "email" => "support@mail.com",
        "password" => bcrypt("password"),
        "personel_id" => $personel_2->id,
    ]);

    $response = actingAsSupport()->putJson("/api/auth/v2/register-update/" . $user->id, [
        "password" => "new password"
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->password[0])->toEqual("validation.confirmed");
});

test("can update password from existing user, min password", function () {
    $personel_2 = Personel::factory()->create();
    $user = User::factory()->create([
        "email" => "support@mail.com",
        "password" => bcrypt("password"),
        "personel_id" => $personel_2->id,
    ]);

    $response = actingAsSupport()->putJson("/api/auth/v2/register-update/" . $user->id, [
        "password" => "new",
        "password_confirmation" => "new",
    ]);

    $response->assertStatus(422);
    expect($response->getData()->data->password[0])->toEqual("validation.min.string");
});

test("can't update password from not fount user", function () {
    $response = actingAsSupport()->putJson("/api/auth/v2/register-update/xxx", [
        "password" => "password",
        "password_confirmation" => "password",
    ]);

    $response->assertStatus(404);
});

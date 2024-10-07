<?php

use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Store;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\CoreFarmer;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("login marketing for web", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = $this->postJson("/api/auth/v2/login", [
        "login" => $user->email,
        "password" => "password",
        "is_mobile" => false,
    ]);

    $response->assertStatus(200);
});

test("login marketing for mobile", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = $this->postJson("/api/auth/v2/login", [
        "login" => $user->email,
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

test("login marketing from mobile must send latitude", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = $this->postJson("/api/auth/v2/login", [
        "login" => $user->email,
        "password" => "password",
        "is_mobile" => true,
    ]);

    $response->assertStatus(422);
});

test("can get me", function () {
    $personel = Personel::factory()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
    ]);

    $response = actingAsMarketing(null, $personel->id)->json("GET", "/api/auth/v2/me", [
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
    
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data->user)->toBeObject();
    expect($response->getData()->data->user)->toHaveKeys(["profile", "permissions", "roles"]);
    expect($response->getData()->data)->toHaveKeys(["active_requirement"]);
    expect($access_history)->toBeTruthy();
    $response->assertStatus(200);
});

/**
 * LOGIN LIKE PRODUCTION ENVIRONMENT
 */
test("login as production environment", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = $this->postJson("/api/auth/v2/login", [
        "login" => $user->email,
        "password" => "password",
        "is_mobile" => true,
        "device_id" => "1212",
        "latitude" => "-7.719638",
        "longitude" => "110.383942",
        "manufacture" => "Xiomei",
        "model" => "Poco-poco",
        "version_app" => "A10",
        "version_os" => "Android10",
        "set_environment_like_production" => true,
    ]);
    
    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "token",
        "expires_in",
        "active",
        "active_requirement",
        "user",
        "requirement_store_left",
        "last_mobile_version",
    ]);

    expect($response->getData()->data->active_requirement)->toHaveKeys([
        "Regional Marketing (RM)",
        "Regional Marketing Coordinator (RMC)",
        "Marketing District Manager (MDM)",
        "Assistant MDM",
        "Aplikator",
    ]);

    expect($response->getData()->data->user->has_store_count)->toEqual(0);
    expect($response->getData()->data->active)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(30);
});

test("me as production environment", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = actingAsMarketing(null,   $user->personel_id)->json("GET", "/api/auth/v2/me", [
        "set_environment_like_production" => true,
        "record_access" => [
            "latitude" => -7.888012140918093,
            "longitude" => 110.60661305196203,
            "is_mobile" => 1,
            "device_id" => "a04e",
            "model" => "SM-A042F",
            "manufacture" => "samsung",
            "version_os" => "14",
            "version_app" => "1.12.8",
        ],
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "expires_in",
        "active",
        "active_requirement",
        "user",
        "requirement_store_left",
        "last_mobile_version",
    ]);

    expect($response->getData()->data->active_requirement)->toHaveKeys([
        "Regional Marketing (RM)",
        "Regional Marketing Coordinator (RMC)",
        "Marketing District Manager (MDM)",
        "Assistant MDM",
        "Aplikator",
    ]);

    expect($response->getData()->data->user->has_store_count)->toEqual(0);
    expect($response->getData()->data->active)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(30);
});

test("login as production environment RM has 30 kios", function () {
    $personel = Personel::factory()->marketingRM()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
        "password" =>  bcrypt("password")
    ]);

    $stores = Store::factory()->count(30)->create([
        "personel_id" => $personel->id
    ]);
    foreach ($stores as $store) {
        CoreFarmer::factory()->count(3)->create([
            "store_id" => $store->id
        ]);

    }
    $response = $this->postJson("/api/auth/v2/login", [
        "login" => $user->email,
        "password" => "password",
        "is_mobile" => true,
        "device_id" => "1212",
        "latitude" => "-7.719638",
        "longitude" => "110.383942",
        "manufacture" => "Xiomei",
        "model" => "Poco-poco",
        "version_app" => "A10",
        "version_os" => "Android10",
        "set_environment_like_production" => true,
    ]);
    
    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "token",
        "expires_in",
        "active",
        "active_requirement",
        "user",
        "requirement_store_left",
        "last_mobile_version",
    ]);

    expect($response->getData()->data->active_requirement)->toHaveKeys([
        "Regional Marketing (RM)",
        "Regional Marketing Coordinator (RMC)",
        "Marketing District Manager (MDM)",
        "Assistant MDM",
        "Aplikator",
    ]);

    expect($response->getData()->data->user->has_store_count)->toEqual(30);
    expect($response->getData()->data->active)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("me as production environment RM has 30 kios", function () {
    $personel = Personel::factory()->marketingRM()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
        "password" =>  bcrypt("password")
    ]);

    $stores = Store::factory()->count(30)->create([
        "personel_id" => $personel->id
    ]);
    foreach ($stores as $store) {
        CoreFarmer::factory()->count(3)->create([
            "store_id" => $store->id
        ]);

    }

    $response = actingAsMarketing(null,   $user->personel_id)->json("GET", "/api/auth/v2/me", [
        "set_environment_like_production" => true,
        "record_access" => [
            "latitude" => -7.888012140918093,
            "longitude" => 110.60661305196203,
            "is_mobile" => 1,
            "device_id" => "a04e",
            "model" => "SM-A042F",
            "manufacture" => "samsung",
            "version_os" => "14",
            "version_app" => "1.12.8",
        ],
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "expires_in",
        "active",
        "active_requirement",
        "user",
        "requirement_store_left",
        "last_mobile_version",
    ]);

    expect($response->getData()->data->active_requirement)->toHaveKeys([
        "Regional Marketing (RM)",
        "Regional Marketing Coordinator (RMC)",
        "Marketing District Manager (MDM)",
        "Assistant MDM",
        "Aplikator",
    ]);

    expect($response->getData()->data->user->has_store_count)->toEqual(30);
    expect($response->getData()->data->active)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("login as production environment RMC has 30 kios", function () {
    $personel = Personel::factory()->marketingRMC()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
        "password" =>  bcrypt("password")
    ]);

    $stores = Store::factory()->count(200)->create([
        "personel_id" => $personel->id
    ]);
    $response = $this->postJson("/api/auth/v2/login", [
        "login" => $user->email,
        "password" => "password",
        "is_mobile" => true,
        "device_id" => "1212",
        "latitude" => "-7.719638",
        "longitude" => "110.383942",
        "manufacture" => "Xiomei",
        "model" => "Poco-poco",
        "version_app" => "A10",
        "version_os" => "Android10",
        "set_environment_like_production" => true,
    ]);
    
    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "token",
        "expires_in",
        "active",
        "active_requirement",
        "user",
        "requirement_store_left",
        "last_mobile_version",
    ]);

    expect($response->getData()->data->active_requirement)->toHaveKeys([
        "Regional Marketing (RM)",
        "Regional Marketing Coordinator (RMC)",
        "Marketing District Manager (MDM)",
        "Assistant MDM",
        "Aplikator",
    ]);

    expect($response->getData()->data->user->has_store_count)->toEqual(200);
    expect($response->getData()->data->active)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("me as production environment RMC has 30 kios", function () {
    $personel = Personel::factory()->marketingRMC()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
        "password" =>  bcrypt("password")
    ]);

    $stores = Store::factory()->count(200)->create([
        "personel_id" => $personel->id
    ]);

    $response = actingAsMarketing(null,   $user->personel_id)->json("GET", "/api/auth/v2/me", [
        "set_environment_like_production" => true,
        "record_access" => [
            "latitude" => -7.888012140918093,
            "longitude" => 110.60661305196203,
            "is_mobile" => 1,
            "device_id" => "a04e",
            "model" => "SM-A042F",
            "manufacture" => "samsung",
            "version_os" => "14",
            "version_app" => "1.12.8",
        ],
    ]);

    $response->assertStatus(200);
    expect($response->getData()->data)->toHaveKeys([
        "expires_in",
        "active",
        "active_requirement",
        "user",
        "requirement_store_left",
        "last_mobile_version",
    ]);

    expect($response->getData()->data->active_requirement)->toHaveKeys([
        "Regional Marketing (RM)",
        "Regional Marketing Coordinator (RMC)",
        "Marketing District Manager (MDM)",
        "Assistant MDM",
        "Aplikator",
    ]);

    expect($response->getData()->data->user->has_store_count)->toEqual(200);
    expect($response->getData()->data->active)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

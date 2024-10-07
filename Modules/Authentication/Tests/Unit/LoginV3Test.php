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

    $response = $this->postJson("/api/auth/v3/login", [
        "login" => $user->email,
        "password" => "password",
    ]);

    $response->assertStatus(200);
});

test("login marketing for mobile", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = $this->postJson("/api/auth/v3/login", [
        "login" => $user->email,
        "password" => "password",
        "record_access" => [
            "device_id" => "1212",
            "latitude" => "-7.719638",
            "longitude" => "110.383942",
            "manufacture" => "Xiomei",
            "model" => "Poco-poco",
            "version_app" => "A10",
            "version_os" => "Android10",
        ],
    ]);

    $response->assertStatus(200);
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
});

test("can get me", function () {
    $personel = Personel::factory()->create();

    $response = actingAsSupportFrom($personel->id)->json("GET", "/api/auth/v3/me", [
        "record_access" => [
            "device_id" => "1212",
            "latitude" => "-7.719638",
            "longitude" => "110.383942",
            "manufacture" => "Xiomei",
            "model" => "Poco-poco",
            "version_app" => "A10",
            "version_os" => "Android10",
        ],
    ]);

    $user = DB::table('users')->where("personel_id", $personel->id)->first();
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    $response->assertStatus(200);
});

test("login as production", function () {
    $personel = Personel::factory()->create();
    $user = User::factory()->create([
        "password" => bcrypt("password"),
        "personel_id" => $personel->id,
    ]);

    $personel->load("position");

    $response = $this->postJson("/api/auth/v3/login", [
        "login" => $user->email,
        "password" => "password",
        "set_environment_like_production" => true,
        "record_access" => [
            "device_id" => "1212",
            "latitude" => "-7.719638",
            "longitude" => "110.383942",
            "manufacture" => "Xiomei",
            "model" => "Poco-poco",
            "version_app" => "A10",
            "version_os" => "Android10",
        ],
    ]);

    $response->assertStatus(200);
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeFalsy();
    expect($response->getData()->data->requirement_store_left)->toEqual(30);
});

test("login as production RM has 30 kios", function () {
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
    $response = $this->postJson("/api/auth/v3/login", [
        "login" => $user->email,
        "password" => "password",
        "set_environment_like_production" => true,
        "record_access" => [
            "device_id" => "1212",
            "latitude" => "-7.719638",
            "longitude" => "110.383942",
            "manufacture" => "Xiomei",
            "model" => "Poco-poco",
            "version_app" => "A10",
            "version_os" => "Android10",
        ],
    ]);

    $response->assertStatus(200);
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("login as production RMC has 30 kios", function () {
    $personel = Personel::factory()->marketingRMC()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
        "password" =>  bcrypt("password")
    ]);

    $stores = Store::factory()->count(200)->create([
        "personel_id" => $personel->id
    ]);
    $response = $this->postJson("/api/auth/v3/login", [
        "login" => $user->email,
        "password" => "password",
        "set_environment_like_production" => true,
        "record_access" => [
            "device_id" => "1212",
            "latitude" => "-7.719638",
            "longitude" => "110.383942",
            "manufacture" => "Xiomei",
            "model" => "Poco-poco",
            "version_app" => "A10",
            "version_os" => "Android10",
        ],
    ]);

    $response->assertStatus(200);
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("login as staging", function () {
    $personel = Personel::factory()->create();
    $user = User::factory()->create([
        "password" => bcrypt("password"),
        "personel_id" => $personel->id,
    ]);

    $personel->load("position");

    $response = $this->postJson("/api/auth/v3/login", [
        "login" => $user->email,
        "password" => "password",
        "set_environment_like_production" => false,
        "record_access" => [
            "device_id" => "1212",
            "latitude" => "-7.719638",
            "longitude" => "110.383942",
            "manufacture" => "Xiomei",
            "model" => "Poco-poco",
            "version_app" => "A10",
            "version_os" => "Android10",
        ],
    ]);

    $response->assertStatus(200);
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeTruthy();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("me as production", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = actingAsMarketing(null, $user->personel_id)->json("GET", "/api/auth/v3/me", [
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
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeFalsy();
    expect($response->getData()->data->requirement_store_left)->toEqual(30);
});

test("me as production, RM has 30 kios", function () {
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
    $response = actingAsMarketing(null, $user->personel_id)->json("GET", "/api/auth/v3/me", [
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
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("me as production, RMC has 30 kios", function () {
    $personel = Personel::factory()->marketingRMC()->create();
    $user = User::factory()->create([
        "personel_id" => $personel->id,
        "password" =>  bcrypt("password")
    ]);

    $stores = Store::factory()->count(200)->create([
        "personel_id" => $personel->id
    ]);
    $response = actingAsMarketing(null, $user->personel_id)->json("GET", "/api/auth/v3/me", [
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
    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();

    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeTrue();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

test("me as staging", function () {
    $user = User::factory()->create();
    $user->password = bcrypt("password");
    $user->save();

    $response = actingAsMarketing(null, $user->personel_id)->json("GET", "/api/auth/v3/me", [
        "set_environment_like_production" => false,
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

    $access_history = DB::table('user_access_histories')
        ->where("user_id", $user->id)
        ->first();
        
    expect($response->getData()->data)->toHaveKeys([
        "id",
        "name",
        "email",
        "username",
        "personel_id",
        "position",
        "photo",
        "last_version",
        "last_version_url",
        "requirement_store_left",
        "is_store_fulfilled",
        "unread_notif_marketing",
        "unread_notif_supervisor",
        "token",
    ]);
    expect($access_history)->toBeTruthy();
    expect($response->getData()->data->is_store_fulfilled)->toBeTruthy();
    expect($response->getData()->data->requirement_store_left)->toEqual(0);
});

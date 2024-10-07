<?php
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Modules\Address\Entities\AddressTemp;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Modules\KiosDealer\Entities\DealerTemp;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("dealer approval: new dealer from RM", function () {
    $dealer_temp = DealerTemp::factory()->create([
        "status" => "wait approval",
        "name" => "dealer-xxx-yyy-123",
    ]);

    DealerChangeHistory::create([
        "dealer_temp_id" => $dealer_temp->id,
        "submited_at" => now(),
        "submited_by" => $dealer_temp->personel_id,
        "confirmed_by" => $dealer_temp->personel_id,
        "confirmed_at" => now(),
        "approved_at" => null,
        "approved_by" => null,
    ]);

    $area = MarketingAreaDistrict::factory()->create();

    AddressTemp::factory()->createMany([
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer",
            "parent_id" => $dealer_temp->id,
        ],
        [
            "district_id" => $area->district_id,
            "city_id" => $area->city_id,
            "province_id" => $area->province_id,
            "type" => "dealer_owner",
            "parent_id" => $dealer_temp->id,
        ],
    ]);

    DealerFileTemp::factory()->createMany([
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "KTP",
            "data" => "xxx.jpd",
        ],
        [
            "dealer_id" => $dealer_temp->id,
            "file_type" => "SIM",
            "data" => "xxx.jpd",
        ],
    ]);

    $user = User::factory()->create();
    $token = JWTAuth::fromUser($user);

    $responses = Http::pool(fn(Pool $pool) => [
        $pool
            ->as("first")
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])
            ->put(env("APP_URL", "localhost") . "/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve"),
        // $pool
        //     ->as("second")
        //     ->withHeaders([
        //         'Authorization' => 'Bearer ' . $token,
        //     ])
        //     ->put(env("APP_URL", "localhost") . "/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve"),
    ]);

    // dd(
    //     $responses["first"]->json(),
    //     // $responses["second"]->ok()
    // );

    $this->assertTrue(true);

    // $client = new Client(['base_uri' => env("APP_URL", "localhost")]);

    // $promises = [
    //     'request_1' => $client->getAsync("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve"),
    //     'request_2' => $client->getAsync("/api/v2/dealer/dealer-temp/" . $dealer_temp->id . "/approve"),
    // ];

    // $responses = Promise\settle($promises)->wait();

    // expect($responses['request_1']['value']->getStatusCode())->toBe(200);

    // expect($responses['request_2']['value']->getStatusCode())->toBe(200);
});

<?php
use Mockery\MockInterface;
use Ladumor\OneSignal\OneSignal;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Facade;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Illuminate\Support\Facades\Notification;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\KiosDealer\Jobs\SubDealerTempNotificationJob;
use Modules\KiosDealer\Notifications\SubDealerTempNotification;

uses(Tests\TestCase::class, DatabaseTransactions::class);

/**
 * REVISION
 */
test("revision notif", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create();
    $personel = Personel::factory()->support()->create();

    Queue::fake();

    $response = actingAsSupport()->postJson("/api/v1/dealer/sub-dealer-temp-note", [
        "note" => "revisi test",
        "status" => "revised change",
        "personel_id" => $personel->id,
        "sub_dealer_temp_id" => $sub_dealer_temp->id,
    ]);

    Queue::assertPushed(SubDealerTempNotificationJob::class);

    $response->assertStatus(201);
});

test("revision notif job", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "revised change",
    ]);

    $user = User::factory()->create([
        "personel_id" => $sub_dealer_temp->personel_id,
    ]);

    $personel = Personel::factory()->support()->create();

    Notification::fake();

    (new SubDealerTempNotificationJob($sub_dealer_temp))->handle();

    Notification::assertSentTo(
        [$user], SubDealerTempNotification::class
    );
});

test("revision notif job one signal", function () {
    $sub_dealer_temp = SubDealerTemp::factory()->create([
        "status" => "revised change",
    ]);

    $user = User::factory()->create([
        "personel_id" => $sub_dealer_temp->personel_id,
    ]);
    $personel = Personel::factory()->support()->create();

    $textNotif = "pengajuan data $sub_dealer_temp->name Membutuhkan Revisi";
    $fields = [
        "include_player_ids" => [],
        "data" => [
            "subtitle" => "Pengajuan Sub Dealer Baru",
            "menu" => "Sub Dealer Temp",
            "data_id" => $sub_dealer_temp->id,
            "mobile_link" => "",
            "desktop_link" => "/marketing-staff/sub-dealer-detail/" . $sub_dealer_temp->id,
            "notification" => $textNotif,
            "is_supervisor" => false,
        ],
        "contents" => [
            "en" => $textNotif,
            "in" => $textNotif,
        ],
        "recipients" => 1,
    ];

    Facade::clearResolvedInstance(OneSignal::class); // Ensure OneSignal facade is clear
    OneSignal::shouldReceive('sendPush')
        ->once()
        ->withAnyArgs()
        ->andReturn(['id' => 'mock-notification-id']);

    $notif = OneSignal::sendPush($fields, $textNotif);
    $this->assertEquals('mock-notification-id', $notif['id']);
});

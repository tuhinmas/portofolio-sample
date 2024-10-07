<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Notification\Entities\Notification;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("mark all notif as read for payment due", function () {
    $notif_1 = Notification::factory()->paymentDueDirectSales()->create();
    $notif_2 = Notification::factory()->paymentDueDirectSales()->create([
        "data_id" => $notif_1->data_id,
        "personel_id" => $notif_1->personel_id,
        "data" => $notif_1->data,
        "notifiable_id" => $notif_1->notifiable_id,
    ]);
    $notif_3 = Notification::factory()->paymentDueDirectSales()->create([
        "data_id" => $notif_1->data_id,
        "personel_id" => $notif_1->personel_id,
        "data" => $notif_1->data,
        "notifiable_id" => $notif_1->notifiable_id,
    ]);

    $response = actingAsSupport()->json("GET", "/api/v1/notification/show/" . $notif_1->id, [
        "personel_id" => $notif_1->personel_id,
    ]);
    $response->assertStatus(200);

    $notif_1->refresh();
    $notif_2->refresh();
    $notif_3->refresh();
    expect($response->getData()->data)->toHaveKeys([
        "id",
        "type",
        "notifiable_type",
        "notifiable_id",
        "personel_id",
    ]);

    expect($notif_1->read_at)->not->toBeNull();
    expect($notif_2->read_at)->not->toBeNull();
    expect($notif_3->read_at)->not->toBeNull();
});

test("mark all notif as read for payment due, only one", function () {
    $notif_1 = Notification::factory()->paymentDueDirectSales()->create();

    $response = actingAsSupport()->json("GET", "/api/v1/notification/show/" . $notif_1->id, [
        "personel_id" => $notif_1->personel_id,
    ]);
    $response->assertStatus(200);

    $notif_1->refresh();
    expect($response->getData()->data)->toHaveKeys([
        "id",
        "type",
        "notifiable_type",
        "notifiable_id",
        "personel_id",
    ]);

    expect($notif_1->read_at)->not->toBeNull();

});

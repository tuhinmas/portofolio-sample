<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Facade;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Jobs\InvoiceMobileNotificationJob;
use Modules\Personel\Entities\Personel;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("marketing notification, fake notif", function () {
    $invoice = Invoice::factory()->create();
    $invoice->load([
        "salesOrder.dealer",
        "user.personel.position",
        "salesOrder.personel.user",
    ]);
    $user = User::factory()->create([
        "personel_id" => $invoice->salesOrder->personel_id,
    ]);

    Facade::clearResolvedInstance(OneSignal::class); // Ensure OneSignal facade is clear
    OneSignal::shouldReceive('sendPush')
        ->once()
        ->withAnyArgs()
        ->andReturn(['id' => 'mock-notification-id']);

    (new InvoiceMobileNotificationJob($invoice))->handle();

    $notification_text = "Direct sales  No. "
    . $invoice->salesOrder->order_number
    . ", toko "
    . $invoice->salesOrder->dealer->name
    . ", telah dikonfirmasi oleh "
    . $invoice->user?->personel?->name
    . ", "
    . $invoice->user?->personel?->position?->name;

    $mobile_link = "/DetailProformaDirectOrderPage";
    $desktop_link = "/marketing-staff/invoice-detail/detail/" . $invoice->id . "/invoice-detail";

    $fields = [
        "include_player_ids" => [],
        "data" => [
            "subtitle" => $notification_text,
            "menu" => "direct_sales",
            "data_id" => $invoice->id,
            "mobile_link" => $mobile_link,
            "desktop_link" => $desktop_link,
            "notification" => $notification_text,
            "is_supervisor" => false,
        ],
        "contents" => [
            "en" => $notification_text,
            "in" => $notification_text,
        ],
        "recipients" => 1,
    ];
});

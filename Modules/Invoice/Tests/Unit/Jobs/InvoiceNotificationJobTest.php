<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Jobs\InvoiceNotificationJob;
use Modules\Invoice\Notifications\InvoiceNotification;
use Modules\Personel\Entities\Personel;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("marketing notification, fake notif", function () {
    $invoice = Invoice::factory()->create();
    $invoice->load("salesOrder");
    $user = User::factory()->create([
        "personel_id" => $invoice->salesOrder->personel_id,
    ]);

    Notification::fake();

    (new InvoiceNotificationJob($invoice))->handle();

    Notification::assertSentTo(
        [$user], InvoiceNotification::class
    );
});

test("marketing notification, real notif", function () {
    $invoice = Invoice::factory()->create();
    $invoice->load([
        "salesOrder.dealer",
        "user.personel.position",
        "salesOrder.personel.user",
    ]);

    $user = User::factory()->create([
        "personel_id" => $invoice->salesOrder->personel_id,
    ]);

    (new InvoiceNotificationJob($invoice))->handle();

    $notification_text = "Direct sales  No. "
    . $invoice->salesOrder->order_number
    . ", toko "
    . $invoice->salesOrder->dealer->name
    . ", telah dikonfirmasi oleh "
    . $invoice->user?->personel?->name
    . ", "
    . $invoice->user?->personel?->position?->name;

    expect($user->notifications)->toHaveCount(1);
    expect($user->unreadNotifications)->toHaveCount(1);
    expect($user->unreadNotifications->first()->notification_marketing_group_id)->toEqual("1");
    expect($user->unreadNotifications->first()->notified_feature)->toEqual("direct_sales");
    expect($user->unreadNotifications->first()->notification_text)->toEqual($notification_text);
    expect($user->unreadNotifications->first()->mobile_link, )->toEqual("/DetailProformaDirectOrderPage");
    expect($user->unreadNotifications->first()->desktop_link)->toEqual("/marketing-staff/invoice-detail/detail/" . $invoice->id . "/invoice-detail");
    expect($user->unreadNotifications->first()->data_id)->toEqual($invoice->id);
    expect($user->unreadNotifications->first()->as_marketing)->toEqual(1);
});

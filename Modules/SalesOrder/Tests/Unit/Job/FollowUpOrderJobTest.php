<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Jobs\FollowUpOrderJob;
use Modules\SalesOrder\Actions\Order\UpdateFollowUpDaysOrderAction;
use Modules\SalesOrder\Entities\SalesOrder;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("follow days: follow_up mode, no order before", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();
    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'follow_up',
    ]);
    (new FollowUpOrderJob($sales_order))->handle(new UpdateFollowUpDaysOrderAction);
    $sales_order->refresh();

    expect($sales_order->follow_up_days)->toEqual(50);
    expect($sales_order->follow_up_days_updated)->toBeTruthy();
});

test("follow days: follow_up mode, exist indirect order before", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
        "date" => now()->subDays(79),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'follow_up',
    ]);
    (new FollowUpOrderJob($sales_order))->handle(new UpdateFollowUpDaysOrderAction);
    $sales_order->refresh();

    expect($sales_order->follow_up_days)->toEqual(79);
    expect($sales_order->follow_up_days_updated)->toBeTruthy();
});

test("follow days: follow_up mode, exist direct order before", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();
    $order_1 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_1->id,
        "created_at" => now()->subDays(69),
    ]);

    $order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'follow_up',
        "follow_up_days" => 0,
    ]);
    (new FollowUpOrderJob($order_2))->handle(new UpdateFollowUpDaysOrderAction);
    $order_2->refresh();

    expect($order_2->follow_up_days)->toEqual(69);
    expect($order_2->follow_up_days_updated)->toBeTruthy();
});

test("follow days: follow_up mode, exist direct order before multiple order same day", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();

    /* FIRST ORDER */
    $order_1 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_1->id,
        "created_at" => now()->subDays(69),
    ]);

    /* SECOND ORDER */
    $order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'follow_up',
        "follow_up_days" => 0,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_2->id,
        "created_at" => now(),
    ]);
    (new FollowUpOrderJob($order_2))->handle(new UpdateFollowUpDaysOrderAction);

    $order_3 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'follow_up',
        "follow_up_days" => 0,
    ]);

    (new FollowUpOrderJob($order_3))->handle(new UpdateFollowUpDaysOrderAction);
    $order_2->refresh();
    $order_3->refresh();

    expect($order_2->follow_up_days)->toEqual(69);
    expect($order_2->follow_up_days_updated)->toBeTruthy();
    expect($order_3->follow_up_days)->toEqual(69);
    expect($order_3->follow_up_days_updated)->toBeTruthy();
});

test("follow days: follow_up mode, exist direct order before multiple order same day, by marketing exist", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();

    /* FIRST ORDER */
    $order_1 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_1->id,
        "created_at" => now()->subDays(69),
    ]);

    /* SECOND ORDER */
    $order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'follow_up',
        "follow_up_days" => 100,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_2->id,
        "created_at" => now(),
    ]);
    (new FollowUpOrderJob($order_2))->handle(new UpdateFollowUpDaysOrderAction);

    /* THIRD ORDER */
    $order_3 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
        "follow_up_days" => 0,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_3->id,
        "created_at" => now(),
    ]);
    (new FollowUpOrderJob($order_3))->handle(new UpdateFollowUpDaysOrderAction);

    /* FOURTH ORDER */
    $order_4 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'follow_up',
        "follow_up_days" => 0,
    ]);

    (new FollowUpOrderJob($order_4))->handle(new UpdateFollowUpDaysOrderAction);
    $order_2->refresh();
    $order_3->refresh();
    $order_4->refresh();

    expect($order_2->follow_up_days)->toEqual(69);
    expect($order_2->follow_up_days_updated)->toBeTruthy();
    expect($order_3->follow_up_days)->toEqual(0);
    expect($order_3->follow_up_days_updated)->toBeNull();
    expect($order_4->follow_up_days)->toEqual(69);
    expect($order_4->follow_up_days_updated)->toBeTruthy();
});


/**
 * OFFICE
 */
test("follow days: office mode, no order before", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();
    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'office',
    ]);
    (new FollowUpOrderJob($sales_order))->handle(new UpdateFollowUpDaysOrderAction);
    $sales_order->refresh();

    expect($sales_order->follow_up_days)->toEqual(50);
    expect($sales_order->follow_up_days_updated)->toBeTruthy();
});

test("follow days: office mode, exist indirect order before", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();
    SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "2",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
        "date" => now()->subDays(79),
    ]);

    $sales_order = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'office',
    ]);
    (new FollowUpOrderJob($sales_order))->handle(new UpdateFollowUpDaysOrderAction);
    $sales_order->refresh();

    expect($sales_order->follow_up_days)->toEqual(79);
    expect($sales_order->follow_up_days_updated)->toBeTruthy();
});

test("follow days: office mode, exist direct order before", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();
    $order_1 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_1->id,
        "created_at" => now()->subDays(69),
    ]);

    $order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'office',
        "follow_up_days" => 0,
    ]);
    (new FollowUpOrderJob($order_2))->handle(new UpdateFollowUpDaysOrderAction);
    $order_2->refresh();

    expect($order_2->follow_up_days)->toEqual(69);
    expect($order_2->follow_up_days_updated)->toBeTruthy();
});

test("follow days: office mode, exist direct order before multiple order same day", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();

    /* FIRST ORDER */
    $order_1 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_1->id,
        "created_at" => now()->subDays(69),
    ]);

    /* SECOND ORDER */
    $order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'office',
        "follow_up_days" => 0,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_2->id,
        "created_at" => now(),
    ]);
    (new FollowUpOrderJob($order_2))->handle(new UpdateFollowUpDaysOrderAction);

    $order_3 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'office',
        "follow_up_days" => 0,
    ]);

    (new FollowUpOrderJob($order_3))->handle(new UpdateFollowUpDaysOrderAction);
    $order_2->refresh();
    $order_3->refresh();

    expect($order_2->follow_up_days)->toEqual(69);
    expect($order_2->follow_up_days_updated)->toBeTruthy();
    expect($order_3->follow_up_days)->toEqual(69);
    expect($order_3->follow_up_days_updated)->toBeTruthy();
});

test("follow days: office mode, exist direct order before multiple order same day, by marketing exist", function () {
    $dealer = Dealer::factory()->create([
        "created_at" => now()->subDay(50),
    ]);
    $personel = Personel::factory()->create();

    /* FIRST ORDER */
    $order_1 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_1->id,
        "created_at" => now()->subDays(69),
    ]);

    /* SECOND ORDER */
    $order_2 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'office',
        "follow_up_days" => 100,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_2->id,
        "created_at" => now(),
    ]);
    (new FollowUpOrderJob($order_2))->handle(new UpdateFollowUpDaysOrderAction);

    /* THIRD ORDER */
    $order_3 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "confirmed",
        "counter_id" => $personel->id,
        "sales_mode" => 'marketing',
        "follow_up_days" => 0,
    ]);

    Invoice::factory()->create([
        "sales_order_id" => $order_3->id,
        "created_at" => now(),
    ]);
    (new FollowUpOrderJob($order_3))->handle(new UpdateFollowUpDaysOrderAction);

    /* FOURTH ORDER */
    $order_4 = SalesOrder::factory()->create([
        "store_id" => $dealer->id,
        "type" => "1",
        "model" => "1",
        "status" => "submited",
        "counter_id" => $personel->id,
        "sales_mode" => 'office',
        "follow_up_days" => 0,
    ]);

    (new FollowUpOrderJob($order_4))->handle(new UpdateFollowUpDaysOrderAction);
    $order_2->refresh();
    $order_3->refresh();
    $order_4->refresh();

    expect($order_2->follow_up_days)->toEqual(69);
    expect($order_2->follow_up_days_updated)->toBeTruthy();
    expect($order_3->follow_up_days)->toEqual(0);
    expect($order_3->follow_up_days_updated)->toBeNull();
    expect($order_4->follow_up_days)->toEqual(69);
    expect($order_4->follow_up_days_updated)->toBeTruthy();
});

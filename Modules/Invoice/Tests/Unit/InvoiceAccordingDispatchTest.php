<?php

use Faker\Factory as Faker;
use Modules\Invoice\Entities\Invoice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\DistributionChannel\Entities\DispatchOrder;

uses(Tests\TestCase::class, DatabaseTransactions::class);

test("can not delete proforma if has active dispatch order", function(){
    $invoice = Invoice::factory()->create();

    DispatchOrder::factory()->create([
        "is_active" => true,
        "invoice_id" => $invoice->id
    ]);

    $response = actingAsMarketing()->deleteJson("/api/v1/invoice/". $invoice->id);
    $response->assertStatus(422);
});

test("can delete proforma if has active dispatch order", function(){
    $invoice = Invoice::factory()->create();

    DispatchOrder::factory()->create([
        "is_active" => true,
        "invoice_id" => $invoice->id
    ]);

    $response = actingAsMarketing()->deleteJson("/api/v1/invoice/". $invoice->id);
    $response->assertStatus(422);
});

test("can update proforma if has active dispatch order", function(){
    $invoice = Invoice::factory()->create();

    DispatchOrder::factory()->create([
        "is_active" => true,
        "invoice_id" => $invoice->id
    ]);

    $response = actingAsMarketing()->deleteJson("/api/v1/invoice/". $invoice->id);
    $response->assertStatus(422);
});
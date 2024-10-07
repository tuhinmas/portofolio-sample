<?php

use Modules\PickupOrder\Entities\PickupLoadHistory;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;

uses(Tests\TestCase::class, DatabaseTransactions::class);


test("delete: pickup order detail from dispatch order list", function () {
    $pickup_order_detail_file = PickupOrderDetailFile::factory()->create();
    $response = actingAsSupport()->putJson("/api/v1/pickup-order/pickup-order-detail-file/".$pickup_order_detail_file->id);
    $response->assertStatus(200);
});
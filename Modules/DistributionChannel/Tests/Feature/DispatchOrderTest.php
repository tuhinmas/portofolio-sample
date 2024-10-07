<?php

namespace Modules\DistributionChannel\Tests\Feature;

use Tests\TestCase;
use Modules\Invoice\Entities\Invoice;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\Warehouse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\DistributionChannel\Entities\DispatchOrder;

class DispatchOrderTest extends TestCase
{
    public function test_dispatch_order_list_without_login(){
        $response = $this->getJson("/api/v1/distribution-channel/dispatch-order");
        $response->assertStatus(401);
    }

    public function test_dispatch_order_list_support(){
        $user = User::where("name", "support")->first();
        $response = $this->actingAs($user)->getJson("/api/v1/distribution-channel/dispatch-order");
        $response->assertStatus(200);
        $response->assertJson([
            "response_code" => "00"
        ]);
    }

    public function test_detail_dispatch_order_data_include(){
        $user = User::where("name", "support")->first();
        $dispatch_order_detail = DispatchOrder::first();
        $response = $this->actingAs($user)->getJson("/api/v1/distribution-channel/dispatch-order/".$dispatch_order_detail->id, [
            "include" => "driver,warehouse,dispatchOrderDetail,invoice.salesOrder.dealer.adress_detail"
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            "response_code" => "00"
        ]);
    }

    public function test_create_dispatch_order(){
        $user = User::where("name", "support")->first();
        $response = $this->actingAs($user)->postJson("/api/v1/distribution-channel/dispatch-order/", [
            "invoice_id" => Invoice::inRandomOrder()->first()->id,
            "id_armada" => null,
            "id_warehouse" => Warehouse::inRandomOrder()->first()->first()->id,
            "type_driver" => "external",
            "transportation_type" => "truck",
            "armada_identity_number" => "AB 123 CV",
            "driver_name" => "Budi",
            "driver_phone_number" => "13109108212",
            "date_delivery" => "2022-04-25",
            "dispatch_order_weight" => 1000,
        ]);
        $response->assertStatus(201);
        $response->assertJson([
            "response_code" => "00"
        ]);
    }

    public function test_update_dispatch_order(){
        $user = User::where("name", "support")->first();
        $dispatch_order = DispatchOrder::inRandomOrder()->first();
        $response = $this->actingAs($user)->putJson("/api/v1/distribution-channel/dispatch-order/".$dispatch_order->id, [
            "invoice_id" => Invoice::inRandomOrder()->first()->id,
            "id_armada" => null,
            "id_warehouse" => Warehouse::inRandomOrder()->first()->first()->id,
            "type_driver" => "external",
            "transportation_type" => "truck",
            "armada_identity_number" => "AB 123 CV",
            "driver_name" => "Budi",
            "driver_phone_number" => "13109108212",
            "date_delivery" => "2022-04-25",
            "dispatch_order_weight" => 1000,
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            "response_code" => "00"
        ]);
    }
}

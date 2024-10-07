<?php

namespace Modules\DistributionChannel\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Authentication\Entities\User;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Tests\TestCase;

class DispatchOrderDetailTest extends TestCase
{
    public function test_dispatch_order_detail_without_login()
    {
        $response = $this->getJson("/api/v1/distribution-channel/dispatch-order-detail");
        $response->assertStatus(401);
    }

    public function test_dispatch_order_detail_list_support()
    {
        $user = User::where("name", "support")->first();
        $response = $this->actingAs($user)->getJson("/api/v1/distribution-channel/dispatch-order-detail");
        $response->assertStatus(200);
        $response->assertJson([
            "response_code" => "00",
        ]);
    }

    public function test_dispatch_order_detail_including_quantity_unit_received()
    {
        $user = User::where("name", "support")->first();
        $dispatch_order = DispatchOrder::query()
            ->whereHas("invoice")
            ->whereHas("dispatchOrderDetail")
            ->first();

        $response = $this->actingAs($user)->getJson("/api/v1/distribution-channel/dispatch-order-detail-with-received-detail", [
            "dispatch_order_id" => $dispatch_order->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            "response_code" => "00",
        ]);
        $response->assertJson(fn(AssertableJson $json) =>
            $json
                ->hasAll([
                    "response_code",
                    "response_message",
                    "data",
                ])
        );
        $response->assertJsonStructure([
            "data" => [
                "*" => [
                    "quantity_unit_received",
                    "quantity_package_received",
                    "quantity_unit_loaded",
                    "quantity_package_loaded",
                    "order_quantity_unit",
                    "order_quantity_package",
                    "package_on_purchase",
                    "product",
                ],
            ],
        ]);
    }

    public function test_store_dispatch_order_detail()
    {
        $user = User::where("name", "support")->first();
        $dispatch_order = DispatchOrder::query()
            ->with([
                "invoice" => function ($QQQ) {
                    return $QQQ->with([
                        "salesOrder",
                    ]);
                },
            ])
            ->whereHas("invoice", function ($QQQ) {
                return $QQQ->whereHas("salesOrder");
            })
            ->whereHas("dispatchOrderDetail")
            ->first();

        $response = $this->actingAs($user)->postJson("/api/v1/distribution-channel/dispatch-order-detail", [
            "id_dispatch_order" => $dispatch_order->id,
            "id_product" => $dispatch_order->invoice->salesOrder->sales_order_detail[0]->product_id,
            "quantity_packet_to_send" => $dispatch_order->invoice->salesOrder->sales_order_detail[0]->product->package ? $dispatch_order->invoice->salesOrder->sales_order_detail[0]->product->package->packaging : 1,
            "quantity_unit" => $dispatch_order->invoice->salesOrder->sales_order_detail[0]->quantity,
            "package_weight" => $dispatch_order->invoice->salesOrder->sales_order_detail[0]->product->package ? $dispatch_order->invoice->salesOrder->sales_order_detail[0]->product->package->weight : null,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            "response_code" => "00",
        ]);
    }

    public function test_delete_dispatch_order_detail()
    {
        $user = User::where("name", "support")->first();
        $dispatch_order = DispatchOrderDetail::query()
            ->first();

        $response = $this->actingAs($user)->deleteJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order->id);

        $response->assertStatus(200);
        $response->assertJson([
            "response_code" => "00",
        ]);
    }

    public function test_update_dispatch_order_detail()
    {
        $user = User::where("name", "support")->first();
        $dispatch_order = DispatchOrder::query()
            ->first();

        $dispatch_order_detail = DispatchOrderDetail::query()
            ->with([
                "dispatchOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice" => function ($QQQ) {
                            return $QQQ->with([
                                "salesOrder",
                            ]);
                        },
                    ]);
                },
            ])
            ->whereHas("dispatchOrder", function ($QQQ) {
                return $QQQ->whereHas("invoice", function ($QQQ) {
                    return $QQQ->whereHas("salesOrder");
                });
            })
            ->first();

        $response = $this->actingAs($user)->putJson("/api/v1/distribution-channel/dispatch-order-detail/" . $dispatch_order_detail->id, [
            "id_dispatch_order" => $dispatch_order->id,
            "id_product" => $dispatch_order_detail->dispatchOrder->invoice->salesOrder->sales_order_detail[0]->product_id,
            "quantity_packet_to_send" => $dispatch_order_detail->dispatchOrder->invoice->salesOrder->sales_order_detail[0]->product->package ? $dispatch_order_detail->dispatchOrder->invoice->salesOrder->sales_order_detail[0]->product->package->packaging : 1,
            "quantity_unit" => $dispatch_order_detail->dispatchOrder->invoice->salesOrder->sales_order_detail[0]->quantity,
            "package_weight" => $dispatch_order_detail->dispatchOrder->invoice->salesOrder->sales_order_detail[0]->product->package ? $dispatch_order_detail->dispatchOrder->invoice->salesOrder->sales_order_detail[0]->product->package->weight : null,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            "response_code" => "00",
        ]);
    }
}

<?php

namespace Modules\ReceivingGood\Database\factories;

use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

class ReceivingGoodDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\ReceivingGood\Entities\ReceivingGoodDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $receiving_good = ReceivingGood::factory()->create();
        $dispatch_order = DB::table('discpatch_order as dis')
            ->join("delivery_orders  as dor", "dor.dispatch_order_id", "dis.id")
            ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
            ->where("rg.id", $receiving_good->id)
            ->where("dor.status", "send")
            ->where("dis.is_active", true)
            ->where("rg.delivery_status", "2")
            ->select("dis.*")
            ->first();

        $dispatch_order_detail = DispatchOrderDetail::factory()->create([
            "id_dispatch_order" => $dispatch_order->id,
        ]);

        // dd([
        //     $dispatch_order,
        // ]);

        return [
            "product_id" => $dispatch_order_detail->id_product,
            "receiving_good_id" => $receiving_good->id,
            "user_id" => User::factory()->create()->id,
            "quantity" => $dispatch_order_detail->quantity_unit,
            "quantity_package" => $dispatch_order_detail->quantity_packet_to_send,
            "status" => "delivered",
            "note" => null,
        ];
    }
}

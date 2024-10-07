<?php

namespace Modules\PickupOrder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PickupOrder\Entities\PickupOrderDetail;

class PickupOrderDetailFileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\PickupOrder\Entities\PickupOrderDetailFile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $pickup_order_details = PickupOrderDetail::factory()->create([
            "pickup_type" => "load",
        ]);
        return [
            "pickup_order_detail_id" => $pickup_order_details->id,
            "type" => "load",
            "attachment" => "public/pickup-order/pickup-order-detail/file/staging/d0c61ce5-82fb-455b-afdb-83ba41b22d8f_665e7f3188345.png",
        ];
    }
}

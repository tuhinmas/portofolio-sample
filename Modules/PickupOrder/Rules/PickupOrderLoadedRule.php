<?php

namespace Modules\PickupOrder\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\PickupOrder\Entities\PickupOrder;

class PickupOrderLoadedRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($pickup_order_id)
    {
        $this->pickup_order_id = $pickup_order_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $pickup_order = PickupOrder::query()
            ->with([
                "armada",
                "warehouse.porter",
                "pickupOrderFileMandatories",
                "pickupOrderDetails" => function ($QQQ) {
                    return $QQQ->with([
                        "product",
                        "pickupOrderDetailFiles",
                    ]);
                },
                "pickupOrderDispatch" => function ($QQQ) {
                    return $QQQ->with([
                        "pickupDispatchAble" => function ($QQQ) {
                            return $QQQ->with([
                                "dispatchDetail",
                                "addressDelivery" => function ($QQQ) {
                                    return $QQQ->with([
                                        "district",
                                        "city",
                                        "province",
                                    ]);
                                },
                            ]);
                        },

                    ]);
                },
                "pickupOrderDetailUnloads" => function ($QQQ) {
                    return $QQQ->with([
                        "product",
                        "pickupOrderDetailFiles",
                    ]);
                },
            ])
            ->findOrFail($this->pickup_order_id["pickup_order"]);

        $is_passed = true;
        if ($value == "canceled" || $value == "failed") {
            switch (true) {
                case !in_array(auth()->user()->personel_id, $pickup_order->warehouse->porter->pluck("personel_id")->toArray()):
                    $this->messages = "Anda bukan porter gudang dari pickup order bersangkutan";
                    $is_passed = false;
                    break;

                default:
                    break;
            }
            return $is_passed;
        } else {
            switch (true) {
                case !in_array(auth()->user()->personel_id, $pickup_order->warehouse->porter->pluck("personel_id")->toArray()):
                    $this->messages = "Anda bukan porter gudang dari pickup order bersangkutan";
                    $is_passed = false;
                    break;

                case $pickup_order->driver_id && !$pickup_order->armada:
                    $this->messages = "Armada sudah tidak tersedia";
                    $is_passed = false;
                    break;

                case $pickup_order->pickupOrderFileMandatories->count() < count(mandatory_captions()):
                    $this->messages = "Lampiran pelengkap kurang";
                    $is_passed = false;
                    break;

                case $pickup_order->pickupOrderDetails->count() <= 0:
                    $this->messages = "Produk pickup tidak ada";
                    $is_passed = false;
                    break;

                case $pickup_order->pickupOrderDetails->filter(fn($pickup_detail) => $pickup_detail->is_loaded)->count() < $pickup_order->pickupOrderDetails->count() || $pickup_order->pickupOrderDetails->filter(fn($pickup_detail) => $pickup_detail->quantity_actual_load >= $pickup_detail->quantity_unit_load)->count() < $pickup_order->pickupOrderDetails->count():
                    $this->messages = "Produk pickup belum dimuat semua";
                    $is_passed = false;
                    break;

                case $pickup_order->pickupOrderDetails->pluck("pickupOrderDetailFiles")->flatten()->count() < $pickup_order->pickupOrderDetails->count():
                    $this->messages = "Lampiran foto produk pickup belum lengkap";
                    $is_passed = false;
                    break;

                case $pickup_order->pickupOrderDispatch->count() <= 0:
                    $this->messages = "Pickup tidak memiliki dispatch";
                    $is_passed = false;
                    break;

                case $pickup_order->pickupOrderDetailUnloads->pluck("pickupOrderDetailFiles")->flatten()->count() < $pickup_order->pickupOrderDetailUnloads->count():
                    $this->messages = "Lampiran foto penurunan produk belum lengkap";
                    $is_passed = false;
                    break;

                case $pickup_order->pickupOrderDetailUnloads->filter(fn($pickup_detail) => !$pickup_detail->is_loaded)->count() < $pickup_order->pickupOrderDetailUnloads->count() || $pickup_order->pickupOrderDetailUnloads->filter(fn($pickup_detail) => $pickup_detail->quantity_actual_load == 0)->count() < $pickup_order->pickupOrderDetailUnloads->count():
                    $this->messages = "Produk pickup revisi belum diturunkan semua";
                    $is_passed = false;
                    break;

                case $value == "checked"
                    && ($pickup_order->pickupOrderDetails->filter(fn($pickup_detail) => $pickup_detail->is_checked)->count() < $pickup_order->pickupOrderDetails->count()
                        || $pickup_order->pickupOrderDetails->filter(fn($pickup_detail) => $pickup_detail->quantity_actual_checked == $pickup_detail->quantity_unit_load)->count() < $pickup_order->pickupOrderDetails->count()):
                    $this->messages = "Produk belum dicek semua";
                    $is_passed = false;
                    break;
            }
        }

        return $is_passed;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->messages;
    }
}

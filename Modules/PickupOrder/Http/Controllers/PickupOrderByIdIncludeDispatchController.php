<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PromotionGood\Entities\DispatchPromotion;

class PickupOrderByIdIncludeDispatchController extends Controller
{
    use ResponseHandler;

    public function __construct(protected PickupOrder $pickup_order)
    {}

    public function __invoke(Request $request, $pickup_order_id)
    {
        try {
            $pickup_order = $this->pickup_order->query()
                ->with([
                    "armada",
                    "warehouse.porter",
                    "pickupOrderFiles",
                    "pickupOrderDetails" => function ($QQQ) {
                        return $QQQ->with([
                            "product",
                            "pickupOrderDetailFiles",
                        ]);
                    },
                    "pickupOrderDetailUnloads" => function ($QQQ) {
                        return $QQQ->with([
                            "product",
                            "pickupOrderDetailFiles",
                        ]);
                    },
                    "pickupOrderDispatch" => function ($QQQ) {
                        return $QQQ->with([
                            "pickupDispatchAble" => function ($QQQ) {
                                return $QQQ->with([
                                    "deliveryOrder",
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
                    "pickupUnloadHistories" => function ($QQQ) {
                        return $QQQ->with([
                            "pickupDispatchAble" => function ($QQQ) {
                                return $QQQ->with([
                                    "dispatchDetail",
                                ]);
                            },
                            "createdBy" => function ($QQQ) {
                                return $QQQ->with([
                                    "position",
                                ]);
                            },
                        ]);
                    },
                ])
                ->findOrFail($pickup_order_id);

            if (!in_array(auth()->user()->personel_id, $pickup_order->warehouse->porter->pluck("personel_id")->toArray())) {
                return $this->response("04", "invalid data send", [
                    "message" => [
                        "Anda bukan porter gudang dari pickup order bersangkutan",
                    ],
                ], 422);
            }
            $pickup_order["dispatch_list"] = $pickup_order->pickupOrderDispatch
                ->map(function ($dispatch) {
                    $detail = [];
                    $load_weight = $dispatch->pickupDispatchAble->deliveryOrder ? $dispatch->pickupDispatchAble->dispatchDetail->sum("package_weight") : $dispatch->pickupDispatchAble->dispatchDetail->sum("planned_package_weight");
                    $detail["id"] = $dispatch->id;
                    $detail["dispatch_id"] = $dispatch->dispatch_id;
                    $detail["dispatch_type"] = $dispatch->dispatch_type;
                    $detail["dispatch_number"] = $dispatch->pickupDispatchAble->dispatch_order_number;
                    $detail["delvery_order_number"] = $dispatch->pickupDispatchAble->deliveryOrder?->delivery_order_number;
                    $detail["delivery_address"] = self::deliveryAddressMapping($dispatch->pickupDispatchAble->addressDelivery);
                    $detail["load_weight"] = (string) $load_weight;
                    $detail["item_count"] = $dispatch->pickupDispatchAble->dispatchDetail->count();
                    $detail["status"] = $dispatch->pickupDispatchAble->status;

                    if ($dispatch->pickupDispatchAble instanceof DispatchOrder) {
                        $dispatch->pickupDispatchAble->load("invoice");
                        $detail["proforma_number"] = $dispatch->pickupDispatchAble->invoice?->invoice;
                        $detail["customer"] = [
                            "id" => $dispatch->pickupDispatchAble->invoice?->dealer?->dealer_id,
                            "name" => $dispatch->pickupDispatchAble->invoice?->dealer?->name,
                            "owner" => $dispatch->pickupDispatchAble->invoice?->dealer?->owner,
                        ];

                    } else if ($dispatch->pickupDispatchAble instanceof DispatchPromotion) {
                        $dispatch->pickupDispatchAble->loadMissing("promotionGoodRequest.event.dealer");
                        $dispatch->pickupDispatchAble->loadMissing("promotionGoodRequest.event.subDealer");
                        $detail["proforma_number"] = null;
                        $customer = null;
                        switch (true) {
                            case $dispatch->pickupDispatchAble->promotionGoodRequest:
                                switch (true) {
                                    case $dispatch->pickupDispatchAble->promotionGoodRequest->event:
                                        $customer = [
                                            "id" => match (true) {
                                                !empty($dispatch->pickupDispatchAble->promotionGoodRequest->event->dealer_id) => $dispatch->pickupDispatchAble->promotionGoodRequest?->event?->dealer?->dealer_id,
                                                !empty($dispatch->pickupDispatchAble->promotionGoodRequest->event->sub_dealer_id) => $dispatch->pickupDispatchAble->promotionGoodRequest?->event?->subDealer?->sub_dealer_id,
                                                default => null,
                                            },
                                            "name" => match (true) {
                                                !empty($dispatch->pickupDispatchAble->promotionGoodRequest->event->dealer_id) => $dispatch->pickupDispatchAble->promotionGoodRequest->event->dealer->name,
                                                !empty($dispatch->pickupDispatchAble->promotionGoodRequest->event->sub_dealer_id) => $dispatch->pickupDispatchAble->promotionGoodRequest->event->subDealer->name,
                                                default => null,
                                            },
                                            "owner" => match (true) {
                                                !empty($dispatch->pickupDispatchAble->promotionGoodRequest->event->dealer_id) => $dispatch->pickupDispatchAble->promotionGoodRequest?->event?->dealer?->owner,
                                                !empty($dispatch->pickupDispatchAble->promotionGoodRequest->event->sub_dealer_id) => $dispatch->pickupDispatchAble->promotionGoodRequest?->event?->subDealer?->owner,
                                                default => null,
                                            },
                                        ];
                                        break;

                                    default:
                                        break;
                                }
                                break;

                            default:
                                break;
                        }

                        $detail["customer"] = $customer;
                    }

                    return $detail;
                });

            $product_dispatch = $pickup_order->pickupOrderDispatch
                ->pluck("pickupDispatchAble")
                ->flatten()
                ->pluck("dispatchDetail")
                ->flatten();

            $product_direct = $product_dispatch
                ->filter(fn($dispatch_detail) => $dispatch_detail instanceof DispatchOrderDetail);

            $pickup_from_direct = $pickup_order
                ->pickupOrderDetails
                ->filter(function ($pickup_detail) use ($product_direct) {
                    return in_array($pickup_detail->product_id, $product_direct->pluck("id_product")->toArray());
                })
                ->filter(fn($pickup_detail) => $pickup_detail->detail_type == "dispatch_order")
                ->values();

            $pickup_from_promotion = $pickup_order
                ->pickupOrderDetails
                ->filter(fn($pickup_detail) => $pickup_detail->detail_type == "dispatch_promotion")
                ->values();

            /**
             * Alert checked pickup
             */
            $alert_direct = null;
            switch ($pickup_order->status) {

                /* pickup has loaded and does not checked */
                case 'loaded':
                    $alert_direct = match (true) {
                        $pickup_from_direct
                            ->filter(function ($direct) {
                                return $direct->is_checked && $direct->quantity_actual_checked == $direct->quantity_unit_load;
                            })
                            ->count() < $pickup_from_direct->count() => "Produk belum dicek",
                        default => "Produk sudah dicek semua",
                    };
                    break;

                default:
                    $alert_direct = match (true) {

                        /* incomplete attacment and load */
                        $pickup_from_direct
                            ->filter(function ($direct) {
                                return $direct->pickupOrderDetailFiles->count() > 0;
                            })
                            ->count() < $pickup_from_direct->count()
                        &&

                        $pickup_from_direct
                            ->filter(function ($direct) {
                                return $direct->is_loaded && $direct->quantity_actual_load >= $direct->quantity_unit_load;
                            })
                            ->count() < $pickup_from_direct->count() => "Muatan dan foto belum lengkap",

                        /* incomplete attacment */
                        $pickup_from_direct
                            ->filter(function ($direct) {
                                return $direct->pickupOrderDetailFiles->count() > 0;
                            })
                            ->count() < $pickup_from_direct->count() => "Foto belum lengkap",

                        /* incomplete load */
                        $pickup_from_direct
                            ->filter(function ($direct) {
                                return $direct->is_loaded && $direct->quantity_actual_load >= $direct->quantity_unit_load;
                            })
                            ->count() < $pickup_from_direct->count() => "Muatan belum lengkap",

                        default => "Sudah dimuat seluruhnya",
                    };
                    break;
            }

            /**
             * Alert checked pickup
             */
            $alert_promotion = null;
            switch ($pickup_order->status) {

                /* pickup has loaded and does not checked */
                case 'loaded':
                    $alert_promotion = match (true) {
                        $pickup_from_promotion
                            ->filter(function ($direct) {
                                return $direct->is_checked && $direct->quantity_actual_checked == $direct->quantity_unit_load;
                            })
                            ->count() < $pickup_from_promotion->count() => "Produk belum dicek",
                        default => "Produk sudah dicek semua",
                    };
                    break;

                default:
                    $alert_promotion = match (true) {

                        /* incomplete attacment and load */
                        $pickup_from_promotion
                            ->filter(function ($direct) {
                                return $direct->pickupOrderDetailFiles->count() > 0;
                            })
                            ->count() < $pickup_from_promotion->count()
                        &&

                        $pickup_from_promotion
                            ->filter(function ($direct) {
                                return $direct->is_loaded && $direct->quantity_actual_load >= $direct->quantity_unit_load;
                            })
                            ->count() < $pickup_from_promotion->count() => "Muatan dan foto belum lengkap",

                        /* incomplete attacment */
                        $pickup_from_promotion
                            ->filter(function ($direct) {
                                return $direct->pickupOrderDetailFiles->count() > 0;
                            })
                            ->count() < $pickup_from_promotion->count() => "Foto belum lengkap",

                        /* incomplete load */
                        $pickup_from_promotion
                            ->filter(function ($direct) {
                                return $direct->is_loaded && $direct->quantity_actual_load >= $direct->quantity_unit_load;
                            })
                            ->count() < $pickup_from_promotion->count() => "Muatan belum lengkap",

                        default => "Sudah dimuat seluruhnya",
                    };
                    break;
            }

            $pickup_order["loading_list"] = [
                "product_direct" => [
                    "products" => $pickup_from_direct,
                    "item_count" => $pickup_from_direct->count(),
                    "alert" => $alert_direct,
                ],

                "product_promotion" => [
                    "products" => $pickup_from_promotion,
                    "item_count" => $pickup_from_promotion->count(),
                    "alert" => $alert_promotion,
                ],
            ];

            /**
             * --------------------------------------------
             *  RELEASE DISPATCHES
             * --------------------------------
             */
            $pickup_order["release_detail_list"] = null;
            if ($pickup_order->pickupUnloadHistories->count() > 0) {

                $product_dispatch = $pickup_order
                    ->pickupUnloadHistories
                    ->pluck("pickupDispatchAble")
                    ->flatten()
                    ->pluck("dispatchDetail")
                    ->flatten();

                $product_direct = $product_dispatch
                    ->filter(fn($dispatch_detail) => $dispatch_detail instanceof DispatchOrderDetail);

                $pickup_from_direct = $pickup_order
                    ->pickupOrderDetailUnloads
                    ->filter(function ($pickup_detail) use ($product_direct) {
                        return in_array($pickup_detail->product_id, $product_direct->pluck("id_product")->toArray());
                    })
                    ->filter(fn($pickup_detail) => $pickup_detail->detail_type == "dispatch_order")
                    ->values();

                $pickup_from_direct = $pickup_order
                    ->pickupOrderDetailUnloads
                    ->filter(fn($pickup_detail) => $pickup_detail->detail_type == "dispatch_order")
                    ->values();

                $pickup_from_promotion = $pickup_order
                    ->pickupOrderDetailUnloads
                    ->filter(fn($pickup_detail) => $pickup_detail->detail_type == "dispatch_promotion")
                    ->values();

                /**
                 * Alert checked pickup
                 */
                $alert_direct = null;
                switch ($pickup_order->status) {

                    /* pickup has loaded and does not checked */
                    case 'loaded':
                        $alert_direct = match (true) {
                            $pickup_from_direct
                                ->filter(function ($direct) {
                                    return $direct->is_checked && $direct->quantity_actual_checked == $direct->quantity_unit_load;
                                })
                                ->count() < $pickup_from_direct->count() => "Produk belum dicek",
                            default => "Produk sudah dicek semua",
                        };
                        break;

                    default:
                        $alert_direct = match (true) {

                            /* incomplete attacment and load */
                            $pickup_from_direct
                                ->filter(function ($direct) {
                                    return $direct->pickupOrderDetailFiles->count() > 0;
                                })
                                ->count() < $pickup_from_direct->count()
                            &&

                            $pickup_from_direct
                                ->filter(function ($direct) {
                                    return !$direct->is_loaded && $direct->quantity_actual_load == 0;
                                })
                                ->count() < $pickup_from_direct->count() => "Muatan dan foto belum lengkap",

                            /* incomplete attacment */
                            $pickup_from_direct
                                ->filter(function ($direct) {
                                    return $direct->pickupOrderDetailFiles->count() > 0;
                                })
                                ->count() < $pickup_from_direct->count() => "Foto belum lengkap",

                            /* incomplete load */
                            $pickup_from_direct
                                ->filter(function ($direct) {
                                    return !$direct->is_loaded && $direct->quantity_actual_load == 0;
                                })
                                ->count() < $pickup_from_direct->count() => "Muatan belum lengkap",

                            default => "Sudah dimuat seluruhnya",
                        };
                        break;
                }

                /**
                 * Alert checked pickup
                 */
                $alert_promotion = null;
                switch ($pickup_order->status) {

                    /* pickup has loaded and does not checked */
                    case 'loaded':
                        $alert_promotion = match (true) {
                            $pickup_from_promotion
                                ->filter(function ($direct) {
                                    return $direct->is_checked && $direct->quantity_actual_checked == $direct->quantity_unit_load;
                                })
                                ->count() < $pickup_from_promotion->count() => "Produk belum dicek",
                            default => "Produk sudah dicek semua",
                        };
                        break;

                    default:
                        $alert_promotion = match (true) {

                            /* incomplete attacment and load */
                            $pickup_from_promotion
                                ->filter(function ($direct) {
                                    return $direct->pickupOrderDetailFiles->count() > 0;
                                })
                                ->count() < $pickup_from_promotion->count()
                            &&

                            $pickup_from_promotion
                                ->filter(function ($direct) {
                                    return $direct->is_loaded && $direct->quantity_actual_load >= $direct->quantity_unit_load;
                                })
                                ->count() < $pickup_from_promotion->count() => "Muatan dan foto belum lengkap",

                            /* incomplete attacment */
                            $pickup_from_promotion
                                ->filter(function ($direct) {
                                    return $direct->pickupOrderDetailFiles->count() > 0;
                                })
                                ->count() < $pickup_from_promotion->count() => "Foto belum lengkap",

                            /* incomplete load */
                            $pickup_from_promotion
                                ->filter(function ($direct) {
                                    return $direct->is_loaded && $direct->quantity_actual_load >= $direct->quantity_unit_load;
                                })
                                ->count() < $pickup_from_promotion->count() => "Muatan belum lengkap",

                            default => "Sudah dimuat seluruhnya",
                        };
                        break;
                }

                $pickup_order["release_list"] = $pickup_order->pickupUnloadHistories
                    ->map(function ($dispatch) {
                        $detail = [];
                        $detail["id"] = $dispatch->dispatch_id;
                        $detail["dispatch_id"] = $dispatch->dispatch_id;
                        $detail["dispatch_number"] = $dispatch->pickupDispatchAble->dispatch_order_number;
                        $detail["dispatch_type"] = $dispatch->dispatch_type;
                        $detail["release_at"] = $dispatch->pickupDispatchAble->created_at;
                        $detail["release_by"] = $dispatch->createdBy?->name;
                        $detail["release_by_position"] = $dispatch->createdBy?->position?->name;
                        $detail["release_reason"] = $dispatch->notes;
                        return $detail;
                    });

                $pickup_order["unload_file_count"] = $pickup_order
                    ->pickupOrderDetailUnloads
                    ->pluck("pickupOrderDetailFiles")
                    ->flatten()
                    ->count();

                $pickup_order["release_detail_list"] = [
                    "product_direct" => [
                        "products" => $pickup_from_direct,
                        "item_count" => $pickup_from_direct->count(),
                        "alert" => $alert_direct,
                    ],

                    "product_promotion" => [
                        "products" => $pickup_from_promotion,
                        "item_count" => $pickup_from_promotion->count(),
                        "alert" => $alert_promotion,
                    ],
                ];
            }

            $is_meet_loaded_rules = false;
            $is_meet_checked_rules = false;
            switch (true) {
                case $pickup_order->pickupOrderFiles->count() == count(mandatory_captions()) && in_array("Sudah dimuat seluruhnya", [$alert_direct, $alert_promotion]):
                    $is_meet_loaded_rules = true;
                    break;

                case $pickup_order->pickupOrderFiles->count() == count(mandatory_captions()) && in_array("Produk sudah dicek semua", [$alert_direct, $alert_promotion]):
                    $is_meet_loaded_rules = true;
                    $is_meet_checked_rules = true;
                    break;

                default:
                    break;
            }

            $pickup_order["total_weight"] = $pickup_order->pickupOrderDetails->sum("total_weight");
            $pickup_order["capacity_armada"] = ($pickup_order->armada ? $pickup_order->armada->capacity : 0);
            $pickup_order["capacity_left"] = ($pickup_order->armada ? $pickup_order->armada->capacity - $pickup_order->pickupOrderDetails->sum("total_weight") : 0);
            $pickup_order["is_meet_loaded_rules"] = $is_meet_loaded_rules;
            $pickup_order["is_meet_checked_rules"] = $is_meet_checked_rules;

            $pickup_order
                ->unsetRelation("pickupOrderDetails")
                ->unsetRelation("pickupOrderDetailUnloads")
                ->unsetRelation("pickupOrderDispatch")
                ->unsetRelation("pickupUnloadHistories");

            return $this->response("00", "succes", $pickup_order);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ]);
        }
    }

    public static function deliveryAddressMapping($delivery_address)
    {
        return [
            "name" => $delivery_address?->name,
            "address" => $delivery_address?->address,
            "postal_code" => $delivery_address?->postal_code,
            "telephone" => $delivery_address?->telephone,
            "latitude" => $delivery_address?->latitude,
            "longitude" => $delivery_address?->longitude,
            "gmaps_link" => $delivery_address?->gmaps_link,
            "district" => $delivery_address?->district,
            "city" => $delivery_address?->city,
            "province" => $delivery_address?->province,
        ];
    }
}

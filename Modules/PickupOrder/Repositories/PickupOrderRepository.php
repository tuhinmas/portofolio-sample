<?php

namespace Modules\PickupOrder\Repositories;

use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\PickupOrder\Constants\DeliveryPickupOrderStatus;
use Modules\PickupOrder\Constants\PickupOrderStatus;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDetail;

class PickupOrderRepository
{
    public function generatePickUpOrder()
    {
        $currentYear = date('Y');
        $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $currentMonth = $romanMonths[date('n') - 1];
        $lastOrder = PickupOrder::withTrashed()->get()->count();
        $lastOrderNumber = $lastOrder ? intval($lastOrder) : 0;
        $newOrderNumber = str_pad($lastOrderNumber + 1, 3, '0', STR_PAD_LEFT);
        $orderNumber = "$currentYear/PU/$currentMonth/$newOrderNumber";
        return $orderNumber;
    }

    public function batchStore($request)
    {
        if (!empty($request['resources'])) {
            foreach ($request['resources'] as $key => $value) {
                PickupOrderDetail::where('pickup_order_id', $value['pickup_order_id'])->forceDelete();
                DeliveryPickupOrder::where('pickup_order_id', $value['pickup_order_id'])->forceDelete();
                break;
            }
        }

        $pickupOrderId = false;
        if (!empty($request['resources'])) {
            $data = [];
            foreach ($request['resources'] as $key => $value) {
                $deliveryOrderType = '';
                $findDeliveryOrder = DeliveryOrder::find($value['delivery_order_id']);
                if ($findDeliveryOrder && $findDeliveryOrder->is_promotion == 0) {
                    $deliveryOrderType = 'delivery_orders';
                } else {
                    $deliveryOrderType = 'promotion_goods';
                }

                $data[] = [
                    "id" => \Str::uuid(),
                    "pickup_order_id" => $value['pickup_order_id'],
                    "delivery_order_id" => $value['delivery_order_id'],
                    "delivery_order_type" => $deliveryOrderType,
                    "status" => $value['status'] ?? DeliveryPickupOrderStatus::NOT_RECEIVED,
                    "created_at" => date('Y-m-d H:i:s'),
                    "updated_at" => date('Y-m-d H:i:s'),
                ];

                $pickupOrderId = $value['pickup_order_id'];
            }
            DeliveryPickupOrder::insert($data);
        }

        $deliveryPickUpOrder = DeliveryPickupOrder::where('pickup_order_id', $pickupOrderId)->get();
        foreach (($deliveryPickUpOrder ?? []) as $item) {
            if ($item->delivery_order_type == "delivery_orders") {
                foreach ((optional($item->deliveryOrder)->dispatchOrder->dispatchOrderDetail ?? []) as $value) {
                    $productName = $value->product->name . ' ' . $value->product->size;
                    $pickupOrder = PickupOrderDetail::where('product_name', $productName)->where('pickup_order_id', $pickupOrderId)->first();
                    if (empty($pickupOrder)) {
                        $weight = ($value->product->weight ?? 0.0);

                        $quantityUnitLoad = !empty($value->product->package) ? ($value->quantity_packet_to_send ?? 0) : ($value->quantity_unit ?? 0);
                        $estimateWeight = !empty($value->product->package) ? (($value->quantity_packet_to_send ?? 0) * $weight) : (($value->quantity_unit ?? 0) * $weight);

                        $pickupOrder = PickupOrderDetail::create([
                            'pickup_order_id' => $pickupOrderId,
                            'product_name' => $productName,
                            'type' => $value->product->type,
                            'quantity_unit_load' => $quantityUnitLoad,
                            'unit' => $value->product->unit,
                            'weight' => $weight,
                            'total_weight' => $estimateWeight,
                        ]);
                    } else {
                        $pickupOrder->quantity_unit_load += ($value->quantity_packet_to_send ?? 0);
                        $pickupOrder->total_weight += ($value->product->weight ?? 0.0);
                        $pickupOrder->save();
                    }
                }
            } else {
                foreach ((optional($item->deliveryOrder)->dispatchPromotion->dispatchPromotionDetails ?? []) as $value) {
                    if (!empty($value->promotionGood->product_id)) {
                        $itemProduct = optional($value->promotionGood)->product;
                        $type = $itemProduct->type;
                        $weight = $itemProduct->weight;
                    } else {
                        $itemProduct = $value->promotionGood;
                        $type = "Non-Produk";
                        $weight = $itemProduct->weight;
                    }

                    $productName = $itemProduct->name . ' ' . $itemProduct->size;
                    $pickupOrder = PickupOrderDetail::where('product_name', $productName)->where('pickup_order_id', $pickupOrderId)->first();
                    if (empty($pickupOrder)) {
                        $pickupOrder = PickupOrderDetail::create([
                            'pickup_order_id' => $pickupOrderId,
                            'product_name' => $productName,
                            'type' => $type,
                            'quantity_unit_load' => ($value->quantity_packet_to_send ?? 0),
                            'unit' => $itemProduct->unit ?? '-',
                            'weight' => ($weight ?? 0.0),
                            'total_weight' => ($weight ?? 0.0),
                        ]);
                    } else {
                        $pickupOrder->quantity_unit_load += ($value->quantity_packet_to_send ?? 0);
                        $pickupOrder->total_weight += ($value->package_weight ?? 0.0);
                        $pickupOrder->save();
                    }
                }
            }
        }

        $pickupOrderDetails = PickupOrderDetail::where('pickup_order_id', $pickupOrderId)->get();
        foreach ($pickupOrderDetails as $key => $value) {
        }

        return PickupOrder::with('pickupOrderDetails')->find($pickupOrderId);
    }

    public function listDeliveryOrder($params = [])
    {
        return DeliveryOrder::with([
            'dispatchOrder',
            'dispatchOrder.driver',
            'receivingGoods',
            "dispatchPromotion",
            "dispatchPromotion.driver",
        ])
            ->where(function ($q) {
                return $q->whereDoesntHave('receivingGoods')->orWhereHas('receivingGoods', function ($q) {
                    return $q->where('status', 1);
                });
            })
            ->where(function ($q) {
                return $q
                    ->whereDoesntHave('deliveryPickupOrders')
                    ->orWhereHas('deliveryPickupOrders', function ($q) {
                        $q->whereHas('pickupOrder', function ($q) {
                            return $q->where('status', PickupOrderStatus::CANCELED);
                        });
                    });
            })
            ->where(function ($q) {
                return $q->where('status', 'send');
            })
            ->when(!empty($params['search']), function ($q) use ($params) {
                return $q->where('delivery_order_number', 'like', '%' . $params['search'] . '%');
            })
            ->where(function ($q) use ($params) {
                $q->where(function ($q) use ($params) {
                    $q->whereHas('dispatchOrder', function ($q) {
                        return $q->where('type_driver', 'internal');
                    });

                    if (!empty($params['warehouse_id'])) {
                        $q->where(function ($q) use ($params) {
                            return $q->whereHas('dispatchOrder', function ($q) use ($params) {
                                $q->where('id_warehouse', $params['warehouse_id']);
                            })->orWhere('id_warehouse', $params['warehouse_id']);
                        });
                    }

                    if (!empty($params['driver_id'])) {
                        $q->where(function ($q) use ($params) {
                            return $q->whereHas('dispatchOrder.driver', function ($q) use ($params) {
                                $q->where('id_armada', $params['driver_id']);
                            });
                        });
                    }
                })->orWhere(function ($q) use ($params) {
                    $q->whereHas('dispatchPromotion', function ($q) {
                        return $q->where('type_driver', 'internal');
                    });
                    if (!empty($params['warehouse_id'])) {
                        $q->where(function ($q) use ($params) {
                            return $q->whereHas('dispatchPromotion', function ($q) use ($params) {
                                $q->where('id_warehouse', $params['warehouse_id']);
                            })->orWhere('id_warehouse', $params['warehouse_id']);
                        });
                    }

                    if (!empty($params['driver_id'])) {
                        $q->where(function ($q) use ($params) {
                            return $q->whereHas('dispatchPromotion.driver', function ($q) use ($params) {
                                $q->where('id_armada', $params['driver_id']);
                            });
                        });
                    }
                });
            })
            ->when(!empty($params['date_delivery']), function ($q) use ($params) {
                return $q->where('date_delivery', 'like', $params['date_delivery'] . '%');
            })
            ->get();
    }

    public function listDeliveryPickupOrder($request)
    {
        $deliveryPickUpOrder = DeliveryOrder::with(['dispatchPromotion.dispatchPromotionDetails.promotionGood.product'])->whereIn('id', $request['delivery_order_id'])->get();
        $pickupOrdersArray = [];
        foreach (($deliveryPickUpOrder ?? []) as $item) {
            foreach ((optional($item->dispatchOrder)->dispatchOrderDetail ?? []) as $value) {
                $productName = $value->product->name . ' ' . $value->product->size;
                $weight = ($value->product->weight ?? 0.0);
                $quantityUnitLoad = !empty($value->product->package) ? ($value->quantity_packet_to_send ?? 0) : ($value->quantity_unit ?? 0);
                $estimateWeight = !empty($value->product->package) ? (($value->quantity_packet_to_send ?? 0) * $weight) : (($value->quantity_unit ?? 0) * $weight);
                $pickupOrder = [
                    'product_name' => $productName,
                    'type' => $value->product->type,
                    'quantity_unit_load' => $quantityUnitLoad,
                    'unit' => $value->product->unit,
                    'weight' => $weight,
                    'estimate_weight' => $estimateWeight,
                ];
                $pickupOrdersArray[] = $pickupOrder;
            }

            foreach ((optional($item->dispatchPromotion)->dispatchPromotionDetails ?? []) as $value) {
                if (!empty($value->promotionGood->product_id)) {
                    $itemProduct = optional($value->promotionGood)->product;
                    $type = $itemProduct->type;
                    $weight = $itemProduct->weight;
                } else {
                    $itemProduct = $value->promotionGood;
                    $type = "Non-Produk";
                    $weight = $itemProduct->weight;
                }

                $productName = $itemProduct->name . ' ' . $itemProduct->size;
                $pickupOrder = [
                    'product_name' => $productName,
                    'type' => $type,
                    'quantity_unit_load' => ($value->quantity_packet_to_send ?? 0),
                    'unit' => $itemProduct->unit ?? '-',
                    'weight' => $weight,
                    'estimate_weight' => (($value->quantity_packet_to_send ?? 0) * ($weight ?? 0.0)),
                ];

                $pickupOrdersArray[] = $pickupOrder;
            }
        }

        $groupedArray = [];

        foreach ($pickupOrdersArray as $pickupOrder) {
            $productName = $pickupOrder['product_name'];
            if (!isset($groupedArray[$productName])) {
                $groupedArray[$productName] = [
                    'product_name' => $productName,
                    'type' => $pickupOrder['type'],
                    'quantity_unit_load' => 0,
                    'unit' => $pickupOrder['unit'],
                    'weight' => $pickupOrder['weight'],
                    'estimate_weight' => $pickupOrder['weight'] * 0,
                ];
            }

            $groupedArray[$productName]['quantity_unit_load'] += $pickupOrder['quantity_unit_load'];
            $groupedArray[$productName]['estimate_weight'] += $pickupOrder['quantity_unit_load'] != 0 ? ($pickupOrder['weight'] * $pickupOrder['quantity_unit_load']) : $pickupOrder['weight'];
        }

        $groupedArray = array_values($groupedArray);

        return $groupedArray;
    }

    public function travelDocumentList($pickupOrderId)
    {
        return DeliveryPickupOrder::where('pickup_order_id', $pickupOrderId)->get()->map(function ($q) {
            $data = [];
            $q->deliveryOrder->dispatchOrder->dispatchOrderDetail->each(function ($value) use (&$data) {
                $productName = $value->product->name . ' ' . $value->product->size;
                $data[] = [
                    'product_name' => $productName,
                    'type' => $value->product->type,
                    'quantity_unit_load' => ($value->quantity_packet_to_send ?? 0),
                    'unit' => $value->product->unit,
                    'weight' => ($value->product->weight ?? 0.0),
                ];
            });

            return [
                'delivery_order_number' => $q->deliveryOrder->delivery_order_number,
                'dispatch_lists' => $data,
            ];
        });
    }
}

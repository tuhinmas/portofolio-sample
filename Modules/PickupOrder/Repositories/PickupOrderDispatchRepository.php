<?php

namespace Modules\PickupOrder\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Product;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\PickupOrder\Constants\DeliveryPickupOrderStatus;
use Modules\PickupOrder\Constants\PickupOrderStatus;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;
use Modules\PickupOrder\Entities\PickupLoadHistory;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Entities\PickupOrderFile;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Modules\PromotionGood\Entities\DispatchPromotionDetail;

class PickupOrderDispatchRepository
{
    public function groupDispatch($requestDispatch)
    {
        $dispatchOrdersQuery = DispatchOrderDetail::whereIn("id_dispatch_order", $requestDispatch)
            ->select("*")
            ->with(['salesOrderDetail', "product"])
            ->get()
            ->map(function($q){
                return [
                    "product_id" => $q->id_product,
                    "product_name" => $q->product->name.' '.$q->product->size,
                    "is_package" => $q->salesOrderDetail->package_name ? "true" : "false",
                    "unit_package" => $q->salesOrderDetail->package_name,
                    "unit" => $q->product->unit,
                    "weight_package" => $q->salesOrderDetail->package_weight,
                    "weight" => $q->product->weight,
                    "type" => $q->product->type,
                    "detail_type" => "dispatch_order",
                    "quantity_unit" => $q->quantity_unit,
                    "quantity_packet_to_send" => $q->quantity_packet_to_send,
                    "planned_quantity_unit" => $q->planned_quantity_unit,
                    "planned_package_to_send" => $q->planned_package_to_send,
                    "product" => $q->product
                ];
            })->toArray();

            $dispatchOrders = [];

            foreach ($dispatchOrdersQuery as $item) {
                $key = $item['product_id'] . '-' . $item['is_package'];
                if (!isset($dispatchOrders[$key])) {
                    $dispatchOrders[$key] = $item;
                } else {
                    $dispatchOrders[$key]['quantity_packet_to_send'] += $item['quantity_packet_to_send'];
                    $dispatchOrders[$key]['planned_package_to_send'] += $item['planned_package_to_send'];
                    $dispatchOrders[$key]['quantity_unit'] += $item['quantity_unit'];
                    $dispatchOrders[$key]['planned_quantity_unit'] += $item['planned_quantity_unit'];
                }
            }

            // Reset the array keys
            $dispatchOrders = array_values($dispatchOrders);

            $mappedDispatchOrders = array_map(function($q) {
                $quantityUnitLoad = $q["is_package"] === "true" ? $q["planned_package_to_send"] : $q["planned_quantity_unit"];
                $quantityActualLoad = $q["is_package"] === "true" ? $q["quantity_packet_to_send"] : $q["quantity_unit"];
                $weight = $q["is_package"] === "true" ? $q["weight_package"] : $q["weight"];
                
                return [
                    "product_id" => $q["product_id"],
                    "product_name" => $q["product_name"],
                    "type" => $q["type"],
                    "quantity_unit_load" => $quantityUnitLoad,
                    "quantity_actual_load" => $quantityActualLoad,
                    "unit" => $q["is_package"] === "true" ? $q["unit_package"] : $q["unit"],
                    "weight" => $weight,
                    "total_weight" => $quantityUnitLoad * (float)$weight,
                    "detail_type" => $q["detail_type"],
                    "product" => $q["product"],
                ];
            }, $dispatchOrders);
            // ->map(function($q){
            //     $quantityActualUnitLoad = !empty($q->product->package) ? $q->total_packet : $q->total_unit;
            //     $quantityPlannedUnitLoad = !empty($q->product->package) ? $q->planned_total_packet : $q->planned_total_unit;
            //     $type = !empty($q->product->package) ? $q->product->package->packaging : $q->product->unit;
            //     $weight = !empty($q->product->package) ? $q->product->package->weight : $q->product->weight;

            // return [
            //         "product_id" => $q->id_product,
            //         "product_name" => $q->product->name.' '.$q->product->size,
            //         "type" => $q->product->type,
            //         "quantity_unit_load" => $quantityPlannedUnitLoad,
            //         "quantity_actual_load" => $quantityActualUnitLoad,
            //         "unit" => $type,
            //         "weight" => $weight,
            //         "total_weight" => $quantityPlannedUnitLoad * $weight,
            //         "detail_type" => "dispatch_order",
            //         "product" => $q->product,
            //     ];
            // })->toArray();


        $dispatchPromotions = DispatchPromotionDetail::whereIn("dispatch_promotion_id", $requestDispatch)
            ->select("*")
            ->addSelect(DB::raw("SUM(quantity_packet_to_send) as total_packet"))
            ->addSelect(DB::raw("SUM(planned_package_to_send) as planned_total_packet"))
            ->addSelect(DB::raw("SUM(quantity_unit) as total_unit"))
            ->addSelect(DB::raw("SUM(planned_quantity_unit) as planned_total_unit"))
            ->with("promotionGood.product")
            ->with("promotionGood")
            ->groupBy("promotion_good_id")
            ->get()
            ->map(function($q){
                $quantityPlannedUnitLoad =  $q->planned_total_unit;
                $quantityActualUnitLoad =  $q->total_unit;
                if ($q->promotionGood->product_id) {
                    $data = [
                        "product_id" => optional($q->promotionGood)->product_id,
                        "product_name" => $q->promotionGood->product->name.' '.$q->promotionGood->product->size,
                        "type" => $q->promotionGood->product->type,
                        "quantity_unit_load" => $quantityPlannedUnitLoad,
                        "quantity_actual_load" => $quantityActualUnitLoad,
                        "unit" => $q->promotionGood->product->unit,
                        "weight" => $q->promotionGood->product->weight,
                        "total_weight" => $quantityPlannedUnitLoad * $q->promotionGood->product->weight,
                        "detail_type" => "dispatch_promotion",
                        "product" => $q->promotionGood->product,
                    ];
                }else{
                    $data = [
                        "product_id" => null,
                        "product_name" => $q->promotionGood->name.' '.$q->promotionGood->size,
                        "type" => $q->promotionGood->type,
                        "unit" => $q->promotionGood->unit,
                        "quantity_unit_load" => $quantityPlannedUnitLoad,
                        "quantity_actual_load" => $quantityActualUnitLoad,
                        "weight" => $q->promotionGood->weight,
                        "total_weight" => $quantityPlannedUnitLoad * $q->promotionGood->weight,
                        "detail_type" => "dispatch_promotion",
                        "product" => null,
                    ];
                }

                return $data;
            })->toArray();

        return [
            "direct_order" => $mappedDispatchOrders,
            "promotion" => $dispatchPromotions
        ];
    }

    public function unloadDispatch($pickupOrderDispatchId, $data = [])
    {
        $pickupOrderDispatch = PickupOrderDispatch::find($pickupOrderDispatchId);

        try {
            DB::beginTransaction();

            //update surat jalan 
            $findDeliveryOrder = DeliveryOrder::where("status", "send")
                ->where(function($q) use($pickupOrderDispatch){
                    $q->where("dispatch_order_id", $pickupOrderDispatch->dispatch_id)->orWhere("dispatch_promotion_id", $pickupOrderDispatch->dispatch_id);
                })->select("id")->get()->pluck("id")->toArray();

            DeliveryOrderNumber::whereIn("delivery_order_id", $findDeliveryOrder)->delete();

            DeliveryOrder::where("status", "send")
                ->where(function($q) use($pickupOrderDispatch){
                    $q->where("dispatch_order_id", $pickupOrderDispatch->dispatch_id)->orWhere("dispatch_promotion_id", $pickupOrderDispatch->dispatch_id);
                })->update([
                    "status" => "canceled",
                    "status_note" => $data["status_note"]
                ]);

            $pickupOrderId = $pickupOrderDispatch->pickup_order_id;

            PickupLoadHistory::create([
                "pickup_order_id" => $pickupOrderDispatch->pickup_order_id,
                "dispatch_id" => $pickupOrderDispatch->dispatch_id,
                "dispatch_type" => $pickupOrderDispatch->dispatch_type,
                "status" => "canceled",
                "dispatch" => json_encode($pickupOrderDispatch->pickupDispatchAble),
                "notes" => $data["status_note"],
                "created_by" => auth()->user()->personel_id
            ]);

            $findPickupOrderDispatch = PickupOrderDispatch::where("id", $pickupOrderDispatch->id)->first();
            if ($findPickupOrderDispatch->dispatch_type == "dispatch_order") {
                DispatchOrder::where("id", $findPickupOrderDispatch->dispatch_id)->update([
                    "status" => "planned"
                ]);
            }else{
                DispatchPromotion::where("id", $findPickupOrderDispatch->dispatch_id)->update([
                    "status" => "planned"
                ]);
            }


            //delete pickup order dispatch id
            PickupOrderDispatch::where("id", $pickupOrderDispatch->id)->delete();

            $existPickupOrderDispatch = PickupOrderDispatch::where("pickup_order_id", $pickupOrderId)
                ->select("dispatch_id")
                ->get()
                ->pluck("dispatch_id")
                ->toArray();

            $pickupOrderDetailExist = [];

            //create ulang
            $dispatchRepository = new PickupOrderDispatchRepository;
            $groupRepository = $dispatchRepository->groupDispatch([$pickupOrderDispatch->dispatch_id]);
            foreach ($groupRepository as $key => $value) {
                foreach ($value as $key => $row) {
                    $actualLoad = $row["quantity_unit_load"];
                    unset($row["product"]);
                    PickupOrderDetail::create(array_merge([
                        "pickup_order_id" => $pickupOrderId,
                        "pickup_type" => "unload"
                    ], $row));
                }
            }

            // $groupRepository = $dispatchRepository->groupDispatch($existPickupOrderDispatch);
            // foreach ($groupRepository as $key => $value) {
            //     foreach ($value as $key => $row) {
            //         $actualLoad = $row["quantity_unit_load"];
            //         unset($row["product"], $row["quantity_unit_load"]);

            //         $findPickupOrderDetail = PickupOrderDetail::where("pickup_order_id", $pickupOrderId)
            //             ->where("product_id", $row["product_id"])
            //             ->where("product_name", $row["product_name"])
            //             ->first();
            //     }
            // }

            $countPickupOrderDispatch = PickupOrderDispatch::where("pickup_order_id", $pickupOrderId)->get()->count();
            if ($countPickupOrderDispatch == 0) {
                PickupOrder::where("id", $pickupOrderId)->update([
                    "status" => "canceled",
                    "note" => "semua muatan dilepas"
                ]);
            }else{
                PickupOrder::where("id", $pickupOrderId)->update([
                    "status" => "revised",
                    "note" => "ada muatan dilepas"
                ]);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            //throw $th;
        }
    }
}
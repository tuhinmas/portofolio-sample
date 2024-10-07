<?php

namespace Modules\PickupOrder\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Driver;
use Modules\DataAcuan\Entities\Warehouse;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchList;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PickupOrder\Constants\DeliveryPickupOrderStatus;
use Modules\PickupOrder\Constants\PickupOrderStatus;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;
use Modules\PickupOrder\Entities\PickupLoadHistory;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDetail;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Entities\PickupOrderHistory;
use Modules\PromotionGood\Entities\DispatchPromotion;

class PickupOrderV2Repository
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

    public function store($data = [])
    {
        try {
            DB::beginTransaction();
            $findDispatch = DispatchOrder::where("id_warehouse", $data["warehouse_id"])
                ->where(function($q) use($data){
                    $q->where("id_armada", $data["driver_id"])->orWhere("armada_identity_number", $data["driver_id"]);
                })
                ->whereDate("date_delivery", date("Y-m-d", strtotime($data["delivery_date"])))
                ->first();
           
            if (!$findDispatch) {
                $findDispatch = DispatchPromotion::where("id_warehouse", $data["warehouse_id"])
                ->where(function($q) use($data){
                    $q->where("id_armada", $data["driver_id"])->orWhere("armada_identity_number", $data["driver_id"]);
                })
                ->whereDate("date_delivery", date("Y-m-d", strtotime($data["delivery_date"])))
                ->first();
            }

            $findDispatchGroup = DispatchOrder::whereIn("id", $data['dispatch_id'])->first();
            if (!$findDispatchGroup) {
                $findDispatchGroup = DispatchPromotion::whereIn("id", $data['dispatch_id'])->first();
            }

            $deliveryDate = $findDispatchGroup->date_delivery." ".date("H:i:s", strtotime($data["delivery_date"]));

            $pickupOrderData = $data;
            $uuid_validation_regex = "/^[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}$/"; 
            if(!preg_match($uuid_validation_regex, $data['driver_id'])){
                unset($pickupOrderData['driver_id']);
            }

            unset($pickupOrderData['dispatch_id'], $pickupOrderData['delivery_date']);
            
            $pickupOrder = PickupOrder::create(array_merge([
                "pickup_number" =>  $this->generatePickUpOrder(),
                "created_by" => auth()->user()->personel_id,
                "type_driver" => $findDispatch->type_driver ?? null,
                "armada_identity_number" => $findDispatch->armada_identity_number,
                "delivery_date" => $deliveryDate
            ],$pickupOrderData));

            if (!empty($data['dispatch_id'])) {
                foreach ($data['dispatch_id'] as $key => $value) {
                    $dispatchOrder = DispatchOrder::find($value);
                    $dispatchPromotion = DispatchPromotion::find($value);

                    PickupOrderDispatch::create([
                        'pickup_order_id' => $pickupOrder->id,
                        "dispatch_id" => $value,
                        "dispatch_type" => $dispatchOrder ? "dispatch_order" : "dispatch_promotion"
                    ]);

                    PickupLoadHistory::create([
                        "pickup_order_id" => $pickupOrder->id,
                        "dispatch_id" => $value,
                        "dispatch_type" => $dispatchOrder ? "dispatch_order" : "dispatch_promotion",
                        "dispatch" => $dispatchOrder ? json_encode($dispatchOrder) : json_encode($dispatchPromotion),
                        "status" => "created",
                        "created_by" => auth()->user()->personel_id 
                    ]);
                }

                $dispatchRepository = new PickupOrderDispatchRepository;
                $groupRepository = $dispatchRepository->groupDispatch($data['dispatch_id']);
                foreach ($groupRepository as $key => $value) {
                    foreach ($value as $key => $row) {
                        unset($row["product"]);
                        PickupOrderDetail::create(array_merge([
                            "pickup_order_id" => $pickupOrder->id
                        ], $row));
                    }
                }
            }

            DB::commit();

            return $pickupOrder;            
        } catch (\Exception $th) {
            DB::rollBack();
            return $th;
        }

    }

    public function update($pickupOrderId, $data = [])
    {
        $pickupOrderData = $data;
        $uuid_validation_regex = "/^[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}$/"; 
        unset($pickupOrderData['dispatch_id'], $pickupOrderData['delivery_date']);
        
        if (isset($data['driver_id'])) {
            if(!preg_match($uuid_validation_regex, $data['driver_id'])){
                unset($pickupOrderData['driver_id']);
            }
        }

        PickupOrder::where('id', $pickupOrderId)->update($pickupOrderData);
        $pickupOrder =  PickupOrder::find($pickupOrderId);

        if (!empty($data['dispatch_id'])) {
            PickupOrderDispatch::where("pickup_order_id", $pickupOrderId)->delete();
            PickupOrderDetail::where("pickup_order_id", $pickupOrderId)->delete();

            foreach ($data['dispatch_id'] as $key => $value) {
                $dispatchOrder = DispatchOrder::find($value);
                $dispatchPromotion = DispatchPromotion::find($value);

                PickupOrderDispatch::create([
                    'pickup_order_id' => $pickupOrder->id,
                    "dispatch_id" => $value,
                    "dispatch_type" => $dispatchOrder ? "dispatch_order" : "dispatch_promotion"
                ]);
                                
                PickupLoadHistory::create([
                    "pickup_order_id" => $pickupOrder->id,
                    "dispatch_id" => $value,
                    "dispatch_type" => $dispatchOrder ? "dispatch_order" : "dispatch_promotion",
                    "dispatch" => $dispatchOrder ? json_encode($dispatchOrder) : json_encode($dispatchPromotion),
                    "status" => "created",
                    "created_by" => auth()->user()->personel_id 
                ]);
            }

            $dispatchRepository = new PickupOrderDispatchRepository;
            $groupRepository = $dispatchRepository->groupDispatch($data['dispatch_id']);
            foreach ($groupRepository as $key => $value) {
                foreach ($value as $key => $row) {
                    unset($row["product"]);
                    PickupOrderDetail::create(array_merge([
                        "pickup_order_id" => $pickupOrder->id
                    ], $row));
                }
            }
        }

        return $pickupOrder;   
    }

    
    public function detailDispatch($params = [])
    {
        $driver = Driver::where("police_number", $params["police_number"])->with("personel")->first();
        $findDispatchList = DispatchList::where(function($q) use($params){
                $q->whereHas("driver", function($q) use($params) {
                    $q->where("police_number", $params["police_number"]);
                })->orWhere("armada_identity_number", $params["police_number"]);
            })
            ->where("id_warehouse", $params['id_warehouse'])
            ->where("date_delivery", $params['date_delivery'])
            ->first();

        $shippingDetail = [
            "pickup_order_number" => null,
            "driver" => $driver,
            "driver_name" => $driver?->personel->name ?? $findDispatchList->driver_name,
            "driver_phone_number" => $findDispatchList->driver_phone_number,
            "warehouse_code" => Warehouse::find($params["id_warehouse"]),
            "date_delivery" => $params['date_delivery'],
            "dispatch_detail" => $findDispatchList
        ];

        $listDispatch = DispatchList::with(
            "promotionGoodRequest",
            "deliveryOrderPromotion.receivingGoodHasReceived",
            "deliveryOrder.receivingGoodHasReceived",
            "invoice",
            "addressDelivery",
            "driver",
            "invoice.salesOrder.dealer",
            "invoice.salesOrder.subDealer",

            "pickupDispatchOriginals.pickupOrder",
            
            "addressDelivery.province",
            "addressDelivery.city",
            "addressDelivery.district",
            "addressDeliveryPromotion",
            "addressDeliveryPromotion.province",
            "addressDeliveryPromotion.city",
            "addressDeliveryPromotion.district",

            "dispatchOrder.dispatchOrderDetail",
            "dispatchPromotion.dispatchPromotionDetails"
        )->select(
            "*",
            DB::raw("dispatch_order_weight as total_weight")
        )
        ->whereNotNull("delivery_address_id")
        ->where(function($q) use($params){
            $q->whereHas("driver", function($q) use($params) {
                $q->where("police_number", $params["police_number"]);
            })->orWhere("armada_identity_number", $params["police_number"]);
        })
        ->where("id_warehouse", $params['id_warehouse'])
        ->where("date_delivery", $params['date_delivery'])
        ->where(function($q) {
            $q->where("status", "planned")
                ->where(function($q) {
                    $q->whereDoesntHave("pickupDispatchOriginals")->orWhereHas("pickupDispatchOriginals", function($q) {
                        $q->whereHas('pickupOrder', function($q) {
                            $q->where('status', 'canceled');
                        });
                    });
                });
        })
        ->get()
        ->map(function($q){
            if ($q->dispatch_type == "dispatch_order") {
                $invoice = [
                    "invoice" => $q->invoice->invoice,
                    "invoice_id" => $q->invoice->id,
                    "customer_number" => $q->invoice->salesOrder->dealer ? 
                        "CUST-".$q->invoice->salesOrder->dealer->dealer_id 
                            : 
                        "CUST-SUB-".$q->invoice->salesOrder->subDealer->dealer_id,
                    "customer_name" => $q->invoice->salesOrder->dealer ? 
                        $q->invoice->salesOrder->dealer->prefix." ". $q->invoice->salesOrder->dealer->name." ".$q->invoice->salesOrder->dealer->sufix
                            : 
                        $q->invoice->salesOrder->subDealer->prefix. " ".$q->invoice->salesOrder->subDealer->name." ".$q->invoice->salesOrder->subDealer->sufix,
                    "customer_owner" =>   $q->invoice->salesOrder->dealer ? 
                        $q->invoice->salesOrder->dealer->owner
                            : 
                        $q->invoice->salesOrder->subDealer->owner,
                    "province" => $q->addressDelivery->province->name,
                    "city" => $q->addressDelivery->city->name,
                    "district" => $q->addressDelivery->district->name,
                    "total_item" => $q->dispatchOrder->dispatchOrderDetail->count(),
                ];
            }else{
                $customerOwner = $q->promotionGoodRequest->createdBy->name;
                $customerNumber = null;
                $customerName = null;
                if ($q->promotionGoodRequest->event_id != null) {
                    if($q->promotionGoodRequest->event->dealer_id != null){
                        $customerNumber = "CUST-".$q->promotionGoodRequest->event->dealer->dealer_id;
                        $customerName = $q->promotionGoodRequest->event->dealer->prefix." ". $q->promotionGoodRequest->event->dealer->name." ".$q->promotionGoodRequest->event->dealer->sufix;
                    }elseif ($q->promotionGoodRequest->event->sub_dealer_id != null) {
                        $customerNumber = "CUST-SUB-".$q->promotionGoodRequest->event->subDealer->sub_dealer_id;
                        $customerName = $q->promotionGoodRequest->event->subDealer->prefix." ". $q->promotionGoodRequest->event->subDealer->name." ".$q->promotionGoodRequest->event->subDealer->sufix;
                    }
                    $customerOwner = $q->promotionGoodRequest->event->personel->name;
                }else{
                    $customerNumber = "-";
                    $customerName = "-";
                }

                $invoice = [
                    "invoice" => "-",
                    "invoice_id" => null,
                    "customer_number" => $customerNumber,
                    "customer_name" => $customerName,
                    "customer_owner" => $customerOwner,
                    "province" => $q->addressDeliveryPromotion->province->name,
                    "city" => $q->addressDeliveryPromotion->city->name,
                    "district" => $q->addressDeliveryPromotion->district->name,
                    "total_item" => $q->dispatchPromotion->dispatchPromotionDetails->count(),
                ];
            }

            return array_merge($invoice,[
                    "id" => $q->id,
                    "number_dispatch" => $q->order_number,
                    "dispatch_type" => $q->dispatch_type,
                    "weight" => $q->total_weight,
                    "status" => $q->status
                ]);
        });

        return [
            "shipping_detail" => $shippingDetail,
            "list_dispatch" => $listDispatch
        ];
    }

    public function detail($params = [], $pickupOrderId)
    {
        $pickupOrder = PickupOrder::with('warehouse.province','warehouse.city','warehouse.district','armada')->find($pickupOrderId);
    
        $dispatchExist = PickupOrderDispatch::where("pickup_order_id", $pickupOrder->id)
            ->select("dispatch_id")
            ->get()
            ->pluck("dispatch_id")
            ->toArray();

        $masterListDispatch = $this->masterListDispatch([
            "police_number" => $pickupOrder->armada->police_number ?? $pickupOrder->armada_identity_number,
            "id_warehouse" => $pickupOrder->warehouse_id,
            "date_delivery" => date('Y-m-d', strtotime($pickupOrder->delivery_date)),
            "pickup_order_id" => $pickupOrder->id
        ]);

        return [
            "pickup_order" => $pickupOrder,
            "master_dispatch" => $masterListDispatch,
            "dispatch_exist_checklist" => $dispatchExist
        ];
    }

    public function masterListDispatch($params = [])
    {
        $dispatchOrder = DispatchOrder::
            with(
                "invoice",
                "addressDelivery",
                "driver",
                "addressDelivery.province",
                "addressDelivery.city",
                "addressDelivery.district",
                "dispatchOrderDetail",
                "invoice.salesOrder.dealer",
                "invoice.salesOrder.subDealer"
            )
            ->where(function($q) use($params){
                $q->whereHas("driver", function($q) use($params){
                    $q->where("police_number", $params["police_number"]);
                })->orWhere("armada_identity_number", $params["police_number"]);
            })
            ->has('invoice')
            ->select("*")
            ->addSelect(DB::raw("dispatch_order_weight as total_weight"))
            ->whereNotNull("delivery_address_id")
            ->where("id_warehouse", $params['id_warehouse'])
            ->where("date_delivery", $params['date_delivery'])
            ->where(function($q){
                $q->where("status", "!=", "canceled")->where("is_active", 1);
            })
            // ->where(function($q) use($params){
            //     $q->whereDoesntHave("pickupDispatchOriginals")->orWhereHas("pickupDispatchOriginals", function($q) use($params){
            //         $q->when(!empty($params['pickup_order_id']), function($q) use($params){
            //             $q->where("pickup_order_id", $params['pickup_order_id']);
            //         })->where(function($q){
            //             $q->whereDoesntHave('pickupOrder')->orWhereHas('pickupOrder', function($q) {
            //                 $q->where('status', '!=', 'canceled');
            //             });
            //         });
            //     });
            // })
            ->get()
            ->map(function($q){
                return [
                    "id" => $q->id,
                    "number_dispatch" => $q->order_number,
                    "invoice" => $q->invoice->invoice,
                    "invoice_id" => $q->invoice->id,
                    "customer_number" => $q->invoice->salesOrder->dealer ? "CUST-".$q->invoice->salesOrder->dealer->dealer_id : "CUST-SUB-".$q->invoice->salesOrder->subDealer->dealer_id,
                    "customer_name" => $q->invoice->salesOrder->dealer ? 
                        $q->invoice->salesOrder->dealer->prefix." ". $q->invoice->salesOrder->dealer->name." ".$q->invoice->salesOrder->dealer->sufix
                            : $q->invoice->salesOrder->subDealer->prefix. " ".$q->invoice->salesOrder->subDealer->name." ".$q->invoice->salesOrder->subDealer->sufix,
                    "customer_owner" =>   $q->invoice->salesOrder->dealer ? 
                        $q->invoice->salesOrder->dealer->owner
                        : $q->invoice->salesOrder->subDealer->owner,
                    "province" => $q->addressDelivery->province->name,
                    "city" => $q->addressDelivery->city->name,
                    "district" => $q->addressDelivery->district->name,
                    "weight" => $q->total_weight,
                    "total_item" => $q->dispatchOrderDetail->count()
                ];
            })->toArray();

        $dispatchPromotion = DispatchPromotion::
            with(
                "addressDelivery",
                "driver",
                "addressDelivery.province",
                "addressDelivery.city",
                "addressDelivery.district",
                "dispatchPromotionDetails",
            )
            ->select("*")
            ->addSelect(DB::raw("dispatch_order_weight as total_weight"))
            ->whereNotNull("delivery_address_id")
            ->where("id_warehouse", $params['id_warehouse'])
            ->where("date_delivery", $params['date_delivery'])
            ->where(function($q) use($params){
                $q->whereHas("driver", function($q) use($params){
                    $q->where("police_number", $params["police_number"]);
                })->orWhere("armada_identity_number", $params["police_number"]);
            })
            ->where(function($q){
                $q->where("status", "!=", "canceled")->where("is_active", 1);
            })
            ->where(function($q) use($params){
                $q->whereDoesntHave("pickupDispatchOriginals")->orWhereHas("pickupDispatchOriginals", function($q) use($params){
                    $q->when(!empty($params['pickup_order_id']), function($q) use($params){
                        $q->where("pickup_order_id", $params['pickup_order_id']);
                    })->where(function($q){
                        $q->whereDoesntHave('pickupOrder')->orWhereHas('pickupOrder', function($q) {
                            $q->where('status', '!=', 'canceled');
                        });
                    });
                });
            })
            ->get()
            ->map(function($q){
                $customerOwner = $q->promotionGoodRequest->createdBy->name;
                $customerNumber = null;
                $customerName = null;
                if ($q->promotionGoodRequest->event_id != null) {
                    if($q->promotionGoodRequest->event->dealer_id != null){
                        $customerNumber = "CUST-".$q->promotionGoodRequest->event->dealer->dealer_id;
                        $customerName = $q->promotionGoodRequest->event->dealer->prefix." ". $q->promotionGoodRequest->event->dealer->name." ".$q->promotionGoodRequest->event->dealer->sufix;
                    }elseif ($q->promotionGoodRequest->event->sub_dealer_id != null) {
                        $customerNumber = "CUST-SUB-".$q->promotionGoodRequest->event->subDealer->sub_dealer_id;
                        $customerName = $q->promotionGoodRequest->event->subDealer->prefix." ". $q->promotionGoodRequest->event->subDealer->name." ".$q->promotionGoodRequest->event->subDealer->sufix;
                    }
                    $customerOwner = $q->promotionGoodRequest->event->personel->name;
                }else{
                    $customerNumber = "-";
                    $customerName = "-";
                }
                return [
                    "id" => $q->id,
                    "number_dispatch" => $q->order_number,
                    "invoice" => "-",
                    "invoice_id" => "",
                    "customer_number" => $customerNumber,
                    "customer_name" => $customerName,
                    "customer_owner" => $customerOwner,
                    "province" => $q->addressDelivery->province->name,
                    "city" => $q->addressDelivery->city->name,
                    "district" => $q->addressDelivery->district->name,
                    "weight" => $q->total_weight,
                    "total_item" => $q->dispatchPromotionDetails->count()
                ];
            })->toArray();

        return array_merge($dispatchOrder, $dispatchPromotion);
    }
}
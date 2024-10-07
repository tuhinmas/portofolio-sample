<?php

namespace Modules\DistributionChannel\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DispatchList;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PromotionGood\Entities\DispatchPromotion;

class DispatchV2Repository {

    public function shippingList($params = [])
    {
        $query = DispatchList::with("driver","warehouse")
            ->select(
                'view_dispatch_list.*',
                DB::raw("
                    subquery.dispatch_count,
                    subquery.pickup_dispatch_count,
                    subquery.total_dispatch_order_weight,
                    subquery.warehouse_code,
                    subquery.warehouse_name,
                    subquery.sq_transportation_type
                ")
            )
            ->join(DB::raw("(
                SELECT
                    warehouses.code as warehouse_code,
                    warehouses.name as warehouse_name,
                    date_delivery as sq_date_delivery,
                    id_warehouse as sq_id_warehouse,
                    vd1.armada_identity_number as sq_armada_identity_number,
                    COUNT(*) AS dispatch_count,
                    SUM(dispatch_order_weight) AS total_dispatch_order_weight,
                    COUNT(pickup_orders.id) AS pickup_dispatch_count,
                    IF(drivers.id IS NOT NULL, drivers.transportation_type, vd1.transportation_type) AS sq_transportation_type
                FROM
                    view_dispatch_list as vd1
                    left join pickup_order_dispatches on pickup_order_dispatches.dispatch_id = vd1.id
                        and pickup_order_dispatches.deleted_at is NULL
                    left join pickup_orders on pickup_order_dispatches.pickup_order_id = pickup_orders.id
                        and pickup_orders.deleted_at is null
                        and pickup_orders.status not in ('canceled', 'received')
                    right join warehouses on warehouses.id = vd1.id_warehouse
                    left join drivers on drivers.id = vd1.id_armada and drivers.deleted_at is null
                GROUP BY
                    date_delivery,
                    id_warehouse,
                    vd1.armada_identity_number
            ) AS subquery"), function($q){
                $q->on("subquery.sq_date_delivery", "=", "view_dispatch_list.date_delivery")
                    ->whereColumn("subquery.sq_id_warehouse", "view_dispatch_list.id_warehouse")
                    ->whereColumn("subquery.sq_armada_identity_number", "view_dispatch_list.armada_identity_number");
            })
            ->whereNotNull("delivery_address_id")
            ->when(!empty($params['date_delivery']), function($q) use($params){
                $q->where("date_delivery", $params['date_delivery']);
            })->when(!empty($params['warehouse']), function($q) use($params){
                $q->whereHas("warehouse", function($q) use($params){
                    $q->where("code", 'like', '%'.$params['warehouse'].'%')->orWhere("name", 'like', '%'.$params['warehouse'].'%');
                });
            })->when(!empty($params['driver']), function($q) use($params){
                $q->where(function($q) use($params){
                    $q->whereHas("driver", function($q) use($params){
                        $q->where("police_number", 'like', '%'.$params['driver'].'%')->orWhere("transportation_type", 'like', '%'.$params['driver'].'%');
                    })->orWhere("armada_identity_number", "like", '%'.$params['driver'].'%');
                });
            })->when(!empty($params['dispatch_number']), function($q) use($params){
                $q->where(function($q) use($params){
                    $q->where("dispatch_order_number", "like", "%".$params["dispatch_number"]."%")->orWhere("order_number", "like", "%".$params["dispatch_number"]."%");
                });
            })->when(!empty($params['customer']), function($q) use($params){
                $q->where(function($q) use($params){
                   $q->where(function($q) use($params){
                        $q->whereHas("invoice.salesOrder.dealer", function($q) use($params){
                            $q->where("dealer_id", "like", "%".$params["customer"]."%")
                                ->orWhere("name", "like", "%".$params["customer"]."%")
                                ->orWhere("owner", "like", "%".$params["customer"]."%");
                        })->orWhereHas("invoice.salesOrder.subDealer", function($q) use($params){
                            $q->where("dealer_id", "like", "%".$params["customer"]."%")
                                ->orWhere("name", "like", "%".$params["customer"]."%")
                                ->orWhere("owner", "like", "%".$params["customer"]."%");
                        });
                   })->orWhere(function($q) use($params){
                        $q->whereHas("promotionGoodRequest.event.dealer", function($q) use($params){
                            $q->where("dealer_id", "like", "%".$params["customer"]."%")->orWhere("name", "like", "%".$params["customer"]."%");
                        })->orWhereHas("promotionGoodRequest.event.subDealer", function($q) use($params){
                            $q->where("sub_dealer_id", "like", "%".$params["customer"]."%")->orWhere("name", "like", "%".$params["customer"]."%");
                        })->orWhereHas("promotionGoodRequest.event.personel", function($q) use($params){
                            $q->where("name", "like", "%".$params["customer"]."%");
                        });
                   });
                });
            })->when(!empty($params['proforma_number']), function($q) use($params){
                $q->whereHas("invoice", function($q) use($params){
                    $q->where("invoice", "like", "%".$params['proforma_number']."%");
                });
            })->when(!empty($params['address_delivery']), function($q) use($params){
                $q->where(function($q) use($params){
                    $q->whereHas("addressDelivery.province", function($q) use($params){
                        $q->where("name", "like", "%".$params["address_delivery"]."%");
                    })->orWhereHas("addressDelivery.city", function($q) use($params){
                        $q->where("name", "like", "%".$params["address_delivery"]."%");
                    });
                });
            })->when(!empty($params['status']), function($q) use($params){
                $q->whereIn("status", $params["status"]);
            })->groupBy("date_delivery", "id_warehouse", "armada_identity_number");
            
            if (!empty($params['sort'])) {
                switch ($params['sort']['field']) {
                    // case 'warehouse_code':
                    //     $query->orderBy("wa", $params["sort"]["direction"]);
                    //     break;
    
                    case 'transportation_type':
                        $query->orderBy(DB::raw("subquery.sq_transportation_type IS NULL, subquery.sq_transportation_type"), $params["sort"]["direction"]);
                        break;

                    case 'police_number':
                        $query->orderBy("armada_identity_number", $params["sort"]["direction"]);
                        break;
                    
                    default:
                        $query->orderBy($params["sort"]["field"], $params["sort"]["direction"]);
                        break;
                }
            }

            if (!empty($params['disable_pagination'])) {
                return $query->get()->map(function($q){
                    return [
                        "id_armada" => $q->id_armada,
                        "armada_identity_number" => $q->armada_identity_number,
                        "date_delivery" => $q->date_delivery,
                        "id_warehouse" => $q->id_warehouse,
                        "dispatch_order_weight" => $q->total_dispatch_order_weight,
                        "warehouse_code" => $q->warehouse->code,
                        "warehouse_name" => $q->warehouse->name,
                        "transportation_type" => $q->driver->transportation_type ?? $q->transportation_type,
                        "type_driver" => $q->type_driver,
                        "police_number" => $q->driver->police_number ?? $q->armada_identity_number,
                        "total_dispatch_order_weight" => $q->total_dispatch_order_weight,
                        "can_pickup" => $q->dispatch_count == $q->pickup_dispatch_count ? false : true
                    ];
                });
            }
                
            return $query->paginate(20)->through(function($q){
                return [
                    "id_armada" => $q->id_armada,
                    "armada_identity_number" => $q->armada_identity_number,
                    "date_delivery" => $q->date_delivery,
                    "id_warehouse" => $q->id_warehouse,
                    "dispatch_order_weight" => $q->total_dispatch_order_weight,
                    "warehouse_code" => $q->warehouse->code,
                    "warehouse_name" => $q->warehouse->name,
                    "transportation_type" => $q->driver->transportation_type ?? $q->transportation_type,
                    "type_driver" => $q->type_driver,
                    "police_number" => $q->driver->police_number ?? $q->armada_identity_number,
                    "total_dispatch_order_weight" => $q->total_dispatch_order_weight,
                    "can_pickup" => $q->dispatch_count == $q->pickup_dispatch_count ? false : true
                ];
            });
    }
    
    public function shippingListDispatch($params = [])
    {
        $query = DispatchList::with(
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
        ->when(!empty($params['date_delivery']), function($q) use($params){
            $q->where("date_delivery", $params['date_delivery']);
        })->when(!empty($params['warehouse']), function($q) use($params){
            $q->whereHas("warehouse", function($q) use($params){
                $q->where("code", 'like', '%'.$params['warehouse'].'%')->orWhere("code", 'like', '%'.$params['warehouse'].'%');
            });
        })->when(!empty($params['driver']), function($q) use($params){
            $q->whereHas("driver", function($q) use($params){
                $q->where("police_number", 'like', '%'.$params['driver'].'%')->orWhere("transportation_type", 'like', '%'.$params['driver'].'%')
                ->orWhere("armada_identity_number", "like", '%'.$params['driver'].'%');
            });
        })->when(!empty($params['dispatch_number']), function($q) use($params){
            $q->where(function($q) use($params){
                $q->where("dispatch_order_number", "like", "%".$params["dispatch_number"]."%")->orWhere("order_number", "like", "%".$params["dispatch_number"]."%");
            });
        })->when(!empty($params['customer']), function($q) use($params){
            $q->where(function($q) use($params){
               $q->where(function($q) use($params){
                    $q->whereHas("invoice.salesOrder.dealer", function($q) use($params){
                        $q->where("dealer_id", "like", "%".$params["customer"]."%")
                            ->orWhere("name", "like", "%".$params["customer"]."%")
                            ->orWhere("owner", "like", "%".$params["customer"]."%");
                    })->orWhereHas("invoice.salesOrder.subDealer", function($q) use($params){
                        $q->where("dealer_id", "like", "%".$params["customer"]."%")
                            ->orWhere("name", "like", "%".$params["customer"]."%")
                            ->orWhere("owner", "like", "%".$params["customer"]."%");
                    });
               })->orWhere(function($q) use($params){
                    $q->whereHas("promotionGoodRequest.event.dealer", function($q) use($params){
                        $q->where("dealer_id", "like", "%".$params["customer"]."%")->orWhere("name", "like", "%".$params["customer"]."%");
                    })->orWhereHas("promotionGoodRequest.event.subDealer", function($q) use($params){
                        $q->where("sub_dealer_id", "like", "%".$params["customer"]."%")->orWhere("name", "like", "%".$params["customer"]."%");
                    })->orWhereHas("promotionGoodRequest.event.personel", function($q) use($params){
                        $q->where("name", "like", "%".$params["customer"]."%");
                    });
               });
            });
        })->when(!empty($params['proforma_number']), function($q) use($params){
            $q->whereHas("invoice", function($q) use($params){
                $q->where("invoice", "like", "%".$params['proforma_number']."%");
            });
        })->when(!empty($params['address_delivery']), function($q) use($params){
            $q->where(function($q) use($params){
                $q->whereHas("addressDelivery.province", function($q) use($params){
                    $q->where("name", "like", "%".$params["address_delivery"]."%");
                })->orWhereHas("addressDelivery.city", function($q) use($params){
                    $q->where("name", "like", "%".$params["address_delivery"]."%");
                });
            });
        })->when(!empty($params['status']), function($q) use($params){
            $q->whereIn("status", $params["status"]);
        })
        ->get()
        ->map(function($q) {
            if ($q->dispatch_type == "dispatch_order") {
                return [
                    "id" => $q->id,
                    "number_dispatch" => $q->dispatch_order_number,
                    "number_dispatch_order" => $q->order_number,
                    "invoice" => $q->invoice?->invoice,
                    "invoice_id" => $q->invoice?->id,
                    "customer_number" => $q->invoice->salesOrder->dealer ? "CUST-".$q->invoice->salesOrder->dealer->dealer_id : "CUST-SUB-".$q->invoice->salesOrder->subDealer->dealer_id,
                    "customer_name" => $q->invoice->salesOrder->dealer ? 
                        $q->invoice->salesOrder->dealer->prefix." ". $q->invoice->salesOrder->dealer->name." ".$q->invoice->salesOrder->dealer->sufix
                        : $q->invoice->salesOrder->subDealer->prefix. " ".$q->invoice->salesOrder->subDealer->name." ".$q->invoice->salesOrder->subDealer->sufix,
                    "customer_owner" => $q->invoice->salesOrder->dealer ? 
                        $q->invoice->salesOrder->dealer->owner
                        : $q->invoice->salesOrder->subDealer->owner,
                    "province" => $q->addressDelivery->province->name,
                    "city" => $q->addressDelivery->city->name,
                    "district" => $q->addressDelivery->district->name,
                    "weight" => $q->total_weight,
                    "total_item" => $q->dispatchOrder->dispatchOrderDetail->count(),
                    "date_delivery" => $q->date_delivery,
                    "status" => $q->status,
                    "delivery_order" => $q->deliveryOrder,
                    "receiving_good" => $q->deliveryOrder?->receivingGoodHasReceived,
                    "can_cancelled" => $q->dispatchOrder->can_cancelled,
                    "is_pickuped" => $q->pickupDispatchPickuped->count() > 0 || $q->status == "canceled" ? true : false
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

                return [
                    "id" => $q->id,
                    "number_dispatch" => $q->dispatch_order_number,
                    "number_dispatch_order" => $q->order_number,
                    "invoice" => "-",
                    "invoice_id" => null,
                    "customer_number" => $customerNumber,
                    "customer_name" => $customerName,
                    "customer_owner" => $customerOwner,
                    "province" => $q->addressDeliveryPromotion->province->name,
                    "city" => $q->addressDeliveryPromotion->city->name,
                    "district" => $q->addressDeliveryPromotion->district->name,
                    "weight" => $q->dispatch_order_weight,
                    "total_item" => $q->dispatchPromotion->dispatchPromotionDetails->count(),
                    "date_delivery" => $q->date_delivery,
                    "status" => $q->status,
                    "delivery_order" => $q->deliveryOrderPromotion,
                    "receiving_good" => $q->deliveryOrderPromotion?->receivingGoodHasReceived,
                    "can_cancelled" => $q->dispatchPromotion->can_cancelled,
                    "is_pickuped" => $q->pickupDispatchPickuped->count() > 0 || $q->status == "canceled" ? true : false
                ];
            }
        });
        
        if (!empty($params["sort"])) {
            if ($params['sort']['direction'] == 'desc') {
                $sortedResults = $query->sortByDesc($params['sort']['field']);
                $sortedResults = $sortedResults->values();
            }else{
                $sortedResults = $query->sortBy($params['sort']['field']);
                $sortedResults = $sortedResults->values();
            }            
            return $sortedResults;
        }

        return $query;
    }

    public function updateDispatch($data, $dispatchId)
    {
        $dispatchOrder = DispatchOrder::find($dispatchId);
        $update = $data;
        if (!empty($data['status'])) {
            if ($data["status"] == "canceled") {
                $update = [
                    "status" => "canceled",
                    "is_active" => 0,
                    "dispatch_note" => $data["dispatch_note"] ?? null
                ];
            }
        }

        if (!empty($data['is_active'])) {
            if ($data["is_active"] == 0) {
                $update = [
                    "status" => "canceled",
                    "is_active" => 0,
                    "dispatch_note" => $data["dispatch_note"] ?? null
                ];
            }
        }

        if ($dispatchOrder) {
            $dispatchOrder->update($update);
        }else{
            DispatchPromotion::where("id", $dispatchId)->update($update);
        }
    }

    public function detailDispatch($data, $dispatchId)
    {
        $dispatchOrder = DispatchOrder::find($dispatchId);
        if ($dispatchOrder) {
            return DispatchOrder::with(
                "receipt",
                "invoice",
                "invoice.salesOrder.dealer",
                "invoice.salesOrder.subDealer",
                "warehouse",
                "deliveryOrder.receivingGoodHasReceived",
                "warehouse.province",
                "warehouse.city",
                "warehouse.district",
                "driver",
                "dispatchOrderDetail",
                "dispatchOrderDetail.product",
                "addressDelivery.province",
                "addressDelivery.city",
                "addressDelivery.district",
            )
            ->find($dispatchId)->append("can_cancelled");
        }else{
            return DispatchPromotion::with(
                "warehouse",
                "deliveryOrder.receivingGoodHasReceived",
                "warehouse.province",
                "warehouse.city",
                "warehouse.district",
                "driver",
                "dispatchPromotionDetails",
                "dispatchPromotionDetails.promotionGood.product",
                "addressDelivery.province",
                "addressDelivery.city",
                "addressDelivery.district",
                "promotionGoodRequest.event.dealer",
                "promotionGoodRequest.event.subDealer",
                "promotionGoodRequest.event.personel"
            )->find($dispatchId)->append("can_cancelled");
        }
    }

    public function generateDispatchPromotion()
    {
        $dispatchPromotion = DispatchPromotion::with("warehouse")->orderBy("created_at", "asc")->get();
        $no = 0;
        foreach ($dispatchPromotion as $key => $value) {
    
            $angkaRomawi = [
                '01' => 'I',
                '02' => 'II',
                '03' => 'III',
                '04' => 'IV',
                '05' => 'V',
                '06' => 'VI',
                '07' => 'VII',
                '08' => 'VIII',
                '09' => 'IX',
                '10' => 'X',
                '11' => 'XI',
                '12' => 'XII'
            ];

            $no +=1;
            $year = date("Y", strtotime($value->created_at));
            $month = date("m", strtotime($value->created_at));
            $string = "BP";
            $warehouse = $value->warehouse->code;

            $code = $year."/".$string."-".$warehouse."-".$angkaRomawi[$month]."/".$no;
            $value->update([
                "order_number" => $no,
                "dispatch_order_number" => $code
            ]);
        }
    }

}
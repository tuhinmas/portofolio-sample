<?php

namespace Modules\DistributionChannel\Http\Controllers\V2;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchList;
use Modules\DistributionChannel\Repositories\DispatchV2Repository;

class DeliveryOrderController extends Controller
{
    use ResponseHandlerV2;

    public function __invoke(Request $request)
    {
        try {
            $query = DB::table('view_delivery_order')
                ->when($request->has("invoice") && $request->invoice != "", function($q) use($request){
                    $q->where("invoice", "like", "%".$request->invoice."%");
                })
                ->when($request->has("delivery_order_number") && $request->delivery_order_number != "", function($q) use($request){
                    $q->where("delivery_order_number", "like", "%".$request->delivery_order_number."%");
                })
                ->when($request->has("date_delivery") && $request->date_delivery != "", function($q) use($request){
                    $q->whereDate("date_delivery", $request->date_delivery);
                })
                ->when($request->has("dispatch_number") && $request->dispatch_number != "", function($q) use($request){
                    $q->where("dispatch_number", "like", "%".$request->dispatch_number."%");
                })
                ->when($request->has("customer") && $request->customer != "", function($q) use($request){
                    $q->where(function($q) use($request){
                        $q->where("full_name_dealer", "like", "%".$request->customer."%")->orWhere("id_dealer", "like", "%".$request->customer."%");
                    });
                })
                ->when($request->has("pickup_number") && $request->pickup_number != "", function($q) use($request){
                    $q->where("pickup_number", "like", "%".$request->pickup_number."%");
                })
                ->when($request->has("personel_branch") && $request->personel_branch != "", function($q) use($request){
                    $marketing_on_branch = personel_branch($request->personel_branch);
                    $personel_dealer = DB::table('dealers')->whereNull("deleted_at")->whereIn("personel_id", $marketing_on_branch)->pluck("personel_id");
                    $sales_orders_id = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $personel_dealer)->pluck("id");
                    $q->whereIn("dispatch_orders.sales_order_id", $sales_orders_id);
                })
                ->when($request->has("status") && $request->status != "", function($q) use($request){
                    $q->where(function($q) use($request){
                        foreach ($request->status as $value) {
                            switch ($value) {
                                case 'received':
                                    $q->orWhere(function($q){
                                        $q->where("status", "send")->whereNotNull("max_date_received");
                                    });
                                    break;
    
                                case 'not-yet-received':
                                    $q->orWhere(function($q){
                                        $q->where("status", "send")->whereNull("max_date_received");
                                    });
                                    break;
                                    
                                case 'canceled':
                                    $q->orWhere("status", "canceled");
                                    break;
                                    
                                case 'failed':
                                    $q->orWhere("status", "failed");
                                    break;
                            }
                        }
                    });
                });

            if ($request->order_by != "") {
                switch ($request->order_by['field']) {
                    case 'delivery_order_number':
                        $query->orderBy("delivery_order_number", $request->order_by['direction']);
                        break;
                        
                    case 'dispatch_number':
                        $query->orderBy("dispatch_number", $request->order_by['direction']);
                        break;
                        
                    case 'invoice':
                        $query->orderBy("invoice", $request->order_by['direction']);
                        break;
                        
                    case 'date_delivery':
                        $query->orderBy("date_delivery", $request->order_by['direction']);
                        break;
                            
                    case 'dealer_id':
                        $query->orderBy("dealer_id", $request->order_by['direction']);
                        break;
                        
                    case 'district_name':
                        $query->orderBy("district_name", $request->order_by['direction']);
                        break;

                    case 'personel':
                        $query->orderBy("personel", $request->order_by['direction']);
                        break;
                }
            }

            if ($request->has('limit')) {
                $response = $query->paginate($request->limit)->through(function($q){
                    $data = [
                        'id' => $q->id,
                        "delivery_order_number" => $q->delivery_order_number,
                        "dispatch_number" => $q->dispatch_number,
                        "invoice" => $q->invoice,
                        "date_delivery" => $q->date_delivery,
                        "customer" => [
                            "cust_id" => $q->id_dealer,
                            "name" => $q->full_name_dealer,
                            "owner" => $q->owner
                        ],
                        "area_customer" => [
                            "district" => $q->district_name,
                            "city" => $q->city_name,
                            "province" => $q->province_name
                        ],
                        "personel" => $q->type_dispatch == "dispatch_order" ? 
                            [
                                "name" => $q->dispatch_order_personel_name,
                                "position" => $q->dispatch_order_position_name
                            ] : 
                            [
                                "name" => $q->promotion_created_name,
                                "position" => $q->promotion_created_position_name
                            ],
                        "status" => $q->status,
                        "date_received" => $q->max_date_received,
                        "delivery_location" => $q->delivery_location,
                        "dispatch_order_id" => $q->dispatch_order_id,
                        "dispatch_promotion_id" => $q->dispatch_promotion_id,
                        "receiving_good_id" => $q->receiving_good_id,
                    ];
                    return $data;
                });
            }else{
                $response = $query->get()->map(function($q){
                    $data = [
                        'id' => $q->id,
                        "delivery_order_number" => $q->delivery_order_number,
                        "dispatch_number" => $q->dispatch_number,
                        "invoice" => $q->invoice,
                        "date_delivery" => $q->date_delivery,
                        "customer" => [
                            "cust_id" => $q->id_dealer,
                            "name" => $q->full_name_dealer,
                            "owner" => $q->owner
                        ],
                        "area_customer" => [
                            "district" => $q->district_name,
                            "city" => $q->city_name,
                            "province" => $q->province_name
                        ],
                        "personel" => $q->type_dispatch == "dispatch_order" ? 
                            [
                                "name" => $q->dispatch_order_personel_name,
                                "position" => $q->dispatch_order_position_name
                            ] : 
                            [
                                "name" => $q->promotion_created_name,
                                "position" => $q->promotion_created_position_name
                            ],
                        "status" => $q->status,
                        "date_received" => $q->max_date_received,
                        "delivery_location" => $q->delivery_location,
                        "dispatch_order_id" => $q->dispatch_order_id,
                        "dispatch_promotion_id" => $q->dispatch_promotion_id,
                        "receiving_good_id" => $q->receiving_good_id,
                    ];
                    return $data;
                });
            }

            return $this->response('00', 'Success', $response);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function queryDeliveryOrder()
    {
        /*
            ** how to execute update query **

            -- php artisan tinker
            -- app(\Modules\DistributionChannel\Http\Controllers\V2\DeliveryOrderController::class)->queryDeliveryOrder();

        */
        DB::statement("DROP VIEW IF EXISTS view_delivery_order");
        DB::statement("
            create VIEW view_delivery_order AS (
                select
                    delivery_orders.id,
                    delivery_orders.delivery_order_number,
                    delivery_orders.date_delivery,
                    delivery_orders.status,
                    receiving_goods.max_date_received,
                    CASE
                        WHEN delivery_orders.dispatch_order_id is not null then 'dispatch_order'
                        ELSE 'dispatch_promotion'
                    END as type_dispatch,
                    CASE
                        WHEN delivery_orders.dispatch_order_id is not null then dispatch_orders.dispatch_order_number
                        ELSE dispatch_promotions.promotion_dispatch_order_number
                    END as dispatch_number,
                    delivery_orders.created_at,
                    delivery_orders.updated_at,
                    dispatch_promotions.*,
                    dispatch_orders.*,
                    pickup.pickup_number,
                    CASE
                        WHEN delivery_orders.dispatch_order_id is not null then dispatch_orders.dispatch_order_personel_name
                        ELSE dispatch_promotions.promotion_created_name
                    END as personel,
                    receiving_goods.receiving_good_id
                from
                    delivery_orders
                    left join (
                        select
                            dispatch_promotions.id as dispatch_promotion_id,
                            personels.name as promotion_created_name,
                            positions.name as promotion_created_position_name,
                            dispatch_promotions.dispatch_order_number as promotion_dispatch_order_number,
                            indonesia_provinces.name as promotion_delivery_province_name,
                            indonesia_cities.`name` as promotion_delivery_city_name,
                            indonesia_districts.name as promotion_delivery_district_name
                        from
                            dispatch_promotions
                            right join promotion_good_requests on promotion_good_requests.id = dispatch_promotions.promotion_good_request_id
                            and promotion_good_requests.deleted_at is null
                            left join personels on personels.id = promotion_good_requests.created_by
                            and personels.deleted_at is NULL
                            left join positions on positions.id = personels.position_id
                            and positions.deleted_at is null
                            left join dispatch_promotion_delivery_addresses on dispatch_promotion_delivery_addresses.id = dispatch_promotions.delivery_address_id
                            and dispatch_promotion_delivery_addresses.deleted_at is null
                            left join indonesia_provinces on indonesia_provinces.id = dispatch_promotion_delivery_addresses.province_id
                            left join indonesia_cities on indonesia_cities.id = dispatch_promotion_delivery_addresses.city_id
                            left join indonesia_districts on indonesia_districts.id = dispatch_promotion_delivery_addresses.district_id
                    ) as dispatch_promotions on dispatch_promotions.dispatch_promotion_id = delivery_orders.dispatch_promotion_id
                    left join (
                        select
                            discpatch_order.id as dispatch_order_id,
                            invoices.invoice,
                            invoices.id as invoice_id,
                            view_dealer_list.id_dealer,
                            view_dealer_list.dealer_id,
                            view_dealer_list.full_name_dealer,
                            view_dealer_list.`owner`,
                            view_dealer_list.district_name,
                            view_dealer_list.city_name,
                            view_dealer_list.province_name,
                            personels.`name` as dispatch_order_personel_name,
                            positions.`name` as dispatch_order_position_name,
                            sales_orders.delivery_location,
                            discpatch_order.dispatch_order_number
                        from
                            discpatch_order
                            left join invoices on invoices.id = discpatch_order.invoice_id
                            and invoices.deleted_at is null
                            right join sales_orders on sales_orders.id = invoices.sales_order_id
                            and sales_orders.deleted_at is null
                            join view_dealer_list on view_dealer_list.id = sales_orders.store_id
                            left join personels on personels.id = sales_orders.personel_id
                            and personels.deleted_at is NULL
                            left join positions on positions.id = personels.position_id
                            and positions.deleted_at is null
                        where
                            discpatch_order.deleted_at is null
                    ) as dispatch_orders on dispatch_orders.dispatch_order_id = delivery_orders.dispatch_order_id
                    left join (
                        SELECT
                            receiving_goods.id as receiving_good_id,
                            receiving_goods.delivery_order_id,
                            MAX(receiving_goods.date_received) AS max_date_received
                        FROM
                            receiving_goods
                        WHERE
                            receiving_goods.delivery_status = 2 GROUP BY receiving_goods.delivery_order_id
                    ) as receiving_goods on receiving_goods.delivery_order_id = delivery_orders.id
                    left join (
                        select
                            pickup_orders.pickup_number,
                            pickup_order_dispatches.dispatch_id
                        from
                            pickup_order_dispatches
                            right join pickup_orders on pickup_orders.id = pickup_order_dispatches.pickup_order_id
                            and pickup_orders.deleted_at is null
                        where
                            pickup_order_dispatches.deleted_at is null
                    ) as pickup on pickup.dispatch_id = delivery_orders.dispatch_order_id
                    or pickup.dispatch_id = delivery_orders.dispatch_promotion_id
            )
        ");
    }
}
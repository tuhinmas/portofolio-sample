<?php

namespace Modules\SalesOrder\Http\Controllers\DirectSales;


use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\SalesOrder\Entities\v2\SalesOrder;

class CanceledOrderController extends Controller
{
    use ResponseHandlerV2;

    public function __invoke(Request $request)
    {
        try {
            $query = SalesOrder::query()
                ->rightJoin('dealers', 'dealers.id', '=', 'sales_orders.store_id')->whereNull("dealers.deleted_at")
                ->rightJoin('personels', 'personels.id', '=', 'sales_orders.personel_id')->whereNull("personels.deleted_at")
                ->rightJoin('positions', 'positions.id', '=', 'personels.position_id')->whereNull("positions.deleted_at")
                ->select(
                    "sales_orders.id",
                    "sales_orders.order_number",
                    "sales_orders.created_at",
                    "sales_orders.store_id",
                    "sod.*",
                    "dealers.name",
                    DB::raw("
                        CONCAT(
                            COALESCE(dealers.prefix, ''),
                            IF(dealers.prefix IS NULL, '', '. '),
                            dealers.name,
                            COALESCE(dealers.sufix, '')
                        ) AS full_name_dealer
                    "),
                    DB::raw("CONCAT('CUST-', dealers.dealer_id) AS dealer_id"),
                    "dealers.owner",
                    "sales_orders.personel_id",
                    "personels.name as personel_name",
                    "positions.name as position_name",
                    "sohcs.*",
                    "sales_orders.status"
                )
                ->where("type", 1)
                ->where("sales_orders.status", "canceled");

            $query->leftJoin(DB::raw("
                (
                    select sales_order_details.sales_order_id as sod_sales_order_id, sum(quantity*unit_price) as nominal_order
                    from sales_order_details
                    where sales_order_details.deleted_at is null
                    group by sales_order_id
                ) as sod
            "), "sod.sod_sales_order_id", "=", "sales_orders.id");

            $query->join(DB::raw("
                (
                    SELECT 
                        sohcs.sales_order_id AS sohcs_sales_order_id,
                        sohcs.created_at AS canceled_at,
                        personels.name AS canceled_name,
                        positions.name AS canceled_position,
                        sohcs.status as status_history
                    FROM sales_order_history_change_statuses AS sohcs
                    LEFT JOIN personels ON personels.id = sohcs.personel_id AND personels.deleted_at IS NULL
                    LEFT JOIN positions ON positions.id = personels.position_id AND positions.deleted_at IS NULL
                    WHERE sohcs.deleted_at IS NULL
                    AND sohcs.created_at = (
                        SELECT MAX(inner_sohcs.created_at)
                        FROM sales_order_history_change_statuses AS inner_sohcs
                        WHERE inner_sohcs.sales_order_id = sohcs.sales_order_id
                            AND inner_sohcs.deleted_at IS NULL
                    )
                ) as sohcs
            "), "sohcs.sohcs_sales_order_id", "=", "sales_orders.id")
            ->when($request->has("order_date") && $request->order_date != '', function($q) use($request){
                $q->whereDate("sales_orders.created_at", $request->order_date);
            })->when($request->has("order_number") && $request->order_number != '', function($q) use($request){
                $q->where("order_number", "like", "%".$request->order_number."%");
            })->when($request->has('dealer') && $request->dealer != '', function ($q) use ($request) {
                $q->where(function($q) use($request){
                    $q->where("dealers.prefix", "like", "%".$request->dealer."%")
                    ->orWhere("dealers.name", "like", "%".$request->dealer."%")
                    ->orWhere("dealers.sufix", "like", "%".$request->dealer."%")
                    ->orWhere("dealers.dealer_id", "like", "%".$request->dealer."%");
                });
            })->when($request->has('personel') && $request->personel != '', function ($q) use ($request) {
                $q->where("personels.name", 'like', '%' . $request->personel . '%');
            })->when($request->has('canceled_date') && $request->canceled_at != '', function ($q) use ($request) {
                $q->where("sohcs.canceled_at", 'like', '%' . $request->canceled_at . '%');
            });

            if ($request->has("order_by")) {
                switch ($request->order_by['field']) {
                    case 'order_number':
                        $query->orderBy("order_number", $request->order_by['direction']);
                        break;

                    case 'created_at':
                        $query->orderBy("created_at", $request->order_by['direction']);
                        break;

                    case 'dealer':
                        $query->orderBy("dealers.dealer_id", $request->order_by['direction']);
                        break;
                        
                    case 'nominal_order':
                        $query->orderBy("sod.nominal_order", $request->order_by['direction']);
                        break;
                        
                    case 'personel':
                        $query->orderBy("personels.name", $request->order_by['direction']);
                        break;
                        
                    case 'canceled_at':
                        $query->orderBy("sohcs.canceled_at", $request->order_by['direction']);
                        break;
                    
                    case 'canceled_name':
                        $query->orderBy("sohcs.canceled_name", $request->order_by['direction']);
                        break;
                    
                    default:
                        $query->orderBy("order_number", 'desc');
                        break;
                }
            }

            if ($request->has("limit")) {
                $response = $query->paginate($request->limit);
            }else{
                $response = $query->get();
            }

            return $this->response("00", "Success", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}

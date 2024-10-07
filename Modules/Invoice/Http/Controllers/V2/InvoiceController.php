<?php

namespace Modules\Invoice\Http\Controllers\V2;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\InvoiceV2;
use Modules\PersonelBranch\Entities\PersonelBranch;

class InvoiceController extends Controller
{
    use ResponseHandler;

    public function list(Request $request)
    {
        try {
            $baseQuery = InvoiceV2::query();
            
            $baseQuery->select(
                "id",
                "invoice",
                "sales_order_id",
                "date_delivery",
                "cust.*",
                DB::raw("
                    (
                        CASE 
                            WHEN delivery_status = 1 then 'done'
                            WHEN delivery_status = 2 && (
                                 select 
                                    count(id) as total_dispatch
                                from discpatch_order
                                where discpatch_order.deleted_at is null
                                and discpatch_order.invoice_id = invoices.id
                            ) > 0 then 'issued'
                            ELSE 'planned'
                        END 
                    ) as status
                ")
            );
            
            $querySalesOrder = DB::table('sales_orders')
                ->select(
                    'sales_orders.id as cust_id',
                    'sales_orders.store_id',
                    'dealers.prefix',
                    'dealers.name',
                    'dealers.sufix',
                    'dealers.dealer_id',
                    DB::raw("
                          (
                            SELECT SUM(
                                CASE 
                                    WHEN sod.quantity_on_package IS NOT NULL THEN sod.package_weight * (sod.quantity / sod.quantity_on_package)
                                    WHEN sod.quantity_on_package IS NULL THEN p.weight * sod.quantity
                                    ELSE sod.package_weight * sod.quantity
                                END
                            ) AS total_weight
                            FROM sales_order_details sod
                            LEFT JOIN products p ON p.id = sod.product_id
                            WHERE sod.sales_order_id = sales_orders.id
                            AND sod.deleted_at is null
                        ) AS total_weight
                    "),
                    DB::raw("count(sales_order_details.id) as total_item")
                )
                ->rightJoin('dealers', function ($join) {
                    $join->on('dealers.id', '=', 'sales_orders.store_id')->whereNull('dealers.deleted_at');
                })
                ->leftJoin("sales_order_details", function($join){
                    $join->on("sales_order_details.sales_order_id", "=", "sales_orders.id")->whereNull("sales_order_details.deleted_at");
                })
                ->whereNull("sales_orders.deleted_at")
                ->groupBy('sales_orders.id');

            $baseQuery->leftJoinSub($querySalesOrder, 'cust', function ($join) {
                $join->on('cust.cust_id', '=', 'invoices.sales_order_id');
            });

            if ($request->has("status")) {
                $baseQuery = DB::table(DB::raw("({$baseQuery->toSql()}) as filtered_invoices"))
                    ->mergeBindings($baseQuery->getQuery()) // Merge bindings for the raw query
                    ->select('*')
                    ->whereIn('status', $request->status);
            }

            if ($request->has("invoice") && $request->invoice != "") {
                $baseQuery->where("invoice", "like", "%".$request->invoice."%");
            }

            if ($request->has("date_delivery") && $request->date_delivery != "") {
                $baseQuery->whereDate("date_delivery", $request->date_delivery);
            }

            if ($request->has("cust") && $request->cust != "") {
                $baseQuery->where(function($q) use($request){
                    $q->where("prefix", "like", "%".$request->cust."%")
                        ->orWhere("name", "like", "%".$request->cust."%")
                        ->orWhere("sufix", "like", "%".$request->cust."%")
                        ->orWhere("dealer_id", "like", "%".$request->cust."%");
                });
            }

            if ($request->has("personel_branch") && $request->personel_branch != "") {
                $marketing_on_branch = personel_branch($request->personel_branch);
                $personel_dealer = DB::table('dealers')->whereNull("deleted_at")->whereIn("personel_id", $marketing_on_branch)->pluck("personel_id");
                $sales_orders_id = DB::table('sales_orders')->whereNull("deleted_at")->whereIn("personel_id", $personel_dealer)->pluck("id");
                $baseQuery->whereIn("sales_order_id", $sales_orders_id);
            }


            if ($request->has("order_by")) {
                $baseQuery->orderBy($request->order_by['field'], $request->order_by['direction']);
            }

            if ($request->has("limit") && $request->limit != "") {
                $response = $baseQuery->paginate($request->limit);
            }else{
                $response = $baseQuery->get();
            }
            

            return $this->response('00', 'Success', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'Failed', $th->getMessage());
        }
    }

}
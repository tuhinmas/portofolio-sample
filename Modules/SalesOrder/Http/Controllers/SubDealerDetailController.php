<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Filters\StatusFilter;
use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;
use Pricecurrent\LaravelEloquentFilters\EloquentFilters;

class SubDealerDetailController extends Controller
{
    use ResponseHandler;

    public function __construct(SalesOrder $sales_order)
    {
        $this->sales_order = $sales_order;
    }

    /**
     * group sales order by month and year
     * @param [type] $store_id
     * @return void
     */
    public function salesOrderGroupByStoreYearly(Request $request, $store = null)
    {
        try {
            $fiveYearsAgo = Carbon::now()->subYears(5);

            if ($request->has("year")) {
                $fiveYearsAgo = $date = Carbon::createFromDate($request->year, 1, 1)->subYears(5);
            }

            $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];
            $data = $this->sales_order->query()
                ->where('store_id', $store)
                ->whereIn('status', considered_order_status_for_recap())
                ->where("model", "2")
                ->where("type", "2")
                ->consideredOrderFromYear($fiveYearsAgo)
                ->consideredOrderToYear($request->year ?? now()->year)
                ->get();

            $indirect_grouped = $data->groupBy([
                function ($order) {return confirmation_time($order)->format('Y');},
                function ($order) {return confirmation_time($order)->format('M');},
            ]);

            $indirect = [];

            if ($request->has("year")) {
                for ($i = 4; $i >= 0; $i--) {
                    $indirect[Carbon::createFromDate($request->year, 1, 1)->subYears($i)->format("Y")] = $month;
                }
            } else {
                for ($i = 4; $i >= 0; $i--) {
                    $indirect[Carbon::now()->subYears($i)->format("Y")] = $month;
                }
            }

            foreach ($indirect_grouped as $year => $value) {
                foreach ($value as $monthOnYear => $val) {
                    $indirect[$year][$monthOnYear] = collect($val)->sum('total');
                }
            }
            return $this->response("00", "direct sales recap on 5 years", $indirect);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to recap sales on 5 years", $th->getMessage());
        }
    }

    /**
     * group sales order by month and year
     * @param [type] $store_id, $date
     * @return void
     */
    public function indirectSaleListOnSubDelaerDetail(Request $request, $id = null)
    {
        $status_filter = EloquentFilters::make([new StatusFilter(["confirmed"])]);
        try {
            $data = $this->sales_order
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("order_type")) {
                        $sort_type = $request->order_type;
                    }
                    if ($request->sorting_column == 'marketing_name') {
                        return $QQQ->orderBy(Personel::select('name')->whereColumn('personels.id', 'sales_orders.personel_id'), $request->order_type);
                    } else if ($request->sorting_column == 'distibutor_name') {
                        return $QQQ->orderBy(DealerV2::select('name')->whereColumn('dealers.id', 'sales_orders.distributor_id'), $request->order_type);
                    } else {
                        return $QQQ->orderBy($request->sorting_column, $sort_type);
                    }
                })
                ->with("distributor", "personel", "subDealer")
                ->where("model", "2")
                ->where("type", "2")
                ->where('store_id', $id)
                ->filter($status_filter)
                ->consideredOrder()

            /* filter by year*/
                ->when($request->has("year"), function ($q) use ($request) {
                    return $q->yearOfNota($request->year);
                })

            /* default result if there has no year parameter */
                ->when(!$request->has("year"), function ($q) {
                    return $q->yearOfNota(now()->year);

                })
            // ->orderBy('sales_orders.created_at', 'desc')
                ->paginate($request->limit ? $request->limit : 20);

            return $this->response("00", "invoice list/sales order per dealer on specific range", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get invoice list per dealer", $th->getMessage());
        }
    }
}

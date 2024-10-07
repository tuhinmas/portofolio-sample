<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Transformers\SalesOrderCollectionResource;
use Modules\SalesOrder\Entities\SalesOrder;

class MarketingSalesController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        SalesOrder $sales_order,
        Personel $personel
    ) {
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    public function __invoke(Request $request, $personel_id)
    {
        $this->personel->findOrFail($personel_id);
        $request->validate([
            "disabled_pagination" => "boolean",
            "payment_status" => [
                "required",
                "array",
            ],
            "year" => "required",
            "month" => "required",
            "by_distributor" => "boolean",
            "by_retailer" => "boolean",
        ]);

        try {
            $start_date = CarbonImmutable::parse($request->year . "-" . $request->month)->startOfMonth();
            $end_date = $start_date->endOfMonth();

            $sales_orders = $this->sales_order->query()
                ->with([
                    "invoice" => function ($QQQ) {
                        return $QQQ->with([
                            "payment",
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "distributorCOntract",
                            "ditributorContract",
                        ]);
                    },
                    "subDealer",
                ])
                ->where("personel_id", $personel_id)
                ->considerOrderStatusForRecap()
                ->salesOrderBetweenToDate($start_date->format("Y-m-d"), $end_date->format("Y-m-d"))
                ->when($request->type, function ($QQQ) use ($request) {
                    return $QQQ->whereIn("type", $request->type);
                })
                ->when($request->payment_status, function ($QQQ) use ($request) {
                    return $QQQ->bySettle($request->payment_status);
                })
                ->when($request->by_distributor, function ($QQQ) use ($start_date, $end_date) {
                    return $QQQ->salesToDistributorBetweenToDate($start_date->format("Y-m-d"), $end_date->format("Y-m-d"), "distributor");
                })
                ->when($request->by_retailer, function ($QQQ) use ($start_date, $end_date) {
                    return $QQQ->salesToDistributorBetweenToDate($start_date->format("Y-m-d"), $end_date->format("Y-m-d"), "retailer");
                });

            if ($request->disabled_pagination) {
                $sales_orders = $sales_orders->get();
            } else {
                $sales_orders = $sales_orders->paginate($request->limit ? $request->limit : 10);
            }
            return new SalesOrderCollectionResource($sales_orders);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}

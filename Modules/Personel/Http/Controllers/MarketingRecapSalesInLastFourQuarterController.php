<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\DistributorTrait;
use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;

class MarketingRecapSalesInLastFourQuarterController extends Controller
{
    use ResponseHandlerV2;
    use DistributorTrait;

    public function __construct(SalesOrder $sales_order, Personel $personel)
    {
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    public function __invoke(Request $request, $personel_id): JsonResponse
    {
        $this->personel->findOrFail($personel_id);
        $quarter_first = Carbon::now()->subQuarter(3)->startOfQuarter();

        try {
            $sales_orders = $this->sales_order->query()
                ->with([
                    "invoiceOnly" => function ($QQQ) use ($quarter_first, $request) {
                        return $QQQ->with([
                            "payment",
                        ]);
                    },
                    "invoice",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                ])
                ->consideredStatusConfirmedReturnedPending($quarter_first)
                ->where("personel_id", $request->personel_id)

            /**
             * filter type
             */
                ->when($request->type, function ($QQQ) use ($request) {
                    return $QQQ->whereIn("type", $request->type);
                })
                ->get()
                ->filter(function ($order) use ($request) {

                    /* check order is inside contract */
                    if ($request->by_distributor) {
                        if ($this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else if ($request->by_retailer) {
                        if (!$this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else {
                        return $order;
                    }
                })
                ->sum(function ($order) {
                    if ($order->type == "2") {
                        return $order->total;
                    }
                    return $order->invoice->total;
                });

            return $this->response("00", "success", $sales_orders);
        } catch (\Throwable $th) {
            return $this->response("00", "success", $th, 500);
        }
    }
}

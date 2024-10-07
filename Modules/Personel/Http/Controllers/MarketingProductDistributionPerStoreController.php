<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class MarketingProductDistributionPerStoreController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        SalesOrderDetail $sales_order_detail
    ) {
        $this->sales_order_detail = $sales_order_detail;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "personel_id" => "required",
            "year" => "required",
            "product_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalida data send", $validator->errors(), 422);
        }

        try {

            $months = [
                "01" => 0,
                "02" => 0,
                "03" => 0,
                "04" => 0,
                "05" => 0,
                "06" => 0,
                "07" => 0,
                "08" => 0,
                "09" => 0,
                "10" => 0,
                "11" => 0,
                "12" => 0,
            ];

            $product = null;

            $sales_order_details = $this->sales_order_detail->query()
                ->with([
                    "product",
                    "salesOrder",
                ])
                ->whereHas("salesOrder", function ($QQQ) use ($request) {
                    return $QQQ
                        ->consideredOrderByYear($request->year)
                        ->where("personel_id", $request->personel_id);
                })
                ->where("product_id", $request->product_id)
                ->get()
                ->groupBy([
                    function ($val) {
                        if ($val->salesOrder->type == "2") {
                            return $val->salesOrder->created_at->format("m");
                        } else {
                            return $val->salesOrder->invoice->created_at->format("m");
                        }
                    },
                ])
                ->map(function ($detail) use(&$product){
                    $product = $detail->first()->product;
                    return $detail->sum("quantity");
                });

            
            $recap = [
                "product" => $product,
                "recap" => collect($sales_order_details)->union($months)->sortKeys()
            ];

            return $this->response("00", "success", $recap);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}

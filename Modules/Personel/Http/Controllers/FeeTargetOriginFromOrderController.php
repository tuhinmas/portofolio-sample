<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\Product;
use Modules\Personel\Transformers\FeeTargetOriginCollectionResources;
use Modules\SalesOrder\Entities\SalesOrder;

class FeeTargetOriginFromOrderController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        SalesOrder $sales_order,
        Product $product
    ) {
        $this->sales_order = $sales_order;
        $this->product = $product;
    }

    public function __invoke(Request $request, $personel_id)
    {
        $validator = Validator::make($request->all(), [
            "product_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        $year = now()->format("Y");
        $quartal = now()->quarter;

        if ($request->has("year")) {
            $year = $request->year;
        }
        if ($request->quartal) {
            $quartal = $request->quartal;
        }

        $this->product->findOrFail($request->product_id);

        try {
            $sales_orders = $this->sales_order->query()
                ->with([
                    "invoice",
                    "salesOrderDetail" => function ($QQQ) use ($request) {
                        return $QQQ->where("product_id", $request->product_id);
                    },
                    "statusFee",
                    "dealer" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                    "subDealer" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                    "distributor" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "position",
                        ]);
                    },
                    "salesCounter" => function ($QQQ) {
                        return $QQQ->with([
                            "position",
                        ]);
                    },
                ])
                ->whereHas("salesOrderDetail", function ($QQQ) use ($request) {
                    return $QQQ->where("product_id", $request->product_id);
                })
                ->whereHas("feeTargetSharingOrigin", function ($QQQ) use ($personel_id, $year, $quartal) {
                    return $QQQ
                        ->where(function ($QQQ) use ($personel_id, $year, $quartal) {
                            return $QQQ
                                ->feeTargetMarketing([$personel_id], $year, $quartal)
                                ->orWhere(function ($QQQ) use ($personel_id, $year, $quartal) {
                                    return $QQQ->feeTargetMarketingActive([$personel_id], $year, $quartal);
                                });
                        })
                        ->whereHas("statusFee", function ($QQQ) {
                            return $QQQ->withTrashed();
                        })
                        ->whereHas("feeProduct", function ($QQQ) use ($year) {
                            return $QQQ
                                ->where("year", $year)
                                ->where("type", "2");
                        });
                })
                ->whereHas("statusFee", function ($QQQ) {
                    return $QQQ->withTrashed();
                })
                ->confirmedOrder()
                ->paginate($request->limit ? $request->limit : 10);

            return new FeeTargetOriginCollectionResources($sales_orders);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}

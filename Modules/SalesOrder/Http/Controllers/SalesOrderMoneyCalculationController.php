<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\DealerBenefit;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;

class SalesOrderMoneyCalculationController extends Controller
{
    use ResponseHandler;

    public function __construct(SalesOrder $sales_order, Dealer $dealer, DealerBenefit $benefit)
    {
        $this->sales_order = $sales_order;
        $this->dealer = $dealer;
        $this->benefit = $benefit;
    }

    public function discount(Request $request)
    {
        try {

            $sales_order = SalesOrder::query()
                ->where("id", $request->sales_order_id)
                ->with("sales_order_detail", "dealer")
                ->first();

            /* get benefit discount with periode if exist */
            $dealer_benefit = DealerBenefit::query()
                ->where("grading_id", $sales_order->dealer->grading_id)
                ->when($request->payment_method_id, function ($QQQ) use ($request) {
                    return $QQQ->where("payment_method_id", $request->payment_method_id);
                })

                ->when(!$request->payment_method_id, function ($QQQ) use ($sales_order) {
                    return $QQQ->where("payment_method_id", $sales_order->payment_method_id);
                })

                ->where(function ($qqq) {
                    return $qqq->where("start_period", "<=", Carbon::now())
                        ->where("end_period", ">=", Carbon::now())
                        ->orWhereNull("start_period");
                })
                ->get();

            $potongan = 0;
            $ppn_percentage = DB::table('ppn')->whereNull("deleted_at")->orderBy("created_at", "desc")->first();
            $total_amount = $sales_order->sales_order_detail->sum("total");
            // $total_amount = $sales_order->total;
            $ppn = $total_amount * $ppn_percentage->ppn / 100;
            $sum_product_discount = 0;

            /**
             * under maintenence
             */
            $data = (object) [
                "sub_total" => $total_amount,
                "ppn" => $ppn,
                "discount" => 0,
                "doscount_product" => 0,
                "discount_total" => 0,
                "total_amount" => $total_amount,
                "product_get_dicount" => null,
            ];
            return $this->response("00", "success, there is no benefit discount was found", $data);

            /* sales order has no benefit found */
            if (count($dealer_benefit) == 0) {
                $data = (object) [
                    "sub_total" => $total_amount,
                    "ppn" => $ppn,
                    "discount" => 0,
                    "doscount_product" => 0,
                    "discount_total" => 0,
                    "total_amount" => $total_amount,
                    "product_get_dicount" => null,
                ];
                return $this->response("00", "success, there is no benefit discount was found", $data);
            }

            /* benefit priority selection */
            /* benefit with periode prioritized */
            $agency_level = $sales_order->dealer->agency_level_id;
            foreach ($dealer_benefit as $key => $value) {

                /* check agency level on benefit*/
                if (in_array($agency_level, $value->agency_level_id) && $value->start_period != null) {
                    $dealer_benefit = $value;
                } else if (in_array($agency_level, $value->agency_level_id)) {
                    $dealer_benefit = $value;
                } else {
                    $dealer_benefit = $value;
                }
            }

            $total_amount_fix = $total_amount;
            $benefit_discount = $dealer_benefit->benefit_discount;
            $product_has_discount = null;
            foreach ($benefit_discount as $key => $stage) {
                if ($stage["type"] == "always") {
                    $discount = $total_amount * $stage["discount"]["discount"] / 100;
                    $total_amount -= $discount;
                    $potongan += $discount;
                }
                if ($stage["type"] == "threshold") {
                    if ($total_amount_fix >= $stage["discount"]["minimum_order"]) {
                        $product_category = $this->productList($stage["discount"]["product_category"]);
                        $product_has_discount = collect($sales_order->sales_order_detail)
                            ->whereIn("product_id", $product_category)
                            ->all();
                        // return $product_has_discount;
                        $discount_product = $stage["discount"]["discount"];
                        $product_has_discount = $this->productHasDiscount($product_has_discount, $discount_product);

                        break;
                    }
                }
            }

            $discount_total = collect($product_has_discount)->sum("discount");
            $total_amount -= $discount_total;
            $ppn = $total_amount * $ppn_percentage->ppn / 100;

            /* ppn exclude */
            $data = (object) [
                "sub_total" => $total_amount_fix,
                "ppn" => $ppn,
                "discount_cash" => $potongan,
                "doscount_product" => $discount_total,
                "discount_total" => $potongan + $discount_total,
                "total_amount" => $total_amount,
                "product_get_dicount" => $product_has_discount,
            ];

            return $this->response("00", "success, discount", $data);
        } catch (\Throwable$th) {
            return $this->response("01", "failed, get discount", $th->getMessage());
        }
    }

    public function productList($category)
    {
        $category_id = DB::table('product_categories')->where("name", $category)->first();
        $product_list = DB::table('products')
            ->where("category", $category_id->id)
            ->whereNull("deleted_at")
            ->get()
            ->pluck("id")
            ->toArray();

        return $product_list;
    }

    public function productHasDiscount($products, $discount)
    {
        foreach ($products as $key => $product) {
            $products[$key]["discount"] = $product->total * $discount / 100;
        }
        return $products;
    }
}

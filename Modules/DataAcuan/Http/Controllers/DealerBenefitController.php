<?php

namespace Modules\DataAcuan\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Modules\DataAcuan\Entities\Grading;
use Orion\Concerns\DisableAuthorization;
use App\Traits\OrionValidationBeforeSave;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\DataAcuan\Entities\DealerBenefit;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\DataAcuan\Http\Requests\DealerBenefitRequest;
use Modules\DataAcuan\Transformers\DealerBenefitResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Transformers\DealerBenefitCollectionResource;

class DealerBenefitController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;
    use OrionValidationBeforeSave;

    protected $model = DealerBenefit::class;
    protected $request = DealerBenefitRequest::class;
    protected $resource = DealerBenefitResource::class;
    protected $collectionResource = DealerBenefitCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            "grading",
            "paymentMethod",
        ];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterAbleBy(): array
    {
        return column_lists(new DealerBenefit);
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchAbleBy(): array
    {
        return column_lists(new DealerBenefit);
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortAbleBy(): array
    {
        return [
            "type",
            "discount",
            "grading_id",
            "created_at",
            "updated_at",
            "grading.name",
            "agency_level_id",
            "old_price_usage",
            "old_price_usage_limit",
            "minimum_nominal",
            "payment_method_id",
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {

        if ($request->has("disabled_pagination")) {
            return $query
                ->get();
        } else {
            return $query
                ->when(isset($request["sort_column"]), function ($QQQ) use ($request) {

                    $sort = collect($request["sort_column"])->pluck("field", "direction");
                    foreach ($sort as $direction => $sort_by) {
                        $direction = match ($direction) {
                            "desc" => "desc",
                            default => "asc",
                        };

                        if ($sort_by == "grading.name") {
                            // dd("ss");
                            return $QQQ
                                ->addSelect([
                                    'grading_name' => Grading::select(DB::raw('CASE WHEN deleted_at IS NULL THEN name ELSE "-" END'))
                                        ->whereColumn('gradings.id', 'dealer_benefits.grading_id')
                                        ->limit(1)
                                ])
                                ->orderByRaw("grading_name {$direction}");
                        }
                    }
                    return $QQQ;
                })
                ->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function beforeStore(Request $request, $model)
    {
        Grading::findOrFail($request->grading_id);
        PaymentMethod::findOrFail($request->payment_method_id);
        foreach ($request->agency_level_id as $agency_level_id) {
            AgencyLevel::findOrFail($agency_level_id);
        }
    }

    public function beforeUpdate(Request $request, $model)
    {
        $this->relationshipAssociateCheckv2($request, new Grading, "grading_id");
        $this->relationshipAssociateCheckv2($request, new PaymentMethod, "payment_method_id");
        $this->relationshipAssociateCheckv2($request, new AgencyLevel, "agency_level_id");
    }

    public function discount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "sales_order_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        try {
            $sales_order = SalesOrder::query()
                ->where("id", $request->sales_order_id)
                ->with("sales_order_detail", "dealer")
                ->first();

            if (!$sales_order) {
                return $this->response("01", "benefit not fount", "this order has no benefit");
                if (!$sales_order->dealer) {
                    return $this->response("01", "benefit not fount", "this order has no benefit");
                }
            }

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
            $ppn = $total_amount * $ppn_percentage->ppn / 100;
            $sum_product_discount = 0;

            /* sales order has no benefit found */
            // if (count($dealer_benefit) == 0) {
            //     $data = (object) [
            //         "sub_total" => $total_amount,
            //         "ppn" => $ppn,
            //         "discount" => 0,
            //         "doscount_product" => 0,
            //         "discount_total" => 0,
            //         "total_amount" => $total_amount,
            //         "product_get_dicount" => null,
            //     ];
            //     return $this->response("00", "success, there is no benefit discount was found", $data);
            // }

            /* benefit priority selection */
            /* benefit with periode prioritized */
            $active_benefit = null;
            $agency_level = $sales_order->dealer->agency_level_id;
            foreach ($dealer_benefit as $key => $value) {

                /* check agency level on benefit*/
                if (in_array($agency_level, $value->agency_level_id) && ($value->start_period <= Carbon::now() && $value->end_period >= Carbon::now())) {
                    $active_benefit = $value;
                } else if (in_array($agency_level, $value->agency_level_id)) {
                    $active_benefit = $value;
                }
            }

            if ($active_benefit) {
                return $this->response("00", "active benefit", $active_benefit);
            } else {
                return $this->response("01", "benefit not fount", "this order has no benefit");
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
        } catch (\Throwable $th) {
            return $this->response("01", "failed, get discount", $th->getMessage(), 500);
        }
    }
}

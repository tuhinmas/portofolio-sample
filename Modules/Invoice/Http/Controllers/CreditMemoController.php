<?php

namespace Modules\Invoice\Http\Controllers;

use Illuminate\Support\Str;
use App\Traits\DistributorStock;
use Orion\Http\Requests\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Invoice;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Entities\CreditMemo;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\Invoice\Actions\CreditMemoCreatedAction;
use Modules\Invoice\Http\Requests\CreditMemoRequest;
use Modules\Invoice\Transformers\CreditMemoResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Invoice\Actions\CreditMemoCanceledAction;
use Modules\Invoice\Transformers\CreditMemoCollectionResource;
use Modules\Distributor\Actions\GetDistributorActiveContractAction;

class CreditMemoController extends Controller
{
    use DisableAuthorization;
    use ResponseHandlerV2;
    use DistributorStock;

    protected $model = CreditMemo::class;
    protected $request = CreditMemoRequest::class;
    protected $resource = CreditMemoResource::class;
    protected $collectionResource = CreditMemoCollectionResource::class;

    /**
     * scope
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [];
    }

    /**
     * include data relation
     */
    public function includes(): array
    {
        return [
            "dealer",
            "dealer.addressDetail",
            "dealer.addressDetail.city",
            "dealer.addressDetail.district",
            "dealer.addressDetail.province",
            "dealer.agencyLevel",
            "origin",
            "destination",
            "creditMemoDetail",
            "creditMemoDetail.product",
            "creditMemoDetail.product.categoryProduct",
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "*"
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "*"
        ];
    }

    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->disabled_pagination) {
            return $query->get();
        }
        return $query->paginate($paginationLimit);
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $post
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $model, array $attributes): void
    {
        $memo_number = self::numberGenerator();
        $attributes["memo"]["status"] = "accepted";
        $attributes["memo"]["number"] = $memo_number["number"];
        $attributes["memo"]["number_order"] = $memo_number["number_order"];
        $model->fill($attributes["memo"]);
        $model->save();

        $products = $this->creditMemoDetail($request, $model);
        $model->creditMemoDetail()->createMany($products);
        $model->total = $products->sum("total");
        $model->save();
    }

    public function beforeStore(Request $request, $model)
    {
        Invoice::findOrFail($request->memo["origin_id"]);
        Invoice::findOrFail($request->memo["destination_id"]);
    }

    public function afterStore(Request $request, $model)
    {
        (new CreditMemoCreatedAction)($model);
    }

    public function afterUpdate(Request $request, $model)
    {
        (new CreditMemoCanceledAction)($model);
    }

    public static function numberGenerator()
    {
        /**
         * 2024/KM-06/001
         */
        $last_memo = DB::table('credit_memos')
            ->whereNull("deleted_at")
            ->whereYear("created_at", now()->year)
            ->orderByDesc("number_order")
            ->first();

        $number = $last_memo ? $last_memo->number_order + 1 : 1;
        return [
            "number" => now()->year . "/KM-" . now()->format('m') . "/" . Str::padLeft($number, 3, '0'),
            "number_order" => $number,
        ];
    }

    /**
     * product validation
     *
     * @param [type] $request
     * @return void
     */
    public function creditMemoDetail($request, $credit_memo)
    {
        $order_detail = DB::table('sales_order_details as sod')
            ->join("sales_orders as s", "s.id", "sod.sales_order_id")
            ->join("invoices as i", "s.id", "i.sales_order_id")
            ->whereNull("sod.deleted_at")
            ->whereNull("s.deleted_at")
            ->whereNull("i.deleted_at")
            ->where("i.id", $request->memo["origin_id"])
            ->select("sod.*")
            ->get()
            ->map(function ($sod) use ($request, $credit_memo) {
                $product = collect($request->products)->filter(fn($product) => $product["product_id"] == $sod->product_id)->first();
                $product = $product ? (object) $product : null;
                return [
                    "credit_memo_id" => $credit_memo->id,
                    "product_id" => $sod?->product_id,
                    "package_name" => $sod->package_name,
                    "quantity_on_package" => $sod->quantity_on_package,
                    "quantity_order" => $sod->quantity,
                    "quantity_return" => $product?->quantity_return,
                    "unit_price" => $sod->unit_price - ($sod->discount / $sod->quantity),
                    "unit_price_return" => $product?->unit_price_return,
                    "total" => $product?->unit_price_return * $product?->quantity_return,
                    "is_valid_product" => $product ? true : false,
                    "is_valid_quantity" => $sod->quantity < $product?->quantity_return ? false : true,
                ];
            });

        $products = $order_detail
            ->filter(function ($order) use ($request) {
                return $order["is_valid_product"];
            });

        switch (true) {
            case !$this->isProductMemoMeetProductOrder($products, $order_detail):
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "produk tidak sesuai dengan order proforma asal",
                    ],
                ], 422);
                throw new HttpResponseException($response);
                break;

            /* there area product higher then available quantity to return */
            case !$this->isQuantityMatch($request, $products):
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "jumlah return melebihi maksimal yang bisa direturn",
                    ],
                ], 422);
                throw new HttpResponseException($response);
                break;

            case !$this->isQuantityMatchWithStock($request, $credit_memo):
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "maksimal return adalah maksimal stok",
                    ],
                ], 422);
                throw new HttpResponseException($response);
                break;
            default:
                break;
        }

        return $products;
    }

    public function isProductMemoMeetProductOrder($product_order, $product_memo)
    {
        return $product_order->count() == $product_memo->count();
    }

    public function isQuantityMatch($request, $products)
    {
        $product_memo = DB::table('credit_memo_details as cmd')
            ->join("credit_memos as cm", "cm.id", "cmd.credit_memo_id")
            ->whereNull("cmd.deleted_at")
            ->whereNull("cm.deleted_at")
            ->where("cm.origin_id", $request->memo["origin_id"])
            ->select("cmd.*", "cm.origin_id")
            ->get()
            ->groupBy("product_id")
            ->map(function ($detail) {
                return [
                    "total_return" => $detail->sum("quantity_return"),
                    "quantity_order" => $detail->first()->quantity_order,
                    "available_to_return" => $detail->first()->quantity_order - $detail->sum("quantity_return"),
                ];
            })
            ->filter(function ($memo, $product_id) use ($request) {
                $product = (object) collect($request->products)->filter(fn($product) => $product["product_id"] == $product_id)->first();
                return $product?->quantity_return > $memo["available_to_return"];
            })
            ->count();

        return !($product_memo > 0 || $products->filter(fn($product) => !$product["is_valid_quantity"])->count() > 0);
    }

    public function isQuantityMatchWithStock($request, $credit_memo)
    {
        $distributor_active_contract = (new GetDistributorActiveContractAction)($credit_memo->dealer_id);
        if ($distributor_active_contract) {
            foreach ($request->products as $product) {
                $stock = $this->distributorProductCurrentStockAdjusmentBased($credit_memo->dealer_id, $product["product_id"], now(), $distributor_active_contract);
                if ($product["quantity_return"] > $stock->current_stock) {
                    return false;
                }
            }
        }

        return true;
    }

}

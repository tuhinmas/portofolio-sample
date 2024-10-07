<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Exports\ExportDirectRetailer;
use App\Exports\TemplateFeeProduct as ExportsTemplateFeeProduct;
use Carbon\Carbon;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Fee;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Orion\Concerns\DisableAuthorization;
use App\Traits\OrionValidationBeforeSave;
use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Excel;
use Illuminate\Http\ResponseTrait;
use Illuminate\Support\Facades\Validator;
use Modules\Personel\Entities\MarketingFee;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\SalesOrderV2\Entities\FeeSharing;
use Modules\DataAcuan\Http\Requests\FeeRequest;
use Modules\DataAcuan\Transformers\FeeResource;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\DataAcuan\Events\FeeInCreatedFeeProductEvent;
use Modules\DataAcuan\Events\FeeInDeletedFeeProductEvent;
use Modules\DataAcuan\Transformers\FeeCollectionResource;
use Modules\DataAcuan\Events\UpdatedFeeTargetProductEvent;
use Modules\DataAcuan\Events\FeeInCreatedFeeTargetProductEvent;
use Modules\DataAcuan\Events\FeeInDeletedFeeTargetProductEvent;
use Modules\DataAcuan\Import\FeeProductImport;
use Modules\DataAcuan\Jobs\SyncFeeByProductJob;
use Modules\DataAcuan\Jobs\SyncFeeProductAfterImportJob;

class FeeController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;
    use OrionValidationBeforeSave;

    protected $model = Fee::class;
    protected $request = FeeRequest::class;
    protected $resource = FeeResource::class;
    protected $collectionResource = FeeCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "product",
            "product.package",
            "product.category",
        ];
    }

    public function includes(): array
    {
        return [
            "productReference",
            "productHasOne"
        ];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [
            "productCategory",
        ];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "product.name",
            "productHasOne.name",
            "product_id",
            "created_at",
            "updated_at",
            "quantity",
            "year",
            "type",
            "fee",
            "id",
            "quartal"
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            "product_id",
            "created_at",
            "updated_at",
            "quantity",
            "year",
            "type",
            "fee",
            "id",
            "quartal"
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "productReference.name",
            "productReference.size",
            "productHasOne.name",
            "productHasOne.size",
            "product_id",
            "created_at",
            "updated_at",
            "quantity",
            "year",
            "type",
            "fee",
            "id",
            "quartal"
        ];
    }

    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations)->latest();
        return $query;
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->save();
        
        if ($entity->type == "1") {
            $fee_product = FeeInCreatedFeeProductEvent::dispatch($entity);
        }
        else {
            $fee_product_target = FeeInCreatedFeeTargetProductEvent::dispatch($entity);
        }
    }

    public function afterStore(Request $request, $model)
    {
        resolve(\Modules\DataAcuan\Repositories\FeeProductRepository::class)->deleteExistJobFeeProduct(
            $model->year, 
            $model->quartal, 
            $model->product_id
        );

        SyncFeeByProductJob::dispatch($model->year, $model->quartal, $model->product_id, true)->onConnection('database')->onQueue('default');
    }

    public function afterUpdate(Request $request, $model)
    {
        resolve(\Modules\DataAcuan\Repositories\FeeProductRepository::class)->deleteExistJobFeeProduct(
            $model->year, 
            $model->quartal, 
            $model->product_id
        );
        
        SyncFeeByProductJob::dispatch($model->year, $model->quartal, $model->product_id, true)->onConnection('database')->onQueue('default');
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $last_year = $entity->year;
        $last_fee = $entity->fee;
        $last_quantity_target = $entity->quantity;
        $entity->fill($attributes);
        $entity->save();
        if (
            (
                $entity->year == Carbon::now()->format("Y")
                && $entity->type == "1"
            )
            &&
            (
                $last_fee != $entity->fee
                || $last_quantity_target != $entity->quantity
            )
        ) {
            $this->resetSalesOrderDetailFee($entity->product_id);
        }

        if (
            (
                $entity->year == Carbon::now()->format("Y")
                && $entity->type == "2"
            )
            &&
            (
                $last_fee != $entity->fee
                || $last_quantity_target != $entity->quantity
            )
        ) {
            $log_marketing_fee_counter_target_deleted = UpdatedFeeTargetProductEvent::dispatch($entity);
        }
    }

    public function beforeSave(Request $request, $model)
    {
        /* trait to validate relation */
        $product = new Product;
        $this->relationshipAssociateCheck($request, $product, $request->product_id, "product_id");
    }

    public function personelUpdate($attributes)
    {
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "L1")->first()->id;
        $district_id = $attributes["district_id"];
        $personel_id = $this->updateMarketing($district_id);

        if ($attributes["type"] == "dealer") {
            $dealer = DealerV2::find($attributes["parent_id"]);

            if ($dealer) {
                $dealer->personel_id = $personel_id;
                $dealer->save();
            }
        } else if ($attributes["type"] == "sub_dealer") {
            $sub_dealer = SubDealer::find($attributes["parent_id"]);
            if ($sub_dealer) {
                $sub_dealer->personel_id = $personel_id;
                $sub_dealer->save();
            }
        }
    }

    public function resetSalesOrderDetailFee($product_id)
    {
        /**
         * get all sales order detail on this year
         * according fee product updated
         */
        $sales_order_detail = SalesOrderDetail::query()
            ->whereHas("sales_order", function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($QQQ) {
                                        return $QQQ->whereYear("created_at", Carbon::now());
                                    });
                            })
                            ->orWhere(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "2")
                                    ->whereYear("date", Carbon::now());
                            });
                    });
            })
            ->where("product_id", $product_id)
            ->get();

        /**
         * get sales order according sales order detail
         * which product_id is updated
         */
        $sales_order_id = $sales_order_detail
            ->map(function ($detail, $key) {
                return $detail->sales_order_id;
            })
            ->values()
            ->toArray();

        /**
         * before recalculate fee sharing, save fee per marketing
         * and reduce all marketing which get fee from
         * sales order this product, no matter
         * was counted or not
         */
        $fee_sharings_counted = FeeSharing::query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice" => function ($QQQ) {
                            return $QQQ->with("allPayment");
                        },
                    ]);
                },
            ])
            ->where("is_checked", "1")
            ->whereHas("logMarketingFeeCounter", function ($QQQ) {
                return $QQQ->where("type", "reguler");
            })
            ->whereIn("sales_order_id", $sales_order_id)
            ->whereYear("created_at", Carbon::now()->format("Y"))
            ->select("fee_sharings.*", DB::raw("QUARTER(fee_sharings.created_at) as quarter"))
            ->get();

        $marketing_fee_reguler_reduced = $this->marketingFeeAccordingProductUpdated($fee_sharings_counted);

        /**
         * update marketing fee this year, reduce fee reguler according
         * how many sales order affected by its updated product
         */
        collect($marketing_fee_reguler_reduced)->each(function ($marketing) {
            $marketing_fee = MarketingFee::query()
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("quarter", $marketing["quarter"])
                ->first();

            $marketing_fee_update = DB::table('marketing_fee')
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("quarter", $marketing["quarter"])
                ->update([
                    "fee_reguler_total" => $marketing_fee->fee_reguler_total - $marketing["fee_reguler_total"],
                    "fee_reguler_settle" => $marketing_fee->fee_reguler_settle - $marketing["fee_reguler_settle"],
                ]);
        });

        /**
         * set fee sharing to unchecked
         * it will be calculated again
         */
        $fee_sharing = DB::table('fee_sharings')
            ->whereIn("sales_order_id", $sales_order_id)
            ->update([
                "fee_shared" => 0,
                "is_checked" => 0,
            ]);

        /**
         * delete all log worker fee
         */
        $log_sales_fee = DB::table('log_worker_sales_fees')
            ->whereIn("sales_order_id", $sales_order_id)
            ->delete();

        /**
         * update fee marketing on
         * sales order detail
         */
        $sales_order_detail = SalesOrderDetail::query()
            ->whereIn("sales_order_id", $sales_order_id)
            ->update([
                "marketing_fee" => 0,
                "marketing_fee_reguler" => 0,
            ]);

        /**
         * delete marketing fee counter
         *
         * @param [type] $marketing_fee_reguler
         * @return void
         */
        DB::table('log_marketing_fee_counter')
            ->whereIn("sales_order_id", $sales_order_id)
            ->where("type", "reguler")
            ->delete();
    }

    public function marketingFeeAccordingProductUpdated($marketing_fee_reguler)
    {

        /**
         * grouping fee by sales order id to get
         * marketing fee
         */
        $marketing_fee_reguler_grouped = $marketing_fee_reguler->groupBy("sales_order_id");

        /**
         * after grouping check sales order follow up
         * to fixing fee value fee marketing
         * as purchaser, prchaser = 0
         * on follow up
         */
        $marketing_fee_reguler = $marketing_fee_reguler_grouped->map(function ($fee) {
            $is_follow_up = collect($fee)
                ->filter(function ($fee_order) {
                    if ($fee_order->handover_status == "1") {
                        return $fee_order;
                    }
                })
                ->first();

            if ($is_follow_up) {
                $fee = collect($fee)
                    ->map(function ($fee_order) {
                        if ($fee_order->fee_status == "purchaser") {
                            $fee_order->fee_shared = 0;
                        }
                        return $fee_order;
                    });
            }

            return $fee;
        });

        /* reverse grouping */
        $marketing_fee_reguler = $marketing_fee_reguler->flatten(1);

        /* grouping fee sharing by quarter */
        $marketing_fee_reguler_grouped = $marketing_fee_reguler
            ->sortBy("quarter")
            ->reject(function ($fee) {
                if (!$fee->personel_id) {
                    return $fee;
                }
            })
            ->groupBy([
                function ($val) {return $val->personel_id;},
                function ($val) {return $val->quarter;},
            ]);

        /**
         * marketing fee template
         */
        $marketing_fee = collect();

        /* payment date check */
        $payment_date_maximum = PaymentMethod::orderBy("days", "desc")->first();

        $marketing_fee_reguler_grouped = $marketing_fee_reguler_grouped->map(function ($fee_per_marketing, $personel_id) use (&$marketing_fee, $payment_date_maximum) {
            $fee_per_marketing = collect($fee_per_marketing)->map(function ($fee_per_quartal, $quartal) use ($payment_date_maximum, &$marketing_fee, $personel_id) {

                $fee_reguler_settle_per_quartal = 0;
                $fee_reguler_unsettle_per_quartal = 0;

                $fee_per_quartal = collect($fee_per_quartal)->each(function ($fee) use (&$fee_reguler_settle_per_quartal, &$fee_reguler_unsettle_per_quartal, $payment_date_maximum) {

                    if ($fee->salesOrder) {
                        if ($fee->salesOrder->type == "1") {
                            if ($fee->salesOrder->invoice) {
                                if ($fee->salesOrder->invoice->payment_status == "settle") {

                                    $last_payment = collect($fee->salesOrder->invoice->allPayment)->sortByDesc("payment_date")->first();
                                    $settle_days_count = 0;
                                    if ($last_payment) {
                                        $settle_days_count = $fee->salesOrder->invoice->created_at->startOfDay()->diffInDays($last_payment->payment_date);
                                    } else {
                                        $settle_days_count = $fee->salesOrder->invoice->created_at->startOfDay()->diffInDays($fee->salesOrder->invoice->updated_at);
                                    }

                                    if ($settle_days_count <= ($payment_date_maximum ? $payment_date_maximum->days : 60)) {
                                        $fee_reguler_settle_per_quartal += $fee->fee_shared;
                                    }

                                } else {
                                    $fee_reguler_unsettle_per_quartal += $fee->fee_shared;
                                }
                            }
                        } else {
                            $fee_reguler_settle_per_quartal += $fee->fee_shared;
                        }
                    }
                });

                $marketing_fee->push(collect([
                    "personel_id" => $personel_id,
                    "fee_reguler_total" => $fee_per_quartal->sum("fee_shared"),
                    "fee_reguler_settle" => $fee_reguler_settle_per_quartal,
                    "fee_reguler_unsettle" => $fee_reguler_unsettle_per_quartal,
                    "year" => $fee_per_quartal->first()->created_at->format("Y"),
                    "quarter" => $quartal,
                    "type" => $fee_per_quartal[0]->salesOrder->type,
                    "fee_count" => $fee_per_quartal->count(),
                ]));

                return $marketing_fee;
            });

            return $fee_per_marketing;
        });

        return $marketing_fee->sortBy("personel_id");
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildDestroyFetchQuery(Request $request, $requestedRelations, bool $softDeletes): Builder
    {
        $query = parent::buildDestroyFetchQuery($request, $requestedRelations, $softDeletes);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runDestroyFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function performDestroy(Model $entity): void
    {
        $entity->delete();

        /**
         * if fee reguler on this year was deleted
         */
        if ($entity->type == "1" && $entity->year == Carbon::now()->format("Y")) {
            $fee_product = FeeInDeletedFeeProductEvent::dispatch($entity);
        }

        if ($entity->type == "2" && $entity->year == Carbon::now()->format("Y")) {
            $fee_product = FeeInDeletedFeeTargetProductEvent::dispatch($entity);
        }
    }

    public function templateImport()
    {
        return Excel::download(new ExportsTemplateFeeProduct(), "template-import-fee-product.xlsx");
    } 

    public function importFeeProduct(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            "fee_product" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }
        
        if (!in_array($request->fee_product->getClientOriginalExtension(), ["xlsx", "xlsm", "xlsb", "xls"])) {
            return $this->response("00", "success","you insert invalid excel/file extension", 422);
        }
        
        try {
            ini_set('max_execution_time', 300);
            $import = new FeeProductImport;
            Excel::import($import, $request->fee_product);
            $response = $import->getData();

            foreach ($response['Product Import'] as $key => $value) {
                resolve(\Modules\DataAcuan\Repositories\FeeProductRepository::class)
                    ->deleteExistJobFeeProduct($value['year'], $value['quartal'], $value['product_id']);

                SyncFeeByProductJob::dispatch($value['year'], $value['quartal'], $value['product_id'])->onConnection('database')->onQueue('default');
            }
            return $this->response("00", "success", $response);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th, 500);
        }
    } 
}

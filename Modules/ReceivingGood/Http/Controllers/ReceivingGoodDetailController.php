<?php

namespace Modules\ReceivingGood\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\PromotionGood\Entities\PromotionGood;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\ReceivingGood\Http\Requests\ReceivingGoodDetailRequest;
use Modules\ReceivingGood\Transformers\ReceivingGoodDetailResource;
use Modules\ReceivingGood\Events\DeliveryStatusCheckInReceivingGoodEvent;
use Modules\ReceivingGood\Transformers\ReceivingGoodDetailCollectionResource;

class ReceivingGoodDetailController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = ReceivingGoodDetail::class;
    protected $request = ReceivingGoodDetailRequest::class;
    protected $resource = ReceivingGoodDetailResource::class;
    protected $collectionResource = ReceivingGoodDetailCollectionResource::class;

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            "product",
            "receivingGood",

            "promotionGood",
            "promotionGood.product",
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
            'id',
            'product_id',
            'receiving_good_id',
            'user_id',
            'quantity',
            'status',
            'note',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'product_id',
            'receiving_good_id',
            'user_id',
            'quantity',
            'status',
            'note',
            'created_at',
            'updated_at',
        ];
    }

    public function beforeStore(Request $request, $model)
    {
        if ($request->has("resources")) {
            foreach ($request->resources as $resource) {
                if (!isset($resource["product_id"]) && !isset($resource["promotion_good_id"])) {
                    $response = $this->response("04", "invalid data send", [
                        "messages" => [
                            "can not empty both product_id and promotion_good_id, fill one",
                        ],
                    ], 422);

                    throw new HttpResponseException($response);
                }
            }
        } else {
            if (!$request->product_id && !$request->promotion_good_id) {

                $response = $this->response("04", "invalid data send", [
                    "messages" => [
                        "can not empty both product_id and promotion_good_id, fill one",
                    ],
                ], 422);

                throw new HttpResponseException($response);
            }
        }
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $receivingGood = ReceivingGood::find($request->receiving_good_id);

        if ($request->has('promotion_good_id') && $receivingGood->deliveryOrder->is_promotion == 1) {
            $promotionGoodRequestId = $receivingGood->deliveryOrder->dispatchPromotion->promotionGoodRequest->id;
            $promotionGood = PromotionGood::where('promotion_good_request_id', $promotionGoodRequestId)->where(function ($q) use ($request) {
                $q->where('product_id', $request->promotion_good_id)->orWhere('id', $request->promotion_good_id);
            })->first();
            $attributes['promotion_good_id'] = $promotionGood->id;
        } else {
            if ($request->has('product_id') || !empty($attributes["product_id"])) {
                $package = $this->packageConverter($attributes["receiving_good_id"], $attributes["product_id"]);
                if ($package) {
                    $quantity_packe = $attributes["quantity"] / $package->quantity_per_package;
                    $attributes["quantity_package"] = $quantity_packe < 1 ? ceil($quantity_packe) : $quantity_packe;
                }
            }
        }
        $entity->fill($attributes);
        $entity->save();
        if (!$entity->user_id) {
            $entity->user_id = auth()->id();
        }
    }

    /**
     * event after store
     *
     * @param Request $request
     * @param [type] $model
     * @return void
     */
    public function afterStore(Request $request, $model)
    {
        if ($model->status == "delivered") {
            $received_product = DeliveryStatusCheckInReceivingGoodEvent::dispatch($model);
        }

        if ($model->product_id) {
            $dispatch_order_detail = DB::table('dispatch_order_detail as dod')
                ->join("discpatch_order as dis", "dis.id", "dod.id_dispatch_order")
                ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
                ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
                ->whereNull("dod.deleted_at")
                ->whereNull("dis.deleted_at")
                ->whereNull("dor.deleted_at")
                ->whereNull("rg.deleted_at")
                ->where("dor.status", "send")
                ->where("dod.id_product", $model->product_id)
                ->where("rg.id", $model->receiving_good_id)
                ->select("dod.*")
                ->first();

            $qty_received = DB::table('receiving_good_details')
                ->whereNull("deleted_at")
                ->where("receiving_good_id", $model->receiving_good_id)
                ->where("product_id", $model->product_id)
                ->sum("quantity");

            if ($qty_received > $dispatch_order_detail?->quantity_unit) {

                $response = $this->response("04", "invalid data send", [
                    "messages" => [
                        "can not create receiving good detail, max quantity is " . $dispatch_order_detail->quantity_unit . " according dispatch"
                    ],
                ], 422);

                throw new HttpResponseException($response);
            }
        }

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
        return ReceivingGoodDetail::findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        if ($entity->product_id != null) {
            $package = $this->packageConverter($entity->receiving_good_id, $entity->product_id);
            if ($package) {
                if (array_key_exists("quantity", $attributes)) {
                    $quantity_package = $attributes["quantity"] / $package->quantity_per_package;
                    $attributes["quantity_package"] = $quantity_package < 1 ? ceil($quantity_package) : $quantity_package;
                }
                $entity->quantity_package = $quantity_package < 1 ? ceil($quantity_package) : $quantity_package;
            }
        }
        $entity->fill($attributes);
        $entity->save();

        if ($entity->status == "delivered") {
            $received_product = DeliveryStatusCheckInReceivingGoodEvent::dispatch($entity);
        }
    }

    public function packageConverter($receiving_good_id, $product_id)
    {
        $receiving_good = ReceivingGood::query()
            ->with([
                "deliveryOrder" => function ($QQQ) use ($product_id) {
                    return $QQQ
                        ->with([
                            "dispatchOrder" => function ($QQQ) use ($product_id) {
                                return $QQQ->with([
                                    "invoice" => function ($QQQ) use ($product_id) {
                                        return $QQQ->with([
                                            "salesOrderOnly" => function ($QQQ) use ($product_id) {
                                                return $QQQ->with([
                                                    "salesOrderDetail" => function ($QQQ) use ($product_id) {
                                                        return $QQQ
                                                            ->where("product_id", $product_id)
                                                            ->limit(1)
                                                            ->with("package");
                                                    },
                                                ]);
                                            },
                                        ]);
                                    },
                                ]);
                            },
                        ]);
                },
            ])
            ->findOrFail($receiving_good_id);

        return $receiving_good->deliveryOrder->dispatchOrder->invoice->salesOrderOnly->sales_order_detail ? $receiving_good->deliveryOrder->dispatchOrder->invoice->salesOrderOnly->salesOrderDetail[0]->package : null;
    }
}

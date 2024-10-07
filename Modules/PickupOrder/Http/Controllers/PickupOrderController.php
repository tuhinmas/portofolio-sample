<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Events\PickupAutoCheckOnLoadEvent;
use Modules\PickupOrder\Events\PickupOrderLoadedEvent;
use Modules\PickupOrder\Http\Requests\PickupOrderRequest;
use Modules\PickupOrder\Repositories\PickupOrderRepository;
use Modules\PickupOrder\Transformers\Collections\PickupOrderCollectionResource;
use Modules\PickupOrder\Transformers\Resources\PickupOrderResource;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class PickupOrderController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = PickupOrder::class;
    protected $request = PickupOrderRequest::class;
    protected $resource = PickupOrderResource::class;
    protected $collectionResource = PickupOrderCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            // "warehouse"
        ];
    }

    public function includes(): array
    {
        return [
            "deliveryPickupOrders.deliveryOrder.dispatchPromotion",
            "deliveryPickupOrders.deliveryOrder.dispatchOrder",
            "deliveryPickupOrders.deliveryOrder",
            "deliveryPickupOrders",
            "pickupOrderDetails",
            "driver.personel",
            "proformaReceipt",
            "warehouse",
            "driver",
            "armada",

            "pickupOrderDispatch.pickupDispatchAble.dispatchOrderDetail",
            "pickupOrderDispatch.pickupDispatchAble.deliveryOrder",
            "pickupOrderDispatch.pickupDispatchAble",
            "pickupOrderDispatch",

            "pickupOrderDetailLoadPromotion",
            "pickupOrderDetailLoadPromotion.product",
            "pickupOrderDetailLoadDirect",
            "pickupOrderDetailLoadDirect.product",
        ];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [
            "byArmada",
            "byPorter",
            "byWarehouse",
            "deliveryOrderNumber",
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
            "id",
            "pickup_number",
            "warehouse.name",
            "warehouse.id",
            "warehouse.police_number",
            "status",
            "delivery_date",
            "created_at",
            "updated_at",
            "driver_id",
            // "pickupOrderDispatch.dispatchOrder.invoice.invoice",
            "pickupOrderDispatch.dispatch.deliveryOrder.delivery_order_number",
            "pickupOrderDispatch.dispatch.invoice.invoice",
            "pickupOrderDispatch.dispatch.dispatch_order_number",
            "pickupOrderDispatch.dispatchOrder.invoice.invoice",

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
            "id",
            "pickup_number",
            "warehouse.name",
            "warehouse.id",
            "warehouse.police_number",
            "status",
            "delivery_date",
            "created_at",
            "updated_at",
            // "pickupOrderDispatch.dispatchOrder.invoice.invoice",
            "pickupOrderDispatch.dispatch.deliveryOrder.delivery_order_number",
            "pickupOrderDispatch.dispatch.invoice.invoice",
            "pickupOrderDispatch.dispatch.dispatch_order_number",
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
            "id",
            "pickup_number",
            "warehouse.name",
            "warehouse.id",
            "warehouse.police_number",
            "status",
            "delivery_date",
            "created_at",
            "updated_at",
        ];
    }

    public function aggregates(): array
    {
        return [
            "pickupOrderDetails",
            "pickupOrderDetails.*",
            "pickupOrderDispatch",
            "pickupOrderDispatch.pickupDispatchAble.*",
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
            return self::customSort($request, $query)->get();
        } else {
            return self::customSort($request, $query)->paginate($request->limit ? $request->limit : 15);
        }
    }

    public function buildShowFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildFetchQuery($request, $requestedRelations);

        return $query;
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
        $entity->fill($attributes);
        $entity->pickup_number = resolve(PickupOrderRepository::class)->generatePickUpOrder();
        $entity->status = $request->status ?? "planned";
        $entity->save();
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

    public function beforeUpdate(Request $request, $model)
    {
        if (in_array($request->status, ["canceled", "failed"])) {
            $dispatchId = PickupOrderDispatch::where("pickup_order_id", $model->id)
                ->select("dispatch_id")
                ->get()
                ->pluck("dispatch_id")
                ->toArray();

            DispatchOrder::query()
                ->whereIn("id", $dispatchId)
                ->where("status", "<>", "received")
                ->lockForUpdate()
                ->get()
                ->each(function ($dispatch) {
                    $dispatch->status = "planned";
                    $dispatch->save();
                });

            DispatchPromotion::query()
                ->whereIn("id", $dispatchId)
                ->where("status", "<>", "received")
                ->lockForUpdate()
                ->get()
                ->each(function ($dispatch) {
                    $dispatch->status = "planned";
                    $dispatch->save();
                });

            $deliveryOrder = DeliveryOrder::query()
                ->where(function ($q) use ($dispatchId) {
                    $q->whereIn("dispatch_order_id", $dispatchId)->orWhere("dispatch_promotion_id", $dispatchId);
                })
                ->whereDoesntHave("receivingGoodHasReceived")
                ->get();

            foreach ($deliveryOrder as $value) {
                DeliveryOrder::where("id", $value->id)->update([
                    "status" => $model->status == "delivered" ? "failed" : "canceled",
                    "status_note" => $request->note,
                ]);

                DeliveryOrderNumber::where("delivery_order_id", $value->id)->delete();
            }
        }
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        if (isset($attributes["is_auto_check"])) {
            unset($attributes["is_auto_check"]);
        }
        $entity->fill($attributes);
        $entity->save();
    }

    public function afterUpdate(Request $request, $model)
    {
        $request->merge([
            "is_auto_check" => $request->is_auto_check ?? true,
        ]);

        if (in_array($model->status, ["loaded", "checked"])) {
            if ($request->is_auto_check) {
                PickupAutoCheckOnLoadEvent::dispatch($model);
            }
            PickupOrderLoadedEvent::dispatch($model);
        }
    }

    public function deliveryOrderList(Request $request)
    {
        try {
            $pickupOrderRepository = new PickupOrderRepository();
            $response = $pickupOrderRepository->listDeliveryOrder($request->all());
            return $this->response("00", "Delivery Order List", $response);
        } catch (\Exception $th) {
            $th;
            return $this->response("01", "failed to save", $th->getMessage(), 500);
        }
    }

    public function detailTravelDocument(Request $request, $pickUpOrderId)
    {
        try {
            $pickupOrderRepository = new PickupOrderRepository();
            $response = $pickupOrderRepository->travelDocumentList($pickUpOrderId);
            return $this->response("00", "Delivery Order List", $response);
        } catch (\Exception $th) {
            $th;
            return $this->response("01", "failed to save", $th->getMessage(), 500);
        }
    }

    public static function customSort($request, $query)
    {
        return $query->when($request->sort_by == "armada", function ($QQQ) use ($request) {
            $direction = $request->direction ?? "asc";
            return $QQQ
                ->select("pickup_orders.*")
                ->leftJoin("drivers as d", "d.id", "pickup_orders.driver_id")
                ->orderByRaw("IF(pickup_orders.driver_id is not null, d.transportation_type, pickup_orders.type_driver) $direction");
        });
    }
}

<?php

namespace Modules\DistributionChannel\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\Warehouse;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Http\Requests\DispatchOrderRequest;
use Modules\DistributionChannel\Transformers\DispatchOrderResource;
use Modules\DistributionChannel\Transformers\DispatchOrderCollectionResource;

class DispatchOrderController extends Controller
{
    use ResponseHandler;

    protected $model = DispatchOrder::class;
    protected $request = DispatchOrderRequest::class;
    protected $resource = DispatchOrderResource::class;
    protected $collectionResource = DispatchOrderCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            // "addressDelivery"
        ];
    }

    public function includes(): array
    {
        return [
            "promotionGoodRequest",
            "deliveryOrder.receivingGoods",
            "deliveryOrder",
            "deliveryOrderValid",
            "addressDelivery",
            "addressDelivery.province",
            "addressDelivery.city",
            "addressDelivery.district",

            "dispatchOrderDetail.product.package",
            "dispatchOrderDetail.salesOrderDetail",
            "dispatchOrderDetail",
            "dispatchOrderFiles",

            "driver.personel.contact",
            "driver.personel",
            "driver",

            "invoice.salesOrder.dealer.adress_detail.district",
            "invoice.salesOrder.dealer.adress_detail.province",
            "invoice.salesOrder.dealer.adress_detail.city",
            "invoice.salesOrder.dealer.personel.position",
            "invoice.salesOrder.dealer.adress_detail",
            "invoice.salesOrder.dealer",
            "invoice.salesOrder",
            "invoice",

            "salesOrderDeep.addressDetailDeep",
            "salesOrderDeep.addressDetailDeep.district",
            "salesOrderDeep.addressDetailDeep.province",
            "salesOrderDeep.addressDetailDeep.city",

            "invoice.salesOrder.personel.position",

            "receipt.confirmedBy.position",
            "receipt.confirmedBy",
            "receipt",
            "warehouse",
            "receivingGood",
            "wasReceived",
            "pickupOrder",
            "pickupOrderNotCanceled",
        ];
    }

    /**
     * The list of available query scopes.
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [
            'byDealerNameDealerOwnerCustIdDistrictCityProvince',
            'hasNotDeliveryOrder',
            'personelBranch',
            'byDealerName',
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
            "id_armada",
            "is_active",
            "id_invoice",
            "id_product",
            "invoice_id",
            'created_at',
            'updated_at',
            "type_driver",
            "driver_name",
            "police_number",
            "date_delivery",
            "promotion_good_request_id",
            "invoice.invoice",
            "transportation_type",
            "deliveryOrder.status",
            "dispatch_order_number",
            "quantity_packet_to_send",
            "deliveryOrder.receivingGoods.delivery_status",
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
            'id',
            "id_invoice",
            "id_armada",
            "id_product",
            "invoice_id",
            "quantity_packet_to_send",
            "type_driver",
            "transportation_type",
            "police_number",
            "date_delivery",
            "invoice.invoice",
            "driver_name",
            "dispatch_order_number",
            'created_at',
            'updated_at',
            "invoice.invoice",
            "is_active",
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
            'id',
            "id_invoice",
            "id_armada",
            "id_product",
            "invoice_id",
            "quantity_packet_to_send",
            "type_driver",
            "transportation_type",
            "police_number",
            "date_delivery",
            "driver_name",
            "dispatch_order_number",
            'created_at',
            'updated_at',
            "invoice.invoice",
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
            $data = $query->whereHas("deliveryOrder")->get();
            if ($request->has('delivery_order_only')) {
                return $data->map(function ($data) {
                    return $data->deliveryOrder;
                });
            }
            return $data;
        } else {

            if ($request->sort_by) {
                $sortedResult = $query
                // ->whereHas("invoice")
                    ->get();
                if ($request->sort_by == 'buyer') {
                    if ($request->direction == "desc") {
                        // dd("sas");
                        $sortedResult = $sortedResult->sortByDesc(function ($item) {
                            return $item->invoice?->salesOrder?->dealer->name;
                        })->values();
                    } elseif ($request->direction == "asc") {
                        $sortedResult = $sortedResult->sortBy(function ($item) {
                            return $item->invoice?->salesOrder?->dealer->name;
                        })->values();
                    }
                }

                if ($request->sort_by == 'location_buyer') {
                    if ($request->direction == "desc") {
                        // dd("sas");
                        $sortedResult = $sortedResult->sortByDesc(function ($item) {
                            return $item->invoice?->salesOrder?->dealer?->address;
                        })->values();
                    } elseif ($request->direction == "asc") {
                        $sortedResult = $sortedResult->sortBy(function ($item) {
                            return $item->invoice?->salesOrder?->dealer?->address;
                        })->values();
                    }
                }

                if ($request->sort_by == 'marketing_name') {
                    if ($request->direction == "desc") {
                        // dd("sas");
                        $sortedResult = $sortedResult->sortByDesc(function ($item) {
                            return $item->invoice?->salesOrder?->personel?->name;
                        })->values();
                    } elseif ($request->direction == "asc") {
                        $sortedResult = $sortedResult->sortBy(function ($item) {
                            return $item->invoice?->salesOrder?->personel?->name;
                        })->values();
                    }
                }

                $currentPage = LengthAwarePaginator::resolveCurrentPage();
                $pageLimit = $request->limit > 0 ? $request->limit : 15;

                // slice the current page items
                $currentItems = $sortedResult->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

                // you may not need the $path here but might be helpful..
                $path = LengthAwarePaginator::resolveCurrentPath();

                // Build the new paginator
                $data = new LengthAwarePaginator($currentItems, count($sortedResult), $pageLimit, $currentPage, ['path' => $path]);
            } else {
                $data = $query
                // ->whereHas("invoice")
                    ->paginate($request->limit > 0 ? $request->limit : 15);
            }

            return $data;
        }
    }

    public function deliveryOrderNumberOnly(Request $request)
    {
        $data = DispatchOrder::whereNull("deleted_at")->with("deliveryOrder")->where("promotion_good_request_id", $request->promotion_good_request_id)->get()->map(function ($data) {
            return $data->deliveryOrder->delivery_order_number;
            //  return "delivery_order_number" = $data->delivery_order_number;
        });
        return $this->response('00', 'success, get status event only', $data);
    }

    public function beforeUpdate(Request $request, $model)
    {
        if (self::hasPickupOrder($model)) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "tidak bisa merubah dispatch, karena dispatch sudah dipickup",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }
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
        $order_number = DB::table('discpatch_order')->whereNull("deleted_at")->orderBy("order_number", "desc")->latest()->first()->order_number ?? 0;
        Warehouse::findOrFail($attributes["id_warehouse"]);

        $warehouse_number = DB::table('warehouses')->whereNull("deleted_at")->where("id", $request->id_warehouse)->first()->code;

        /* get data reference template for dispatch order */
        $receipt = DB::table('proforma_receipts')->whereNull("deleted_at")->where("receipt_for", "3")->orderBy("created_at", "desc")->first();

        $attributes["receipt_id"] = $receipt ? $receipt->id : null;
        $attributes["order_number"] = $order_number + 1;
        $attributes["status"] = "planned";
        $attributes["dispatch_order_number"] = $warehouse_number . "/" . str_pad($order_number + 1, 3, 0, STR_PAD_LEFT);
        $entity->fill($attributes);
        $entity->save();
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        if ($request->has("is_active") && $request->is_active == 0) {
            $attributes["status"] = "canceled";
        }

        $entity->fill($attributes);
        $entity->save();
    }

    /**
     * is dispatch order has receiving good
     * even with invalid delivery order
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public static function hasPickupOrder(DispatchOrder $dispatch_order): bool
    {
        $has_pickup_order = DB::table('pickup_orders as po')
            ->join("pickup_order_dispatches as pod", "po.id", "pod.pickup_order_id")
            ->whereIn("po.status", ["loaded", "planned", "revised"])
            ->where("pod.dispatch_id", $dispatch_order->id)
            ->whereNull("po.deleted_at")
            ->whereNull("pod.deleted_at")
            ->select("po.*")
            ->first();

        return $has_pickup_order ? true : false;
    }
}

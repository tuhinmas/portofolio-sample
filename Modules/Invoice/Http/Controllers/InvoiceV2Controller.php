<?php

namespace Modules\Invoice\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Http\Requests\InvoiceRequest;
use Modules\Invoice\Transformers\InvoiceCollectionResource;
use Modules\Invoice\Transformers\InvoiceResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class InvoiceV2Controller extends Controller
{
    use DisableAuthorization;

    protected $model = Invoice::class;
    protected $request = InvoiceRequest::class;
    protected $resource = InvoiceResource::class;
    protected $collectionResource = InvoiceCollectionResource::class;

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [

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
            'byPersonel',
            'byDealer',
            'byMarketing',
            'byReceivedBy',
            'byRegion',
            'detailByMonth',
            'invoiceListPerDealerPerQuartal',
            'invoicePerDealer',
            'personelBranch',
            'supervisor',
            "byDateBetween",
            "byDealerName",
            "byDeliveryOrderNumber",
            "dispatchOrderhasNotDeliveryOrder",
            "hasDeliveryOrder",
            "hasReceivingGood",
            "goodsReceiptHistory",
            "byOwnerDealerIdDealerName",
            "notCanceled",
            "notReceivedAll",
            "paymentDue",
        ];
    }

    public function includes(): array
    {
        return [
            "confirmedBy",
            "deliveryOrder",
            "deliveryOrder.receivingGoodHasReceived",
            "deliveryOrder.receivingGoodHasReceived.receivedBy",
            "deliveryOrder.receivingGoodHasReceived.receivedBy.position",
            "deliveryOrder.receivingGoodHasReceived.receivingGoodDetail",
            "deliveryOrder.receivingGoods",
            "deliveryOrder.receivingGoods.receivedBy",
            "deliveryOrder.receivingGoods.receivedBy.position",
            "deliveryOrder.receivingGoods.receivingGoodDetail",
            "deliveryOrder.dealer",
            "deliveryOrder.marketing.position",
            "dispatchOrder.deliveryOrder.receivingGoods",
            "dispatchOrder.deliveryOrder",
            "dispatchOrder.dispatchOrderDetail",
            "dispatchOrder",
            "dispatchOrderTest.deliveryOrder",
            "dispatchOrderTest",
            "entrusmentPayment",
            "invoiceProforma",
            "invoiceProforma.personel",
            "payment",
            "receipt.confirmedBy.position",
            "receipt.confirmedBy",
            "receipt",
            "salesOrder.dealer.adress_detail.city",
            "salesOrder.dealer.adress_detail.district",
            "salesOrder.dealer.adress_detail.province",
            "salesOrder.dealer.agencyLevel",
            "salesOrder.dealer.personel.position",
            "salesOrder.dealer.personel",
            "salesOrder.paymentMethod",
            "salesOrder.paymentMethod",
            "salesOrder.personel.position",
            "salesOrder.personel.supervisor",
            "salesOrder.personel",
            "salesOrder.sales_order_detail.product.package",
            "salesOrder.sales_order_detail.product",
            "salesOrder.sales_order_detail",
            "salesOrder",
            "user.personel.position",
            "user.personel",
            "user",
            "lastReceivingGood",
            "dealer",
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
            'user_id',
            'sales_order_id',
            'invoice',
            'deliveryOrder.marketing.name',
            'payment_status',
            'date_delivery',
            'created_at',
            'updated_at',
            "salesOrder.personel_id",
            'delivery_status',
            "salesOrder.order_number",
            "invoiceProforma.invoice_proforma_number",
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return array_merge(column_lists(new $this->model), [
            "salesOrder.personel_id",
            "salesOrder.dealer.dealer_id",
            "dealer.dealer_id",
            "deliveryOrder.date_delivery",
            "deliveryOrder.delivery_order_number",
            'invoiceProforma.proforma_number',
            'invoiceProforma.invoice_proforma_number',

        ]);
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        return $query

        /* order by receiving good */
            ->when($request->sort_by_receiving_good, function ($QQQ) {
                return $QQQ;
            });
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
        $date_receiving_start = null;
        $date_receiving_end = null;
        $filter_by_delivery_order_number = false;
        $delivery_order_number = null;
        $delivery_has_received_only = false;
        if ($request->has("disabled_pagination")) {
            return $query
                ->when($request->has("limit"), function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })

                /* order by last received */
                ->when($request->sort_by_last_receiving_good, function ($QQQ) use ($request) {
                    return $QQQ
                        ->withAggregate("lastReceivingGood", "date_received")
                        ->orderBy("last_receiving_good_date_received", $request->direction ? $request->direction : "desc");
                })

                ->get();
        } else {
            $invoices = $query
                ->when($request->has("limit"), function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })

                /* order by last received */
                ->when($request->sort_by_last_receiving_good, function ($QQQ) use ($request) {
                    return $QQQ
                        ->withAggregate("lastReceivingGood", "date_received")
                        ->orderBy("last_receiving_good_date_received", $request->direction ? $request->direction : "desc");
                })

                /* sort by customer ID */
                ->when(isset($request["sort"]), function ($QQQ) use ($request) {

                    $sort = collect($request["sort"])->pluck("field", "direction");
                    foreach ($sort as $direction => $sort_by) {
                        $direction = match ($direction) {
                            "desc" => "orderByDesc",
                            default => "orderBy",
                        };

                        if ($sort_by == "dealer.dealer_id") {
                            return $QQQ
                                ->{$direction}(
                                    DB::table('dealers as d')
                                        ->join("sales_orders as s", "s.store_id", "d.id")
                                        ->whereColumn('s.id', 'invoices.sales_order_id')
                                        ->select("dealer_id")
                                        ->limit(1)
                                );
                        }
                    }
                    return $QQQ;
                })

                ->paginate($request->limit ? $request->limit : 10);

            if ($request->has("scopes")) {

                foreach ($request->scopes as $scope) {
                    if ($scope["name"] == "hasReceivingGood") {
                        $delivery_has_received_only = true;
                        if (array_key_exists("parameters", $scope)) {
                            if (count($scope["parameters"]) > 1) {
                                $date_receiving_start = $scope["parameters"][1];
                                $date_receiving_end = $scope["parameters"][2];
                            }
                        }
                    }

                    // if($scope["name"] == "marketing") {
                    //     if (array_key_exists("parameters", $scope)) {
                    //         if (count($scope["parameters"]) > 1) {
                    //             $date_receiving_start = $scope["parameters"][1];
                    //             $date_receiving_end = $scope["parameters"][2];
                    //         }
                    //     }
                    // }

                    if ($scope["name"] == "byDeliveryOrderNumber") {
                        $filter_by_delivery_order_number = true;
                        $delivery_order_number = $scope["parameters"][0];
                    }
                }
            }

            /**
             * filter proforma and receiving good
             */
            foreach ($invoices as $invoice) {
                if ($invoice->deliveryOrder) {

                    $delivery_order = $invoice->deliveryOrder;

                    /***
                     * sort delivery order, date received, received by
                     * in fact orion can not sort include yet
                     */
                    if (isset($request["includes"])) {
                        foreach ($request["includes"] as $key => $value) {
                            if (isset($value["sort"])) {
                                $sort = collect($value["sort"])->pluck("field", "direction");
                                $direction = match (array_keys($sort->toArray())[0]) {
                                    "desc" => "sortByDesc",
                                    default => "sortBy",
                                };

                                if ($sort->contains("delivery_order_number")) {
                                    $delivery_order = $delivery_order->{$direction}("delivery_order_number")->values();
                                } elseif ($sort->contains("date_received")) {
                                    $delivery_order = $delivery_order->{$direction}(function ($delivery) {
                                        return $delivery->receivingGoodHasReceived?->date_received;
                                    })->values();
                                } elseif ($sort->contains("receivedBy.name")) {
                                    $delivery_order = $delivery_order
                                        ->{$direction}(function ($delivery) {
                                            return Str::title($delivery?->receivingGoods?->receivedBy?->name);
                                        })->values();
                                }
                            }
                        }
                    }

                    $invoice->unsetRelation("deliveryOrder");
                    $invoice->delivery_order = collect($delivery_order)
                        ->filter(function ($deliveryOrder, $key) use (
                            $filter_by_delivery_order_number,
                            $delivery_has_received_only,
                            $delivery_order_number,
                            $date_receiving_start,
                            $date_receiving_end,
                        ) {
                            if ($delivery_has_received_only) {
                                if (!$deliveryOrder->receivingGoodHasReceived) {
                                    return false;
                                }

                                if ($deliveryOrder->receivingGoodHasReceived) {
                                    if (!is_null($date_receiving_start) && !is_null($date_receiving_end)) {

                                        if ($deliveryOrder->receivingGoodHasReceived->date_received >= $date_receiving_start
                                            &&
                                            $deliveryOrder->receivingGoodHasReceived->date_received <= $date_receiving_end) {
                                            return $deliveryOrder;
                                        }
                                        return $deliveryOrder;
                                    } else {
                                        return $deliveryOrder;
                                    }
                                }
                            } else {
                                return true;
                            }
                        })
                        ->reject(function ($deliveryOrder, $key) use ($delivery_has_received_only) {
                            if ($delivery_has_received_only) {
                                if (!$deliveryOrder->receivingGoodHasReceived) {
                                    return $deliveryOrder;
                                }
                            }
                        })
                        ->values();

                    if ($filter_by_delivery_order_number) {
                        // dd("test");

                        $invoice->delivery_order = collect($invoice->delivery_order)
                            ->filter(function ($delivery_order) use ($delivery_order_number) {
                                $contains = Str::contains($delivery_order->delivery_order_number, $delivery_order_number);
                                if ($contains) {
                                    return $delivery_order;
                                }
                            })
                            ->values();
                    }
                }
            }

            return $invoices;
        }
    }
}

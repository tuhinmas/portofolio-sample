<?php

namespace Modules\DistributionChannel\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Actions\GetQuantityLoadedByProductAction;
use Modules\DistributionChannel\Actions\GetQuantityOrderByProductAndDispatchAction;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\DistributionChannel\Http\Requests\DispatchOrderDetailRequest;
use Modules\DistributionChannel\Transformers\DispatchOrderDetailCollectionResource;
use Modules\DistributionChannel\Transformers\DispatchOrderDetailResource;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class DispatchOrderDetailController extends Controller
{
    use ResponseHandler;

    protected $model = DispatchOrderDetail::class;
    protected $request = DispatchOrderDetailRequest::class;
    protected $resource = DispatchOrderDetailResource::class;
    protected $collectionResource = DispatchOrderDetailCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "product.package",
        ];
    }

    public function includes(): array
    {
        return [
            'product.package',
            'dispatchOrder.addressDelivery',
            'dispatchOrder.addressDelivery.province',
            'dispatchOrder.addressDelivery.city',
            'dispatchOrder.addressDelivery.district',
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
            "id_dispatch_order",
            "id_product",
            "quantity_packet_to_send",
            "package_weight",
            "date_received",
            'created_at',
            'updated_at',
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
            "id_dispatch_order",
            "id_product",
            "quantity_packet_to_send",
            "package_weight",
            "date_received",
            'created_at',
            'updated_at',
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
            "id_dispatch_order",
            "id_product",
            "quantity_packet_to_send",
            "package_weight",
            "date_received",
            'created_at',
            'updated_at',
        ];
    }

    public function afterStore(Request $request, $model)
    {
        if ($request->has("resources")) {

            /* qty order */
            $sales_order_detail = (new GetQuantityOrderByProductAndDispatchAction)($model->id_dispatch_order, $model->id_product);
            $response = null;
            if ($sales_order_detail?->quantity < $model->quantity_unit) {
                $response = $this->response("04", "invalid data send", [
                    "quantity_unit" => [
                        "quantity can not higher than quantity order",
                    ],
                ], 422);

            }

            if ($sales_order_detail?->quantity < $model->planned_quantity_unit) {
                $response = $this->response("04", "invalid data send", [
                    "planned_quantity_unit" => [
                        "quantity can not higher than quantity order",
                    ],
                ], 422);

            }

            if ($response) {
                throw new HttpResponseException($response);
            }

            $qty_loaded = (new GetQuantityLoadedByProductAction)($sales_order_detail?->invoice_id, $model->id_product);
            if ($qty_loaded > $sales_order_detail?->quantity) {
                $response = $this->response("04", "invalid data send", [
                    "quantity_unit" => [
                        "quantity can not higher than quantity remaining",
                    ],
                ], 422);
            }

            if ($qty_loaded > $sales_order_detail?->quantity) {
                $response = $this->response("04", "invalid data send", [
                    "planned_quantity_unit" => [
                        "quantity can not higher than quantity remaining",
                    ],
                ], 422);
            }

            if ($response) {
                throw new HttpResponseException($response);
            }
        }
    }

    public function afterUpdate(Request $request, $model)
    {

        if ($request->has("resources")) {

            /* qty order */
            $sales_order_detail = (new GetQuantityOrderByProductAndDispatchAction)($model->id_dispatch_order, $model->id_product);
            $response = null;
            if ($sales_order_detail?->quantity < $model->quantity_unit) {
                $response = $this->response("04", "invalid data send", [
                    "quantity_unit" => [
                        "quantity can not higher than quantity order",
                    ],
                ], 422);

            }

            if ($sales_order_detail?->quantity < $model->planned_quantity_unit) {
                $response = $this->response("04", "invalid data send", [
                    "planned_quantity_unit" => [
                        "quantity can not higher than quantity order",
                    ],
                ], 422);

            }

            if ($response) {
                throw new HttpResponseException($response);
            }

            $qty_loaded = (new GetQuantityLoadedByProductAction)($sales_order_detail?->invoice_id, $model->id_product, $model->id);

            if ($qty_loaded + $model->quantity_unit > $sales_order_detail?->quantity) {
                $response = $this->response("04", "invalid data send", [
                    "quantity_unit" => [
                        "quantity can not higher than quantity remaining",
                    ],
                ], 422);
            }

            if ($qty_loaded + $model->planned_quantity_unit > $sales_order_detail?->quantity) {
                $response = $this->response("04", "invalid data send", [
                    "planned_quantity_unit" => [
                        "quantity can not higher than quantity remaining",
                    ],
                ], 422);
            }

            if ($response) {
                throw new HttpResponseException($response);
            }
        }
    }

    public function dispatchOrderDetailWithQuantityReceived(Request $request, DispatchOrderDetail $dispatch_order_detail)
    {
        try {
            $dipacth_order_detail = $dispatch_order_detail->query()
                ->with([
                    "dispatchOrder" => function ($QQQ) {
                        return $QQQ
                            ->with([
                                "invoice" => function ($QQQ) {
                                    return $QQQ
                                        ->whereHas("salesOrderOnly")
                                        ->with([
                                            "dispatchOrder" => function ($QQQ) {
                                                return $QQQ->with([
                                                    "dispatchOrderDetail",
                                                    "deliveryOrder" => function ($QQQ) {
                                                        return $QQQ->with([
                                                            "receivingGoods" => function ($QQQ) {
                                                                return $QQQ
                                                                    ->whereHas("receivingGoodDetail", function ($QQQ) {
                                                                        return $QQQ->where("status", "delivered");
                                                                    })
                                                                    ->with([
                                                                        "receivingGoodDetail" => function ($QQQ) {
                                                                            return $QQQ->where("status", "delivered");
                                                                        },
                                                                    ]);
                                                            },
                                                        ]);
                                                    },
                                                ]);
                                            },
                                            "salesOrderOnly" => function ($QQQ) {
                                                return $QQQ
                                                    ->whereHas("sales_order_detail")
                                                    ->with([
                                                        "sales_order_detail" => function ($QQQ) {
                                                            return $QQQ->with("package");
                                                        },
                                                    ]);
                                            },
                                        ]);
                                },
                                "dispatchOrderDetail",
                                "deliveryOrder" => function ($QQQ) {
                                    return $QQQ
                                        ->whereHas("receivingGoods")
                                        ->with([
                                            "receivingGoods" => function ($QQQ) {
                                                return $QQQ
                                                    ->whereHas("receivingGoodDetail", function ($QQQ) {
                                                        return $QQQ->where("status", "delivered");
                                                    })
                                                    ->with([
                                                        "receivingGoodDetail" => function ($QQQ) {
                                                            return $QQQ->where("status", "delivered");
                                                        },
                                                    ]);
                                            },
                                        ]);
                                },
                            ])
                            ->whereHas("invoice");
                    },
                    "product",

                ])
                ->whereHas("dispatchOrder", function ($QQQ) {
                    return $QQQ->whereHas("invoice");
                })
                ->when($request->has("dispatch_order_id"), function ($QQQ) use ($request) {
                    return $QQQ->where("id_dispatch_order", $request->dispatch_order_id);
                })->with('product.package','salesOrderDetail')
                ->get();

            /**
             * get received product
             */
            $dispatch_order_received = collect();
            foreach ($dipacth_order_detail as $detail) {
                $quantity_unit_received = 0;
                $quantity_package_received = 0;
                $plannedUnit = 0;
                $plannedPackageUnit = 0;

                $sentUnit = 0;
                $sentPackageUnit = 0;

                if ($detail->dispatchOrder->invoice->dispatchOrder) {
                    $detail->dispatch_order = collect($detail->dispatchOrder->invoice->dispatchOrder)
                        ->map(function ($dispatch_order, $key) use (&$quantity_unit_received, &$quantity_package_received, $detail, &$dispatch_order_detail) {
                            if ($dispatch_order->dispatchOrderDetail) {
                                $dispatch_order_detail = collect($dispatch_order->dispatchOrderDetail)->where("id_product", $detail->id_product)->values();
                                if (count($dispatch_order_detail) > 0) {
                                    return $dispatch_order;
                                }
                            }
                        })
                        ->filter(function ($dispatch_order, $key) {
                            if ($dispatch_order) {
                                return $dispatch_order;
                            }
                        })
                        ->values();

                    foreach ($detail->dispatch_order as $dispatch_order) {
                        if ($dispatch_order->deliveryOrder) {
                            if ($dispatch_order->deliveryOrder->receivingGoods) {
                                if ($dispatch_order->deliveryOrder->receivingGoods->delivery_status == "2") {
                                    if ($dispatch_order->deliveryOrder->receivingGoods->receivingGoodDetail) {
                                        $quantity_unit_received += collect($dispatch_order->deliveryOrder->receivingGoods->receivingGoodDetail)->where("status", "delivered")->where("product_id", $detail->id_product)->sum("quantity");
                                        $quantity_package_received += collect($dispatch_order->deliveryOrder->receivingGoods->receivingGoodDetail)->where("status", "delivered")->where("product_id", $detail->id_product)->sum("quantity_package");
                                        $dispatch_order_received->push($dispatch_order->id);
                                    }
                                }
                            }
                        }

                        $plannedUnit += collect($dispatch_order->dispatchOrderDetail)
                            ->where("id_product", $detail->id_product)
                            ->where('quantity_unit', 0)
                            ->where('dispatch_order_id', '!=', $detail->dispatchOrder->id)
                            ->sum("planned_quantity_unit");

                        $plannedPackageUnit += collect($dispatch_order->dispatchOrderDetail)
                            ->where("id_product", $detail->id_product)
                            ->where('quantity_packet_to_send', 0)
                            ->where('dispatch_order_id', '!=', $detail->dispatchOrder->id)
                            ->sum("planned_package_to_send");
                    }
                }

                $detail->quantity_unit_received = $quantity_unit_received;

                /* received product conersion to package */
                $package = collect($detail->dispatchOrder->invoice->salesOrderOnly->sales_order_detail)->where("product_id", $detail->id_product)->first();
                if ($package) {
                    $package = $package->quantity_on_package > 0 ? $package->quantity_on_package : 1;
                } else {
                    $package = 1;
                }

                // $detail->quantity_package_received = $quantity_unit_received
                // ? floor($quantity_unit_received / $package)
                // : 0;

                $detail->quantity_package_received = $quantity_package_received;

                /**
                 * get loaded product, has received or
                 * was set to delivery
                 */
                $quantity_unit_loaded = 0;
                $quantity_package_loaded = 0;

                /* get all dispatch order which not received yet */
                $dispatch_order_not_received = collect($detail->dispatchOrder->invoice->dispatchOrder)->whereNotIn("id", $dispatch_order_received)->toArray();

                if (count($dispatch_order_not_received) > 0) {
                    foreach ($dispatch_order_not_received as $dispatch_order) {

                        $dispatch_order = (object) $dispatch_order;
                        $quantity_unit_loaded += collect($dispatch_order->dispatch_order_detail)
                            ->where("id_product", $detail->id_product)
                            ->sum("quantity_unit");

                        $quantity_package_loaded += collect($dispatch_order->dispatch_order_detail)
                            ->where("id_product", $detail->id_product)
                            ->sum("quantity_packet_to_send");

                    }
                }

                /**
                 * package check
                 */
                $package_on_purchase = collect($detail->dispatchOrder->invoice->salesOrderOnly->sales_order_detail)->where("product_id", $detail->id_product)->first();
                if ($package_on_purchase) {
                    $package_on_purchase = $package_on_purchase->package ? $package_on_purchase->package : "-";
                } else {
                    $package_on_purchase = "-";
                }

                /* get quntiy unit order */
                $order_quantity_unit = collect($detail->dispatchOrder->invoice->salesOrderOnly->sales_order_detail)->where("product_id", $detail->id_product)->sum("quantity");

                /* get quantity package order */
                $order_quantity_package = $package_on_purchase != "-" ? $order_quantity_unit / $package_on_purchase->quantity_per_package : 0;

                $detail->quantity_unit_loaded = $quantity_unit_loaded + $quantity_unit_received;
                $detail->quantity_package_loaded = $quantity_package_loaded + $quantity_package_received;

                $detail->order_quantity_unit = $order_quantity_unit;
                $detail->order_quantity_package = $package_on_purchase != "-" ? $order_quantity_unit / $package_on_purchase->quantity_per_package : null;

                $historySend = DispatchOrderDetail::select(DB::raw('SUM(quantity_unit) as unit'), DB::raw('SUM(quantity_packet_to_send) as package'))->where('id_product', $detail->id_product)->whereHas('dispatchOrder.deliveryOrder', function ($q) {
                    return $q->has('receivingGoods');
                })->whereHas('dispatchOrder.invoice', function ($q) use ($detail) {
                    return $q->where('id', $detail->dispatchOrder->invoice->id);
                })->first();

                $detail->sent_unit = $historySend->unit;
                $detail->sent_package_unit = $historySend->package;

                $salesOrderDetail = SalesOrderDetail::where('product_id', $detail->id_product)->where('sales_order_id', $detail->dispatchOrder->invoice->salesOrderOnly->id)->first();

                if ($detail->product->package != null) {
                    $sjCountPackageUnitDrafted = DispatchOrderDetail::where('id_product', $detail->id_product)->whereHas('dispatchOrder.deliveryOrder', function ($q) {
                        return $q->whereDoesntHave('receivingGoods');
                    })->whereHas('dispatchOrder.invoice', function ($q) use ($detail) {
                        return $q->where('id', $detail->dispatchOrder->invoice->id);
                    })->sum('quantity_packet_to_send');
                    $detail->remaining_unit = ($salesOrderDetail->quantity / $salesOrderDetail->quantity_on_package) - ($plannedPackageUnit + $sjCountPackageUnitDrafted + $quantity_package_received);
                } else {
                    $sjCountUnitDrafted = DispatchOrderDetail::where('id_product', $detail->id_product)->whereHas('dispatchOrder.deliveryOrder', function ($q) {
                        return $q->whereDoesntHave('receivingGoods');
                    })->whereHas('dispatchOrder.invoice', function ($q) use ($detail) {
                        return $q->where('id', $detail->dispatchOrder->invoice->id);
                    })->sum('quantity_unit');
                    $detail->remaining_unit = $salesOrderDetail->quantity - ($plannedUnit + $sjCountUnitDrafted + $quantity_unit_received);
                }

                $detail->package_on_purchase = $package_on_purchase;
                $detail->unsetRelation("dispatchOrder");
                unset($detail->dispatch_order);
            }

            return $this->response("00", "success", $dipacth_order_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]);
            return $this->response("01", "failed", $th->getMessage());
        }
    }
}

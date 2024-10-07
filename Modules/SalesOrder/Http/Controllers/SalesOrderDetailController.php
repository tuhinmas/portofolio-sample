<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Models\ExportRequests;
use App\Traits\DistributorStock;
use App\Traits\ResponseHandler;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\PointProduct;
use Modules\DataAcuan\Entities\Price;
use Modules\DataAcuan\Entities\Product;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\KiosDealer\Entities\Store;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\v2\SalesOrderDetail as SOD;
use Modules\SalesOrder\Events\DeletedProductEvent;
use Modules\SalesOrder\Events\FeeMarketingPerProductEvent;

class SalesOrderDetailController extends Controller
{
    use ResponseHandler;
    use DistributorStock;

    public function __construct(SalesOrder $sales_order, SalesOrderDetail $sales_order_detail, Package $package, Product $product, Price $price, Store $store)
    {
        $this->sales_order = $sales_order;
        $this->sales_order_detail = $sales_order_detail;
        $this->package = $package;
        $this->product = $product;
        $this->price = $price;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $with = [
                'product',
                'package',
                'sales_order' => function ($QQQ) {
                    return $QQQ
                        ->whereHas("invoice")
                        ->with([
                            "invoice" => function ($QQQ) {
                                return $QQQ
                                    ->with([
                                        "dispatchOrder" => function ($QQQ) {
                                            return $QQQ
                                                ->with([
                                                    "dispatchOrderDetail",
                                                    "deliveryOrder" => function ($QQQ) {
                                                        return $QQQ
                                                            ->orderBy("date_delivery")
                                                            ->where("status", "send")
                                                            // ->whereHas("receivingGoods", function ($QQQ) {
                                                            //     return $QQQ
                                                            //         ->where("delivery_status", "!=", "1")
                                                            //         ->whereHas("receivingGoodDetail", function ($QQQ) {
                                                            //             return $QQQ->where("status", "delivered");
                                                            //         });
                                                            // })
                                                            // ->orWhereDoesntHave("receivingGoods")
                                                            ->with([
                                                                "receivingGoods" => function ($QQQ) {
                                                                    return $QQQ
                                                                        ->where("delivery_status", "!=", "1")
                                                                        ->orderBy("date_received")
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
                                    ]);
                            },
                            'statusFee',
                        ]);
                },
            ];

            if ($request->has("sales_order_id")) {
                $sales_order = SalesOrder::findOrFail($request->sales_order_id);
                if ($sales_order->type == "2") {
                    $with = [
                        'product',
                        'package',
                        'sales_order',
                    ];
                }
            }
            $sales_order_detail = $this->sales_order_detail->query()
                ->with($with)
                ->where('sales_order_id', $request->sales_order_id)
                ->get();

            /**
             * get received product
             */
            if ($request->has("sales_order_id")) {
                $sales_order = SalesOrder::findOrFail($request->sales_order_id);

                if ($sales_order->type == "1") {
                    foreach ($sales_order_detail as $detail) {
                        $sjDrafted = 0;
                        $plannedUnit = 0;
                        $plannedPackageUnit = 0;
                        $sumProductFailedUnit = 0;
                        $sumProductFailedPackageUnit = 0;
                        $quantity_unit_received = 0;
                        $quantity_package_received = 0;
                        $dispatch_order_id = collect([]);
                        $dispatch_order_detail = null;
                        if ($detail->sales_order->invoice->dispatchOrder) {
                            $detail->dispatch_order = collect($detail->sales_order->invoice->dispatchOrder)
                                ->map(function ($dispatch_order, $key) use (&$quantity_unit_received, $detail, &$dispatch_order_detail) {
                                    if ($dispatch_order->dispatchOrderDetail) {
                                        $dispatch_order_detail = collect($dispatch_order->dispatchOrderDetail)
                                            ->where("id_product", $detail->product_id)
                                            ->values();

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

                            foreach ($detail->dispatch_order->where('is_active', 1) as $dispatch_order) {
                                if ($dispatch_order->deliveryOrder) {
                                    if ($dispatch_order->deliveryOrder->receivingGoods) {
                                        if ($dispatch_order->deliveryOrder->receivingGoods->delivery_status == "2") {
                                            if ($dispatch_order->deliveryOrder->receivingGoods->receivingGoodDetail) {
                                                $quantity_unit_received += collect($dispatch_order->deliveryOrder->receivingGoods->receivingGoodDetail)->where("status", "delivered")->where("product_id", $detail->product_id)->sum("quantity");
                                                $quantity_package_received += collect($dispatch_order->deliveryOrder->receivingGoods->receivingGoodDetail)->where("status", "delivered")->where("product_id", $detail->product_id)->sum("quantity_package");
                                                $dispatch_order_id->push($dispatch_order->id);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $plannedUnit = DispatchOrderDetail::query()
                            ->where('id_product', $detail->product_id)
                            ->whereHas('dispatchOrder', function ($q) {
                                return $q->whereDoesntHave('deliveryOrder', function ($QQQ) {
                                    return $QQQ->where("status", "send");
                                })->where('is_active', 1);
                            })
                            ->whereHas('dispatchOrder.invoice', function ($q) use ($detail) {
                                return $q->where('sales_order_id', $detail->sales_order_id);
                            })
                            ->sum('planned_quantity_unit');

                        /* dispatch that doesnt have valid delivery order */
                        $plannedPackageUnit = DispatchOrderDetail::query()
                            ->where('id_product', $detail->product_id)
                            ->whereHas('dispatchOrder', function ($q) {
                                return $q->whereDoesntHave('deliveryOrder', function ($QQQ) {
                                    return $QQQ->where("status", "send");
                                })
                                ->where('is_active', 1);
                            })
                            ->whereHas('dispatchOrder.invoice', function ($q) use ($detail) {
                                return $q->where('sales_order_id', $detail->sales_order_id);
                            })
                            ->sum('planned_package_to_send');

                        $detail->quantity_unit_received = $quantity_unit_received;

                        /**
                         * quantity package received
                         */
                        $detail->quantity_package_received = $quantity_package_received;

                        /**
                         * get loaded product, has received or
                         * was set to delivery
                         */
                        $quantity_unit_loaded = 0;
                        $quantity_package_loaded = 0;
                        $sentUnit = 0;
                        $sentPackageUnit = 0;
                        if ($detail->dispatch_order) {

                            foreach (collect($detail->dispatch_order)->where('is_active', 1)->whereNotIn("id", $dispatch_order_id) as $dispatchOrder) {

                                /* dispatch order does not received */
                                if ($dispatchOrder) {
                                    $quantity_unit_loaded += collect($dispatchOrder->dispatchOrderDetail)
                                        ->where("id_product", $detail->product_id)
                                        ->sum(function ($detail) use ($dispatchOrder) {

                                            if ($dispatchOrder?->deliveryOrder?->status == "send") {
                                                return $detail->quantity_unit;
                                            }
                                            return $detail->planned_quantity_unit;
                                        });

                                    $quantity_package_loaded += collect($dispatchOrder->dispatchOrderDetail)
                                        ->where("id_product", $detail->product_id)
                                        ->sum(function ($detail) use ($dispatchOrder) {

                                            if ($dispatchOrder->deliveryOrder) {
                                                if ($dispatchOrder?->deliveryOrder?->status == "send") {
                                                    return $detail->quantity_packet_to_send;
                                                }
                                            }
                                            return $detail->planned_package_to_send;
                                        });

                                }
                            }

                            $dispatch_order_detail_was_send = collect($detail->dispatch_order)
                                ->filter(fn($dispatch) => $dispatch->is_active == true)
                                ->reject(fn($dispatch) => !$dispatch->deliveryOrder)
                                ->filter(fn($dispatch) => $dispatch->deliveryOrder?->status == "send")
                                ->pluck("dispatchOrderDetail")
                                ->flatten()
                                ->filter(function ($dispatch) use ($detail) {
                                    return $dispatch->id_product == $detail->product_id;
                                });

                            $sentUnit = $dispatch_order_detail_was_send->sum("quantity_unit");
                            $sentPackageUnit = $dispatch_order_detail_was_send->sum("quantity_packet_to_send");

                            // foreach ($dispatch_orders as $dispatchOrder) {

                            //     /* dispatch order has delivery order */
                            //     if ($dispatchOrder) {
                            //         $sentUnit += collect($dispatchOrder->dispatchOrderDetail)
                            //             ->where("id_product", $detail->product_id)
                            //             ->sum("quantity_unit");

                            //         $sentPackageUnit += collect($dispatchOrder->dispatchOrderDetail)
                            //             ->where("id_product", $detail->product_id)
                            //             ->sum("quantity_packet_to_send");
                            //     }
                            // }
                        }

                        $load_weight = $detail->quantity_on_package > 0 ? (($detail->package_weight * $detail->quantity) / $detail->quantity_on_package) : $detail->quantity * $detail->product->weight;
                        $detail->quantity_unit_loaded = $quantity_unit_loaded + $quantity_unit_received;
                        $detail->quantity_package_loaded = $quantity_package_loaded + $detail->quantity_package_received;
                        $detail->load_weight = $load_weight;
                        $detail->sent_unit = $sentUnit;
                        $detail->sent_package_unit = $sentPackageUnit;

                        if ($detail->package != null) {
                            /**
                             * dispatch order that has valid delivery order
                             * but not received
                             */
                            $sjCountPackageUnitDrafted = DispatchOrderDetail::query()
                                ->where('id_product', $detail->product_id)
                                ->whereHas('dispatchOrder.deliveryOrder', function ($QQQ) {
                                    return $QQQ
                                        ->where("status", "send")
                                        ->whereDoesntHave('receivingGoods', function ($QQQ) {
                                            return $QQQ->where("delivery_status", "2");
                                        });
                                })
                                ->whereHas('dispatchOrder.invoice', function ($q) use ($detail) {
                                    return $q->where('sales_order_id', $detail->sales_order_id);
                                })
                                ->sum('quantity_packet_to_send');

                            $detail->remaining_unit = ($detail->quantity / $detail->quantity_on_package) - ($plannedPackageUnit + $sjCountPackageUnitDrafted + $quantity_package_received);
                        } else {
                            $sjCountUnitDrafted = DispatchOrderDetail::query()
                                ->where('id_product', $detail->product_id)
                                ->whereHas('dispatchOrder.deliveryOrder', function ($q) {
                                    return $q
                                        ->where("status", "send")
                                        ->whereDoesntHave('receivingGoods', function ($QQQ) {
                                            return $QQQ->where("delivery_status", "2");
                                        });
                                })
                                ->whereHas('dispatchOrder.invoice', function ($q) use ($detail) {
                                    return $q->where('sales_order_id', $detail->sales_order_id);
                                })
                                ->sum('quantity_unit');
                            $detail->remaining_unit = $detail->quantity - ($plannedUnit + $sjCountUnitDrafted + $quantity_unit_received);
                        }

                        /* delivery status */
                        $delivery_status = "undone";

                        /* check package unit quantity if exist */
                        if ($detail->quantity_unit_loaded >= $detail->quantity) {
                            $delivery_status = "done";
                        }

                        $detail->delivery_status = $delivery_status;
                        unset($detail->dispatch_order);
                    }
                }
            }

            return $this->response("00", "sales order detail index", $sales_order_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to display sales order detail index", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('salesorder::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "sales_order_id" => "required|max:255",
            "product_id" => "required|max:255",
            "quantity" => "required|max:255",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        try {
            $package_check = $this->package_check($request->product_id);
            $package_id = $package_check->package_id;
            $package_name = $package_check->packaging;
            $quantity_on_package = $package_check->quantity_per_package;
            $quantity = $request->quantity * $package_check->quantity_per_package;
            $package_weight = $package_check->package_weight;

            if ($request->has("quantity_on_package")) {
                $quantity_on_package = $request->quantity_on_package;
            }

            $agency_level_id = $this->agencyLevelCheck($request, $quantity);
            if ($request->has("agency_level_id")) {
                $agency_level_id = $request->agency_level_id;
            }

            if ($request->has("only_unit")) {
                if ($request->only_unit) {
                    $quantity = $request->quantity;
                    $sales_order_detail = $this->sales_order_detail->updateOrCreate([
                        'sales_order_id' => $request->sales_order_id,
                        'product_id' => $request->product_id,
                        'package_id' => null,
                    ], [
                        'quantity' => $quantity,
                        'quantity_order' => $quantity,
                        'stock' => $quantity,
                        'unit_price' => $request->unit_price,
                        'total' => $request->total,
                        'agency_level_id' => $agency_level_id,
                        'retail_point' => $request->store_point,
                        'marketing_point' => $request->marketing_point,
                        'marketing_fee' => $request->marketing_fee,
                    ]);
                } else {
                    $sales_order_detail = $this->sales_order_detail->updateOrCreate([
                        'sales_order_id' => $request->sales_order_id,
                        'product_id' => $request->product_id,
                    ], [
                        'quantity' => $quantity,
                        'quantity_order' => $quantity,
                        'stock' => $quantity,
                        'unit_price' => $request->unit_price,
                        'total' => $request->total,
                        'agency_level_id' => $agency_level_id,
                        'retail_point' => $request->store_point,
                        'marketing_point' => $request->marketing_point,
                        'marketing_fee' => $request->marketing_fee,
                        'package_id' => $package_id,
                        'package_name' => $package_name,
                        'quantity_on_package' => $quantity_on_package,
                        'package_weight' => $package_weight,
                        'package_name' => $package_check->packaging,
                    ]);
                }
            } else {
                $sales_order_detail = $this->sales_order_detail->updateOrCreate([
                    'sales_order_id' => $request->sales_order_id,
                    'product_id' => $request->product_id,
                ], [
                    'quantity' => $quantity,
                    'quantity_order' => $quantity,
                    'stock' => $quantity,
                    'unit_price' => $request->unit_price,
                    'total' => $request->total,
                    'agency_level_id' => $agency_level_id,
                    'retail_point' => $request->store_point,
                    'marketing_point' => $request->marketing_point,
                    'marketing_fee' => $request->marketing_fee,
                    'package_id' => $package_id,
                    'package_name' => $package_name,
                    'quantity_on_package' => $quantity_on_package,
                    'package_weight' => $package_weight,
                    'package_name' => $package_check->packaging,
                ]);
            }

            $sales_order_detail = $this->sales_order_detail->query()
                ->with('product', 'package')
                ->where('id', $sales_order_detail->id)
                ->first();

            if (is_null($request->marketing_point)) {

                $point = PointProduct::where('product_id', $sales_order_detail->product_id)->where('year', Carbon::now()->format('Y'))->orderBy('minimum_quantity', 'desc')->get();

                $groupByEu = collect($point)->groupBy('product_id');
                $array_final_eu = [];

                foreach ($groupByEu as $pointv) {
                    $eucledian = $request->quantity / $pointv[0]->minimum_quantity;
                    $final_eucledian = $eucledian * $pointv[0]->point;
                    $array_final_eu[] = $final_eucledian;
                }

                $eucledian_sum = array_sum($array_final_eu);

                $sales_order_detail->marketing_point = 0;
                $sales_order_detail->save();
            }

            return $this->response("00", "sales order detail saved", $sales_order_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save sales order detail", $th->getMessage(), 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $sales_order_detail = $this->sales_order_detail->query()
                ->where('id', $id)
                ->with('product', 'package')
                ->first();

            $package_check = $this->package_check($sales_order_detail->product_id);
            $sales_order_detail->quantity = $sales_order_detail->quantity / $package_check->quantity_per_package;
            // $sales_order_detail->packaging = $package_check->packaging;
            $sales_order_detail->packaging = $sales_order_detail->package_name;
            return $this->response("00", "sales order detail", $sales_order_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to display sales order detail", $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $sales_order_detail = $this->sales_order_detail->query()
            ->where('id', $id)
            ->with('product', 'package')
            ->first();
        $package_check = $this->package_check($sales_order_detail->product_id);
        $sales_order_detail->quantity = $sales_order_detail->quantity / $package_check->quantity_per_package;
        $sales_order_detail->packaging = $sales_order_detail->package_name;

        return response()->json([
            'response_code' => '00',
            'response_message' => 'sales order edit',
            'data' => $sales_order_detail,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $package_check = $this->package_check($request->product_id);
            $package_id = $package_check->package_id;
            $package_name = $package_check->packaging;
            $quantity_on_package = $package_check->quantity_per_package;
            $quantity = $request->quantity * $package_check->quantity_per_package;
            $package_weight = $package_check->package_weight;

            if ($request->package_id) {
                $package_check = $this->packageCheckById($request->product_id, $request->package_id);
                if ($package_check->status == "01") {
                    return $this->response("01", "failed, this package is invalid package for this product", "choose another one");
                }
                $package_id = $request->package_id;
                $package_name = $package_check->packaging;
                $quantity_on_package = $package_check->quantity_per_package;
                $quantity = $request->quantity * $package_check->quantity_per_package;
                $package_weight = $package_check->package_weight;
            }

            $agency_level_id = $this->agencyLevelCheck($request, $quantity);

            if ($request->has("agency_level_id")) {
                $agency_level_id = $request->agency_level_id;
            }

            $new_request = $request->all();
            $sales_order_detail = $this->sales_order_detail->query()
                ->with([
                    "sales_order" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice" => function ($QQQ) {
                                return $QQQ
                                    ->with([
                                        "invoiceProforma",
                                        "dispatchOrder" => function ($QQQ) {
                                            return $QQQ
                                                ->orderBy("date_delivery")
                                                ->with([
                                                    "deliveryOrder" => function ($QQQ) {
                                                        return $QQQ->where("status", "send");
                                                    },
                                                ]);
                                        },
                                    ]);
                            },
                        ]);
                    },
                ])
                ->findOrFail($id);

            if ($request->has("quantity")) {
                // if ($sales_order_detail->sales_order->invoice) {

                /**
                 * product can not be updated if there
                 * invoice issued
                 */
                // if ($sales_order_detail->sales_order->invoice->invoiceProforma) {
                //     return $this->response("01", "failed to update sales order detail", "invoice for this order has been issued at " . $sales_order_detail->sales_order->invoice->invoiceProforma->created_at);
                // }

                /**
                 * if delivery order issued, product
                 * also can not be updated
                 */
                // else if ($sales_order_detail->sales_order->invoice->dispatchOrder) {

                //     /* check delivery order */
                //     $sales_order_detail_with_delivery_order = collect($sales_order_detail->sales_order->invoice->dispatchOrder)
                //         ->map(function ($dispatchOrder, $key) {
                //             if ($dispatchOrder->deliveryOrder) {
                //                 return $dispatchOrder;
                //             }
                //         })
                //         ->filter(function ($dispatchOrder, $key) {
                //             return $dispatchOrder != null;
                //         })
                //         ->sortBy(function ($dispatchOrder, $key) {
                //             return $dispatchOrder->deliveryOrder->date_delivery;
                //         })
                //         ->first();

                //     if ($sales_order_detail_with_delivery_order) {
                //         return $this->response("01", "failed to update sales order detail", "delivery order has been issued for this order at " . $sales_order_detail_with_delivery_order->created_at, 500);
                //     }
                // }

                /**
                 * if proforma was greater than 2 days
                 * product cannot be updated
                 */
                // if ($sales_order_detail->sales_order->invoice->created_at->diffInDays(now()) > 3) {
                //     return $this->response("01", "failed to update sales order detail", "proforma for this order was published more than 2 days ago so you can update quantity of this order, proforma published at: " . $sales_order_detail->sales_order->invoice->created_at, 500);
                // }
                // }
            }

            if ($request->has('only_unit')) {
                $quantity = $request->quantity;
            }

            unset($new_request["only_unit"]);
            unset($new_request["agency_level_id"]);
            unset($new_request["package_id"]);
            unset($new_request["package_name"]);
            unset($new_request["quantity_on_package"]);
            unset($new_request["quantity"]);
            unset($new_request["package_weight"]);

            foreach ($new_request as $key => $value) {
                $sales_order_detail[$key] = $value;
            }

            $sales_order_detail->quantity = $quantity;
            $sales_order_detail->quantity_order = $quantity;
            $sales_order_detail->stock = $quantity;
            $sales_order_detail->agency_level_id = $agency_level_id;
            $sales_order_detail->package_id = $package_id;
            $sales_order_detail->package_name = $package_name;
            $sales_order_detail->quantity_on_package = $quantity_on_package;
            $sales_order_detail->package_weight = $package_weight;
            $sales_order_detail->discount_percentage = $request->discount_percentage;

            $sales_order_detail->save();

            $sales_order_detail = $this->sales_order_detail->query()
                ->with('product', 'package')
                ->where('id', $sales_order_detail->id)
                ->first();
            $stock = 0;

            if (!$request->has('only_unit')) {
                $sales_order_detail->quantity = $sales_order_detail->quantity / $package_check->quantity_per_package;
                $stock = $sales_order_detail->quantity;
                $sales_order_detail->stock = $stock;
                $sales_order_detail->quantity_order = $sales_order_detail->quantity;
                $sales_order_detail->packaging = $package_check->packaging;
            }

            if ($request->type == 1) {
                $export_request_check_detail = DB::table('export_requests')->where("type", "sales_order_direct_detail")->where("status", "requested")->first();

                $type = "sales_order_direct";
                $type_sales_order = "sales_order_direct_detail";
            } else {
                $export_request_check_detail = DB::table('export_requests')->where("type", "sales_order_indirect_detail")->where("status", "requested")->first();

                $type = "sales_order_indirect";
                $type_sales_order = "sales_order_indirect_detail";
            }

            if (!$export_request_check_detail) {
                ExportRequests::Create([
                    "type" => $type_sales_order,
                    "status" => "requested",
                    "created_at" => now(),
                ]);
            }

            /* calculate fee */
            $fee_marketing = FeeMarketingPerProductEvent::dispatch($sales_order_detail->salesOrder);

            return response()->json([
                'response_code' => '00',
                'response_message' => 'sales order product updated',
                'data' => $sales_order_detail,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'response_code' => '01',
                'response_message' => 'Failed update sales order detail',
                'data' => [
                    "line" => $th->getLine(),
                    "message" => $th->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $sales_order_detail = $this->sales_order_detail->query()
                ->with([
                    "sales_order" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice" => function ($QQQ) {
                                return $QQQ
                                    ->with([
                                        "payment",
                                        "invoiceProforma",
                                        "dispatchOrder" => function ($QQQ) {
                                            return $QQQ
                                                ->orderBy("date_delivery")
                                                ->with([
                                                    "deliveryOrder" => function ($QQQ) {
                                                        return $QQQ->where("status", "send");
                                                    },
                                                ]);
                                        },
                                    ]);
                            },
                        ]);
                    },
                ])
                ->findOrFail($id);

            // if ($sales_order_detail->sales_order->invoice) {

            /**
             * product can not be deleted if there
             * invoice issued
             */
            // if ($sales_order_detail->sales_order->invoice->invoiceProforma) {
            //     return $this->response("01", "failed to delete sales order detail", "invoice for this order has been issued at " . $sales_order_detail->sales_order->invoice->invoiceProforma->created_at);
            // }

            /**
             * if delivery order issued, product
             * also can not be deleted
             */
            // else if ($sales_order_detail->sales_order->invoice->dispatchOrder) {

            //     /* check delivery order */
            //     $sales_order_detail_with_delivery_order = collect($sales_order_detail->sales_order->invoice->dispatchOrder)
            //         ->map(function ($dispatchOrder, $key) {
            //             if ($dispatchOrder->deliveryOrder) {
            //                 return $dispatchOrder;
            //             }
            //         })
            //         ->filter(function ($dispatchOrder, $key) {
            //             return $dispatchOrder != null;
            //         })
            //         ->sortBy(function ($dispatchOrder, $key) {
            //             return $dispatchOrder->deliveryOrder->date_delivery;
            //         })
            //         ->first();

            //     if ($sales_order_detail_with_delivery_order) {
            //         return $this->response("01", "failed to delete sales order detail", "delivery order has been issued for this order at " . $sales_order_detail_with_delivery_order->created_at, 500);
            //     }
            // }

            /**
             * if proforma was greater than 2 days
             * product cannot be deleted
             */
            // if ($sales_order_detail->sales_order->invoice->date_delivery < now()) {
            //     return $this->response("01", "failed to delete sales order detail", "proforma for this order was published more than 2 days ago so you can update quantity of this order, proforma published at: " . $sales_order_detail->sales_order->invoice->created_at, 500);
            // }
            // }

            $sales_order_detail->delete();

            $delete_event = DeletedProductEvent::dispatch($sales_order_detail);
            return $this->response("00", "sales order detail deleted", $sales_order_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to delete sales order detail", [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]);
        }
    }

    /**
     * check product package is active and packaging
     *
     * @param [type] $product_id
     * @return void
     */
    public function package_check($product_id)
    {
        $quantity_per_package = 1;
        $packaging = null;
        $product_type = null;
        $package_id = null;
        $package_weight = null;

        $product = $this->product->where("id", $product_id)->withTrashed()->first();
        $packages = $this->package->query()
            ->where('product_id', $product_id)
            ->get();

        if (!$packages) {
            $packaging = $product->unit;
        } else {
            foreach ($packages as $package) {
                if ($package->isActive == 1) {
                    $quantity_per_package = $package->quantity_per_package;
                    $packaging = $package->packaging;
                    $package_id = $package->id;
                    $package_weight = $package->weight;
                    break;
                } else {
                    $packaging = $package->unit;
                }
            }
        }

        $data = (object) [
            'product_type' => $product->type,
            'package_id' => $package_id,
            'packaging' => $packaging,
            'quantity_per_package' => $quantity_per_package,
            'package_weight' => $package_weight,
        ];

        return $data;
    }

    /**
     * check product package is active and packaging
     *
     * @param [type] $product_id
     * @return void
     */
    public function packageCheckById($product_id, $id)
    {
        $quantity_per_package = 1;
        $packaging = null;
        $product_type = null;
        $package_id = null;
        $package_weight = null;

        $product = $this->product->findOrFail($product_id);
        $packages = $this->package->findOrFail($id);
        if ($packages->product_id !== $product_id) {
            return (object) [
                'product_type' => $product->type,
                'package_id' => null,
                'packaging' => null,
                'quantity_per_package' => $quantity_per_package,
                'package_weight' => null,
                "status" => "01",
            ];
        }

        if (!$packages) {
            $packaging = $product->unit;
        } else {
            $quantity_per_package = $packages->quantity_per_package;
            $packaging = $packages->packaging;
            $package_id = $packages->id;
            $package_weight = $packages->weight;
        }

        $data = (object) [
            'product_type' => $product->type,
            'package_id' => $package_id,
            'packaging' => $packaging,
            'quantity_per_package' => $quantity_per_package,
            'package_weight' => $package_weight,
            "status" => "00",
        ];

        return $data;
    }

    /**
     * product list in sales order
     *
     * @return void
     */
    public function product_list(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "store_id" => [
                "required",
                "string",
                "max:36",
            ],
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        $active_contract = $this->distributorActiveContract($request->store_id);

        if (!$active_contract) {
            return $this->response("00", "distributor out of contract", []);
        }

        try {
            $product_list = $this->product->query()
                ->with([
                    "package",
                ])
                ->when($request->has("category_name"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas("category", function ($QQQ) use ($request) {
                        return $QQQ->whereIn("name", $request->category_name);
                    });
                })
                ->where(function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("salesOrderDetail", function ($QQQ) use ($request) {
                            return $QQQ
                                ->whereHas("sales_order", function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->where(function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->where("store_id", $request->store_id)
                                                ->where("status", "confirmed");
                                        })
                                        ->orWhere(function ($QQQ) use ($request) {
                                            return $QQQ
                                                ->where("distributor_id", $request->store_id)
                                                ->whereIn("status", ["confirmed", "pending"]);
                                        });
                                });
                        })
                        ->orWhereHas("lastAdjustmentStock", function ($QQQ) use ($request) {
                            return $QQQ->where("dealer_id", $request->store_id);
                        });
                })
                ->productMarketing()
                ->orderBy("name", "asc")
                ->get();

            return $this->response("00", "product list", $product_list);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get product list", $th->getMessage());
        }
    }

    /**
     * check product quantity last order
     *
     * @param Request $request
     * @return void
     */
    public function product_price(Request $request)
    {
        $package_check = $this->package_check($request->product_id);
        $price = null;
        $quantity = $request->quantity;

        $prices = $this->price->query()
            ->where('product_id', $request->product_id)
            ->get();

        foreach ($prices as $price) {
            if ($quantity >= $price->minimum_order) {
                $price = $price->price;
            }
        }

        $data = (object) [
            'price' => $price,
            'quantity' => $quantity,
            'total' => $quantity * $price,
            'package' => $package_check,
        ];

        return response()->json([
            'response_code' => '00',
            'response_message' => 'unit_price',
            'data' => $data,
        ]);
    }

    /**
     * check store last order
     *
     * @param [type] $store_id
     * @return void
     */
    public function last_order($store_id)
    {
        $last_order = $this->sales_order->query()
            ->with('sales_order_detail')
            ->where('store_id', $store_id)
            ->orderBy('created_at')
            ->first();

        return $last_order;
    }

    /**
     * check store by sales order
     *
     * @param Request $request
     * @return renderable
     */
    public function getSalesOrderDetailByDealer(Request $request)
    {
        try {
            $data = SOD::query()
                ->where('stock', '>', 0)
                ->with('product')
                ->whereHas('salesOrder', function ($q) use ($request) {
                    $q
                        ->where(function ($QQQ) use ($request) {
                            return $QQQ
                                ->where('store_id', '=', $request->store_id)
                                ->where('status', 'confirmed');
                        })
                        ->orWhere(function ($QQQ) use ($request) {
                            return $QQQ
                                ->where('distributor_id', '=', $request->store_id)
                                ->where('status', 'confirmed');
                        });
                })
                ->select([DB::raw("SUM(stock) as total_stock"), "sales_order_details.*"])
                ->withAggregate("product", "name")
                ->orderBy("product_name", "asc")
                ->groupBy('product_id')
                ->productMarketing()
                ->get();

            return $this->response("00", "success to get sales order detail product", compact('data'));
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get sales order detail product", $th->getMessage());
        }

    }

    public function agencyLevelCheck($request, $qty)
    {
        $agency_level = DB::table('prices')
            ->where("product_id", $request->product_id)
            ->where(function ($QQQ) use ($qty) {
                return $QQQ
                    ->where("minimum_order", ">=", $qty)
                    ->orWhere("minimum_order", "<=", $qty);
            })
            ->get();
        $length = count($agency_level);
        $product_Price = null;

        foreach ($agency_level as $key => $price) {
            if ($key == $length - 1) {
                if (!$product_Price) {
                    $product_Price = $agency_level[$key];
                    break;
                }
                break;
            }

            if ($qty <= $agency_level[$key]->minimum_order && $qty > $agency_level[$key + 1]->minimum_order) {
                $product_Price = $agency_level[$key + 1];
                break;
            }
        }

        $agency_level_id = null;
        if ($product_Price) {
            $agency_level_id = $product_Price->agency_level_id;
        }
        return $agency_level_id;
    }

}

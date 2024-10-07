<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Exports\ProductExport;
use App\Traits\DistributorStock;
use App\Traits\Enums;
use App\Traits\RequestMerger;
use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Http\Requests\ProductRequest;
use Modules\Distributor\Entities\DistributorArea;
use Modules\Distributor\Entities\DistributorProduct;
use Modules\Event\Entities\Event;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrderV2\Entities\SalesOrderDetailV2;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class ProductController extends Controller
{
    use Enums;
    use RequestMerger;
    use ResponseHandler;
    use DistributorStock;

    public function __construct(Product $product, SalesOrderDetail $sales_order_detail, SalesOrder $sales_order)
    {
        $this->product = $product;
        $this->sales_order_detail = $sales_order_detail;
        $this->sales_order = $sales_order;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "dealer_id" => "required_with:scope_unset_first_stock_distributor_product",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        $with = [
            'package',
            'allPackage',
            'price',
            'priceD1',
            'price.agency_level',
            'priceCheapToExpensive',
            'lowerPrice',
            'productGroup',
        ];

        if ($request->has("include_event_sales_estimation")) {
            $with = collect($with)->merge([
                'eventSalesEstimation' => function ($QQQ) use ($request) {
                    return $QQQ->where("event_id", $request->include_event_sales_estimation);
                },
            ])
                ->toArray();
        }

        try {
            $products = $this->product->query()
                ->with($with)
                ->where('name', 'like', '%' . $request->name . '%')

            /* user marketing category a and b only*/
                ->productMarketing()

            /* filter by category name */
                ->when($request->category_name, function ($QQQ) use ($request) {
                    if (!in_array("special", $request->category_name)) {
                        return $QQQ->whereIn("category", $this->category($request));
                    } else {
                        return $QQQ->whereIn("category", $this->category($request));
                    }
                })

                ->when($request->category_id, function ($QQQ) use ($request) {
                    return $QQQ->where("category", $request->category_id);
                })

                ->when($request->metric_unit, function ($QQQ) use ($request) {
                    return $QQQ->where("metric_unit", $request->metric_unit);
                })

            /* filter by dealer id */
                ->when($request->dealer_id && !$request->scope_unset_first_stock_distributor_product, function ($QQQ) use ($request) {
                    $distributorProduct = $this->distributorProduct($request);
                    return $QQQ->whereNotIn("id", $distributorProduct);
                })

                ->when($request->dealer_id, function ($QQQ) use ($request) {
                    $dealer = Dealer::find($request->dealer_id);
                    if ($dealer) {
                        $agencyLevel = $dealer->agencyLevel->id;
                        $QQQ->with('priceByDealer', function ($q) use ($agencyLevel) {
                            return $q->where('agency_level_id', $agencyLevel)->whereDate('valid_from', '<=', date('Y-m-d'))->orderByDesc('valid_from');
                        });
                    }
                })

            /* filter by price */
                ->when($request->has("product_price"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas("price", function ($QQQ) use ($request) {
                        return $QQQ->where("price", ">=", $request->product_price);
                    });
                })

            /* filter by price */
                ->when($request->product_have_price, function ($QQQ) use ($request) {
                    return $QQQ->whereHas("price");
                })

            /* filter active product */
                ->when($request->has("is_active"), function ($QQQ) use ($request) {
                    return $QQQ->where("is_active", $request->is_active);
                })

            /* first stock distributyor product */
                ->when($request->scope_unset_first_stock_distributor_product, function ($QQQ) use ($request) {
                    return $QQQ->unsetFirstStockDistributorProduct($request->dealer_id);
                })

            /* event pruduct bundle */
                ->when($request->has("include_event_sales_estimation"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas('eventSalesEstimation', function ($QQQ) use ($request) {
                        return $QQQ->where("event_id", $request->include_event_sales_estimation);
                    });
                })

                ->when($request->exclude_product_inside_budle, function ($QQQ) use ($request) {
                    return $QQQ->whereHas('eventProductBundle', function ($QQQ) use ($request) {
                        return $QQQ->where("bundle_id", $request->exclude_product_inside_budle);
                    });
                })

            /* exclude product was set inside bundle */
                ->when($request->has("sort_by"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction;
                    }
                    if ($request->sort_by == 'product_name') {
                        return $QQQ->orderBy("name", $sort_type);
                    } elseif ($request->sort_by == 'category_name') {
                        return $QQQ->withAggregate("category", "name")
                            ->orderByRaw("category_name {$sort_type}");
                    } elseif ($request->sort_by == 'unit') {
                        return $QQQ->orderBy("unit", $sort_type);
                    } elseif ($request->sort_by == 'type') {
                        return $QQQ->orderBy("type", $sort_type);
                    } else {
                        return $QQQ->orderBy("name", "asc");
                    }
                });

            if ($request->has("limit")) {
                $products = $products->paginate($request->limit > 0 ? $request->limit : 15);
            } elseif ($request->with_pagination) {
                $products = $products->paginate($request->limit > 0 ? $request->limit : 10);
            } else {
                $products = $products->get();
            }

            if ($request->distributor_stock_check && $request->has("dealer_id")) {
                foreach ($products as $product) {
                    $previous_contract_stock = $this->distributorProductStockPreviousContract($request->dealer_id, $product->id);
                    $product->previous_contract_stock = $previous_contract_stock->current_stock;
                }
            }

            return $this->response('00', 'products index', $products);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display products', [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ]);
        }
    }

    public function distributorProduct($request)
    {
        $is_distributor = false;
        $product_list_buyer_as_distributor = [];
        $buyer_contract = [];
        $produk_exclude = [];
        $dealer = DealerV2::query()
            ->with([
                "distributorContract" => function ($QQQ) {
                    return $QQQ
                        ->with([
                            "product",
                        ])
                        ->whereDate("contract_start", "<=", Carbon::now())
                        ->whereDate("contract_end", ">=", Carbon::now());
                },
                "agencyLevel",
            ])
            ->whereNull("deleted_at")
            ->where("id", $request->dealer_id)
            ->first();

        if ($dealer) {
            if ($dealer->distributorContract) {
                $buyer_contract = $dealer->distributorContract->pluck("id");
            }
        }

        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("parent_id", $request->dealer_id)
            ->where("type", "dealer")
            ->first();

        $dealer_district = null;
        if ($dealer_address) {
            if ($dealer_address->district_id) {
                $dealer_district = $dealer_address->district_id;
            }
        }

        $distributor_area = DistributorArea::query()
            ->with([
                "contract",
            ])
            ->whereHas("contract", function ($QQQ) {
                return $QQQ
                    ->whereDate("contract_start", "<=", Carbon::now())
                    ->whereDate("contract_end", ">=", Carbon::now());
            })
            ->where("district_id", $dealer_district)
            ->get()
            ->pluck("contract.id");

        /* if in the dealer district there is no distributor,
         * show all product without excluding
         */
        if (count($distributor_area) == 0) {
            $produk_exclude = [];
        }

        /* if there are distributor,  and dealer as buyer is not distributor
         * product distributor will be not displayed
         */
        $distributor_product = DistributorProduct::query()
            ->with([
                "product",
                "contract" => function ($QQQ) {
                    return $QQQ->with("distributorLevel");
                },
            ])
            ->whereIn("contract_id", $distributor_area)
            ->whereNotIn("contract_id", $buyer_contract)
            ->get();

        $contract_list = $distributor_product
            ->pluck("contract.id")
            ->toArray();

        $product_list_distributor = $distributor_product
            ->pluck("product_id")
            ->toArray();

        $produk_exclude = $product_list_distributor;

        /* if there are distributor,
         * and dealer as buyer is distributor
         */
        $different_product = null;
        $same_product = null;

        if ($dealer) {
            if ($dealer->distributorContract) {
                $is_distributor = true;

                /* own product distributor (dealer as buyer) */
                $product_list_buyer_as_distributor = $dealer->distributorContract
                    ->pluck("product")
                    ->flatten()
                    ->pluck("product_id")
                    ->flatten()
                    ->toArray();

                /* compare product distributor and buyer
                 * check different product on
                 * distributor
                 */
                $different_product = array_diff($product_list_distributor, $product_list_buyer_as_distributor);

                /* compare product distributor and buyer
                 * check different product on
                 * distributor
                 */
                $same_product = array_intersect($product_list_distributor, $product_list_buyer_as_distributor);

                /* if there product distributor same with product buyer
                 * compare distributor level
                 */
                if (count($same_product) > 0) {
                    $buyer_level = $dealer->agencyLevel->agency;
                    foreach ($distributor_product as $distributor_level_on_product) {
                        if (in_array($distributor_level_on_product->product_id, $same_product)) {
                            if (!$distributor_level_on_product->contract->distributorLevel) {
                                continue;
                            }

                            $distributor_level = $distributor_level_on_product->contract->distributorLevel->agency;
                            if ($distributor_level > $buyer_level) {
                                array_push($produk_exclude, $distributor_level_on_product->product_id);
                            } else {
                                $produk_exclude = collect($produk_exclude)
                                    ->reject(function ($product_exclude, $index) use ($distributor_level_on_product) {
                                        return $product_exclude == $distributor_level_on_product->product_id;
                                    })
                                    ->toArray();
                            }
                        }
                    }
                }

                /* push product distributor which is diiferent
                 * with product buyer as distributor
                 * to exclude list
                 */
                foreach ($different_product as $diff) {
                    array_push($produk_exclude, $diff);
                }
            }
        }

        return collect($produk_exclude)->unique()->toArray();
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('dataacuan::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(ProductRequest $request)
    {
        try {
            $weight = $request->weight / 1000;
            $product = $this->product->firstOrCreate([
                "name" => $request->name,
                "size" => $request->size,
                "unit" => $request->unit,
                "type" => $request->type,
                "category" => $request->category_id,
                "category_id" => $request->category_id,
                "weight" => $weight,
            ], [
                "image" => $request->image,
                "stock" => $request->stock,
                "metric_unit" => $request->metric_unit,
                "volume" => $request->volume,
                "is_active" => $request->is_active,
            ]);

            $product->weight = $product->weight * 1000;
            return $this->response('00', 'product saved', $product);
        } catch (\Exception $th) {
            return $this->response('01', 'failed to save product', [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ]);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $with = [
                'package',
                'price',
                'price.agency_level',
                'allPackage',
                'priceCheapToExpensive',
                'priceD1',
                'pointProduct',
            ];

            if ($request->has("include_event_sales_estimation")) {
                $with = collect($with)
                    ->merge([
                        'eventSalesEstimation' => function ($QQQ) use ($request) {
                            return $QQQ->where("event_id", $request->include_event_sales_estimation);
                        },
                    ])
                    ->toArray();
            }

            $product = $this->product->query()
                ->with($with)
                ->where('id', $id)
                ->first();
            return $this->response('00', 'Products detail', $product);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display products', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('dataacuan::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $product = $this->product->findOrFail($id);
        $product = $product->fill($request->all());
        $product->save();

        try {
            if ($request->has("weight")) {
                $request->merge([
                    "weight" => $request->weight / 1000,
                ]);
            }

            $request->merge([
                "category" => $request->category_id,
            ]);
            $product = $product->fill($request->all());
            $product->save();
            $product->weight = $product->weight * 1000;

            return $this->response('00', 'product updated', $product);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display products', [
                "message" => $th->getMessage(),
            ], 500);
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
            $product = $this->product->findOrFail($id);
            $product->delete();
            $this->salesOrder($id);

            return $this->response('00', 'product delete', $product);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to delete product', $th->getMessage());
        }
    }

    public function salesOrder($id)
    {
        $status = "confirmed";
        $sales_orders = $this->sales_order->with("sales_order_detail")->where("status", "!=", $status)->get();
        foreach ($sales_orders as $sales_order) {
            foreach ($sales_order->sales_order_detail as $sales_order_detail) {
                if ($sales_order_detail->product_id == $id) {
                    $sales_order_detail->forceDelete();
                }
            }
        }
    }

    public function updateHet(Request $request, $id)
    {
        try {
            $product = $this->product->where('id', $id)
                ->update([
                    "het" => $request->het,
                ]);
            $product = $this->product->findOrFail($id);
            return $this->response('00', 'product het updated ', $product);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update product het', $th->getMessage());
        }
    }

    public function category($request)
    {
        $categories = $request->category;
        $category = DB::table('product_categories')->whereNull("deleted_at")->get();
        $category_id_list = collect($category)->pluck("id");

        if (!$request->category) {
            $categories = $category_id_list;
        }

        if ($request->category_name) {
            $categories = collect($category)->whereIn("name", $request->category_name)->pluck("id")->toArray();
        }
        return $categories;
    }

    public function export()
    {
        try {
            $data = (new ProductExport)->store('products.xlsx', 's3');
            return $this->response("00", "Export Success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "Export Failed", $th->getMessage());
        }
    }

    public function getProductByDealer(Request $request)
    {
        try {
            $data = $this->product->query()
                ->with(['productByDealer' => function ($q) use ($request) {
                    return $q->select('sales_order_details.product_id', 'sales_orders.id', DB::raw("SUM(sales_order_details.stock) as total_stock"))
                        ->where('sales_orders.store_id', $request->store_id)
                        ->where('sales_orders.status', 'confirmed')->where('sales_order_details.stock', '>', 0)
                        ->whereNull('sales_order_details.deleted_at')
                        ->whereNull('sales_orders.deleted_at')
                        ->whereNull('sales_orders.distributor_id')
                        ->groupBy('sales_order_details.product_id');
                }])

            /* user marketing, category a and b only*/
                ->productMarketing()
                ->where('name', 'like', "%" . $request->name . "%")

                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    // dd($QQQ->first());
                    $direction = $request->order_type ?? "asc";
                    if ($request->sorting_column == 'active_stock') {
                        return $QQQ->withAggregate(['productByDealer as total_stock' => function ($query) use ($request) {
                            return $query->select(DB::raw("SUM(sales_order_details.stock) as total_stock"))
                                ->where('sales_orders.store_id', $request->store_id)
                                ->where('sales_orders.status', 'confirmed')->where('sales_order_details.stock', '>', 0)
                                ->whereNull('sales_order_details.deleted_at')
                                ->whereNull('sales_orders.deleted_at')
                                ->whereNull('sales_orders.distributor_id')
                            ;
                        }], 0)
                            ->orderBy("total_stock", $direction);

                        // return $QQQ->orderBy(
                        //     function ($query) use ($request) {
                        //         $query->selectRaw('SUM(sod.stock) as total_stock')
                        //         ->from('sales_order_details as sod')
                        //             ->where('s.store_id', $request->store_id)
                        //             ->where('s.status', 'confirmed')
                        //             ->where('sod.stock', '>', 0)
                        //             ->join('sales_orders as s', 's.id', '=', 'sod.sales_order_id')
                        //             ->whereColumn('sod.product_id', 'products.id')
                        //             ->groupBy('sod.product_id');

                        //     },
                        //     $direction
                        // );
                    } elseif ($request->sorting_column == 'product_name') {
                        return $QQQ->orderBy("name", $direction);
                    }
                })
                ->paginate($request->has('limit') ? $request->limit : 15);
            return $this->response("00", "get Data success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "get Data Failed ", $th->getMessage());
        }
    }
    public function getProductSalesByDealer(Request $request)
    {
        try {
            $data = SalesOrderDetailV2::query()
                ->where('product_id', $request->product_id)
                ->whereHas('sales_order_list', function ($query) use ($request) {
                    $query
                        ->where(function ($query) use ($request) {
                            $query
                                ->where('store_id', '=', $request->store_id)
                                ->orWhere('distributor_id', $request->store_id);
                        })
                        ->consideredOrder()
                        ->whereNull('deleted_at');
                })
                ->with(['sales_order_list', 'sales_order_list.invoice'])
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $direction = $request->order_type ?? "asc";
                    if ($request->sorting_column == 'no_nota') {
                        return $QQQ->orderBy(
                            DB::table('sales_orders as s')
                                ->selectRaw("IF(s.type = 2, s.reference_number, i.invoice) as nota")
                                ->leftJoin("invoices as i", "i.sales_order_id", "s.id")
                                ->whereColumn("sales_order_details.sales_order_id", "s.id")
                                ->limit("1"), $direction
                        );
                    } elseif ($request->sorting_column == 'type') {
                        return $QQQ
                            ->join("sales_orders as so", "so.id", "sales_order_details.sales_order_id")
                            ->select("so.type", "sales_order_details.*")
                            ->orderByRaw("so.type $direction");
                    } elseif ($request->sorting_column == 'confirmation_date') {
                        return $QQQ->orderBy(
                            DB::table('sales_orders as s')
                                ->selectRaw("IF(s.type = 2, s.date, i.created_at) as nota")
                                ->leftJoin("invoices as i", "i.sales_order_id", "s.id")
                                ->whereColumn("sales_order_details.sales_order_id", "s.id")
                                ->limit("1"), $direction
                        );
                    } else {
                        return $QQQ->orderBy($request->sorting_column, $direction);
                    }
                });
            if ($request->has("disabled_pagination")) {
                $data = $data->get();
            } else {
                $data = $data->paginate($request->limit ? $request->limit : 15);
            }
            return $this->response("00", "get Data success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "get Data Failed ", $th->getMessage());
        }
    }

    public function productAgencyLevelD1(Request $request, $id)
    {
        try {
            $product = $this->product->query()
                ->with([
                    "priceHasOne",
                    "lowerPrice",
                ])
                ->findOrFail($id);

            $price = $product->priceHasOne ? $product->priceHasOne->price : $product->priceHasOneV2->price;
            $priceFinal = $price ?: $product->lowerPrice;

            $data = [
                "id" => null,
                "name" => null,
            ];
            $data["id"] = $product->id;
            $data["name"] = $product->name;
            $data["price"] = $priceFinal ?: 0.00;
            return $this->response("00", "get Data success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "get Data Failed ", $th->getMessage());
        }
    }

    public function productAgencyLevelD1Direct(Request $request, $id)
    {
        try {
            $product = $this->product->query()
                ->select("id", "name")
                ->with([
                    "priceHasOneV2",
                ])
                ->with([
                    "lowerPrice",
                ])
                ->findOrFail($id);

            $validate = Validator::make($request->all(), [
                "event_id" => [
                    "required",
                ],
            ]);

            if ($validate->fails()) {
                return $this->response("04", "invalid data send", $validate->errors(), 422);
            }

            $event = Event::select("id", "dealer_id", "sub_dealer_id")->findOrFail($request->event_id);

            $store_id = $event->dealer_id ?: $event->sub_dealer_id;

            $salesOrderDirect = SalesOrder::select("id", "type", "store_id")->where("type", "1")->where("store_id", $store_id)->latest()->first();

            $salesOrderIndirectSales = SalesOrder::select("id", "type", "store_id")
                ->where("type", "2")
                ->whereHas("salesOrderOrigin")
                ->with("salesOrderOrigin")
                ->where("store_id", $store_id)->orderByDesc("date")->first();

            $salesOrderDetailId = $salesOrderDirect ? $salesOrderDirect->id : ($salesOrderIndirectSales ? $salesOrderIndirectSales->salesOrderOrigin->direct_id : null);

            $salesOrderOrderDetail = SalesOrderDetail::select("id", "sales_order_id", "product_id", "unit_price")->where("sales_order_id", $salesOrderDetailId)->where("product_id", $id)->first();

            $priceUnit = $salesOrderOrderDetail ? $salesOrderOrderDetail->unit_price : $product->priceHasOneV2->price;

            $priceFinal = $priceUnit ?: $product->lowerPrice->price;

            $data = [
                "id" => null,
                "name" => null,
            ];
            $data["id"] = $product->id;
            $data["name"] = $product->name;
            $data["price"] = $priceFinal ?: 0.00;
            return $this->response("00", "get Data success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "get Data Failed ", $th->getMessage());
        }
    }

    public function productPackageOnly(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                "product_in" => [
                    "array",
                ],
                "products_not_in" => [
                    "array",
                ],
            ]);

            if ($validate->fails()) {
                return $this->response("04", "invalid data send", $validate->errors(), 422);
            }
            $product = $this->product->query()
                ->with("category", "package")

            /* user marketing category a and b only*/
                ->productMarketing()
                ->when($request->products_in, function ($query) use ($request) {
                    return $query
                        ->whereIn("id", $request->products_in);
                })
                ->when($request->products_not_in, function ($query) use ($request) {
                    return $query->whereNotIn("id", $request->products_not_in);
                })
                ->when($request->product_name, function ($query) use ($request) {
                    return $query->where("name", "like", $request->product_name);
                })
                ->orderBy("name", "asc")
                ->get();

            return $this->response("00", "get Data success", $product);
        } catch (\Throwable $th) {
            return $this->response("01", "get Data Failed ", $th->getMessage());
        }
    }
}

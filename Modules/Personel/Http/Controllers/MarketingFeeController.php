<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Transformers\MarketingFeeCollectionResource;
use Modules\Personel\Transformers\MarketingFeeResource;
use Modules\SalesOrder\Entities\SalesOrder;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class MarketingFeeController extends Controller
{
    use DisableAuthorization, ResponseHandler;

    protected $model = MarketingFee::class;
    protected $resource = MarketingFeeResource::class;
    protected $collectionResource = MarketingFeeCollectionResource::class;

    public function includes(): array
    {
        return [
            "personel",
            "personel.position",
            "marketing",
            "area",
        ];
    }
    public function exposedScopes(): array
    {
        return [
            "byArea",
        ];
    }

    public function filterableBy(): array
    {
        return [
            "personel_id",
            "personel.name",
            "fee_reguler_total",
            "fee_reguler_settle",
            "fee_target_total",
            "fee_target_settle",
            "year",
            "quarter",
            "created_at",
            "updated_at",
        ];
    }

    public function seachableBy(): array
    {
        return [
            "personel_id",
            "fee_reguler_total",
            "fee_reguler_settle",
            "fee_target_total",
            "fee_target_settle",
            "year",
            "quarter",
            "created_at",
            "updated_at",
        ];
    }

    public function sortableBy(): array
    {
        return [
            "personel_id",
            "personel.name",
            "fee_reguler_total",
            "fee_reguler_settle",
            "fee_target_total",
            "fee_target_settle",
            "year",
            "quarter",
            "created_at",
            "updated_at",
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
    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->disabled_pagination) {
            return $query;
        } else {
            return $query->paginate($paginationLimit);
        }
    }

    public function marketingFeeGrouped(Request $request)
    {
        $year = Carbon::now()->format("Y");
        $quartal = Carbon::now()->quarter;
        if ($request->has("year")) {
            $year = $request->year;
        }
        if ($request->has("quartal")) {
            $quartal = $request->quartal;
        }

        try {

            $marketing_fee = MarketingFee::query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ
                            ->orderBy("name")
                            ->with([
                                "position",
                            ]);
                    },
                    "payment"
                ])
                ->where(function ($QQQ) use ($year, $request) {
                    return $QQQ
                        ->whereHas("personel", function ($QQQ) use ($year, $request) {
                            return $QQQ
                                ->whereHas("position", function ($query) use ($year, $request) {
                                    if ($request->type_data) {
                                        if (in_array("2", $request->type_data) && (count($request->type_data) == 1)) {
                                            return $query->whereIn("name", ["Sales Counter (SC)"]);
                                        } elseif (in_array("1", $request->type_data)  && (count($request->type_data) == 1)) {
                                            return $query->whereIn("name", marketing_positions());
                                        } else {
                                            return $query->whereIn("name", array_merge(marketing_positions(), ["Sales Counter (SC)"]));
                                        }
                                    }else{
                                        return $query->whereIn("name", array_merge(marketing_positions(), ["Sales Counter (SC)"]));
                                    }
                                })
                                ->withTrashed();
                        });
                    // ->orWhere(function ($QQQ) use ($year) {
                    //     return $QQQ
                    //         ->where("year", $year)
                    //         ->where("fee_reguler_settle", ">", 0);
                    // });
                })
                ->when($request->status, function ($query) use ($request) {
                    return $query
                        ->whereHas("personel", function ($query) use ($request) {
                            // return $query->whereIn("status", collect($request->status)->reject(fn ($status) => $status == "3")->toArray());
                            return $query->whereIn("status", $request->status);
                            
                        });
                        // ->when(in_array("3", $request->status), function ($QQQ) use ($request) {
                        //     return $QQQ
                        //         ->orWhere(function ($QQQ) {
                        //             return $QQQ
                        //                 ->whereHas("personel", function ($QQQ) {
                        //                     return $QQQ->where("status", "3");
                        //                 })
                        //                 ->where(function ($QQQ) {
                        //                     return $QQQ
                        //                         ->where("fee_reguler_total", ">", 0)
                        //                         ->orWhere("fee_target_total", ">", 0);
                        //                 });
                        //         });
                        // });
                })

                /* default marketing status */
                ->when(!$request->status, function ($query) use ($request) {
                    return $query->whereHas("personel", function ($query) use ($request) {
                        return $query->whereIn("status", [1, 2]);
                    });
                })

                /* filter by are, region or sub region */
                ->when($request->area_id, function ($QQQ) use ($request) {
                    return $QQQ->byArea($request->area_id);
                })

                /* filter by personel name */
                ->when($request->has("marketing_name"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas("personel", function ($QQQ) use ($request) {
                        return $QQQ->where("name", "like", "%" . $request->marketing_name . "%");
                    });
                })

                /* filter year */
                ->where("year", $year)
                ->leftJoin("personels", "personels.id", "=", "marketing_fee.personel_id")
                ->select("marketing_fee.*")
                ->when($request->has("sort_by"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction;
                    }
                    if ($request->sort_by == 'personel_name') {
                        return $QQQ->orderBy("name", $sort_type);
                    } else {
                        return $QQQ
                            ->orderBy("personels.name")
                            ->orderBy("personel_id")
                            ->orderBy("year")
                            ->orderBy("quarter");
                    }
                });

            if ($request->disabled_pagination) {
                $marketing_fee = $marketing_fee->get();
            } else {
                $total_data = ceil($marketing_fee->count() / 4);
                if ($request->disabled_paginationv2) {
                    $marketing_fee = $marketing_fee
                        ->get();
                } else {
                    $marketing_fee = $marketing_fee
                        ->simplePaginate(20);
                }

                $marketing_fee_grouped = $marketing_fee->groupBy([
                    function ($val) {
                        return $val->personel_id;
                    },
                    function ($val) {
                        return $val->quarter;
                    },
                ])
                    ->each(function ($fee, $personel_id) use ($year) {
                        if (collect($fee)->count() < 4) {
                            for ($i = 1; $i < 5; $i++) {
                                MarketingFee::firstOrCreate([
                                    "personel_id" => $personel_id,
                                    "year" => $year,
                                    "quarter" => $i,
                                ], [
                                    "fee_reguler_total" => 0,
                                    "fee_reguler_settle" => 0,
                                    "fee_target_total" => 0,
                                    "fee_target_settle" => 0,
                                ]);
                            }
                        }
                    });

                $marketing_fee_grouped = $marketing_fee_grouped->map(function ($marketing, $key) use ($request) {
                    $marketing_sorted = collect($marketing)->sortKeys();
                    $marketing_detail = null;
                    $marketing_region = null;
                    $marketing_sub_region = null;

                    $quartal_list = [1, 2, 3, 4];
                    $quartal_available = collect($marketing)->keys();
                    $marketing_remap = $marketing_sorted->map(function ($marketing_quartal, $quartal) use (&$marketing_detail, &$marketing_region, &$marketing_sub_region, &$detail_per_quartal, $quartal_list, $quartal_available, &$marketing_remap) {
                        $marketing_detail = $marketing_quartal[0]->personel;
                        $marketing_region = $marketing_quartal[0]->region;
                        $marketing_sub_region = $marketing_quartal[0]->subRegion;
                        $fee_active_total = $marketing_quartal[0]->fee_reguler_settle + $marketing_quartal[0]->fee_target_settle;
                        $fee_total = $marketing_quartal[0]->fee_reguler_total + $marketing_quartal[0]->fee_target_total;
                        $fee_paid = $marketing_quartal[0]->payment->sum("amount");

                        if ($marketing_quartal[0]->payment->sum("amount") == 0) {
                            $status = "unpaid";
                        } elseif (($marketing_quartal[0]->payment->sum("amount") > 0) && ($fee_paid < $fee_active_total)) {
                            $status = "paid";
                        } else {
                            $status = "settle";
                        }

                        $detail_quartal = [
                            "fee_reguler_total" => $marketing_quartal[0]->fee_reguler_total,
                            "fee_reguler_settle_pending" => $marketing_quartal[0]->fee_reguler_settle_pending,
                            "fee_reguler_settle" => $marketing_quartal[0]->fee_reguler_settle,
                            "fee_target_total" => $marketing_quartal[0]->fee_target_total,
                            "fee_target_settle_pending" => $marketing_quartal[0]->fee_target_settle_pending,
                            "fee_target_settle" => $marketing_quartal[0]->fee_target_settle,
                            "fee_active_total" => $marketing_quartal[0]->fee_reguler_settle + $marketing_quartal[0]->fee_target_settle,
                            "payment_amount" => $marketing_quartal[0]->payment->sum("amount"),
                            "not_paid_yet" => $fee_active_total-$fee_paid,
                            "status" => $status
                        ];

                        return $detail_quartal;
                    })->filter(function ($value, $key) use ($request) {
                        // dd($value["status"]);
                        if ($request->payment_status == "unpaid") {
                            return $value["status"] == "unpaid";
                        }
                        return $value;
                    });

                    $lost_quarter = array_diff([1, 2, 3, 4], array_keys($marketing_remap->toArray()));


                    if (!$request->payment_status == "unpaid") {
                        // dd("cek");
                        if (count($lost_quarter)) {
                            foreach ($lost_quarter as $quartal) {
                                $marketing_remap[$quartal] = [
                                    "fee_reguler_total" => "0.00",
                                    "fee_reguler_settle_pending" => "0.00",
                                    "fee_reguler_settle" => "0.00",
                                    "fee_target_total" => "0.00",
                                    "fee_target_settle_pending" => "0.00",
                                    "fee_target_settle" => "0.00",
                                    "fee_active_total" => "0.00",
                                    "payment_amount" => 0,
                                    "not_paid_yet" => 0,
                                    "status" => "unpaid"
                                ];
                            }
                        }
                    }

                    $marketing = [
                        "marketing" => $marketing_detail,
                        "region" => $marketing_region,
                        "sub_region" => $marketing_sub_region,
                        "data" => $marketing_remap->sortKeys(),
                    ];
                    return $marketing;
                });
            }

            if ($request->disabled_paginationv2) {
                $marketing_fee_grouped = $marketing_fee_grouped->values();
            } else {
                $marketing_fee_grouped = $this->paginator($request, $marketing_fee_grouped, $total_data);
            }

            if ($request->sort_by == 'buyer') {
                if ($request->direction == "desc") {
                    // dd("sas");
                    $sortedResult = $marketing_fee_grouped->getCollection()->sortByDesc(function ($item) {
                        return $item->dispatchOrder?->invoice?->salesOrder?->dealer->name;
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $marketing_fee_grouped->getCollection()->sortBy(function ($item) {
                        return $item->dispatchOrder?->invoice?->sales_order?->dealer->name;
                    })->values();
                }

                $marketing_fee_grouped->setCollection($sortedResult);
            }

            return $this->response("00", "success", $marketing_fee_grouped);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
            ]);
        }
    }

    public function paginator($request, $data, $total_data = null)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageLimit = $request->limit > 0 ? $request->limit : 5;

        // $currentItems = $data->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();
        $currentItems = $data->values();
        $path = LengthAwarePaginator::resolveCurrentPath();

        $paginator = new LengthAwarePaginator($currentItems, $total_data, $pageLimit, $currentPage, ['path' => $path]);

        return $paginator;
    }

    public function marketingFeeDetail(Request $request, $personel_id)
    {
        $year = now()->format("Y");
        $quartal = now()->quarter;

        if ($request->has("year")) {
            $year = $request->year;
        }
        if ($request->quartal) {
            $quartal = $request->quartal;
        }

        try {

            /**
             * follow up order will get fee if follow up days
             * more than 60 days
             */
            $follow_up_days_reference = DB::table('fee_follow_ups')->whereNull("deleted_at")->orderBy("follow_up_days")->first();

            $marketing_fee_detail = SalesOrder::query()
                ->with([
                    "personel",
                    "invoice" => function ($QQQ) {
                        return $QQQ;
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                    "subDealer" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                    "feeSharingOrigin" => function ($QQQ) use ($year, $quartal, $personel_id) {
                        return $QQQ
                            ->where("personel_id", $personel_id)
                            ->whereYear("confirmed_at", $year)
                            ->whereRaw("quarter(confirmed_at) = ?", $quartal);
                    },
                    "statusFee",
                    "followUpBy" => function ($QQQ) {
                        return $QQQ->with([
                            "position",
                        ]);
                    },
                    "salesOrderDetail",
                    "firstDeliveryOrder" => function ($QQQ) {
                        return $QQQ->orderBy("date_delivery");
                    },
                    "lastReceivingGoodIndirect",
                ])
                ->where(function ($QQQ) {
                    return $QQQ
                        ->whereHas("dealer", function ($QQQ) {
                            return $QQQ->withTrashed();
                        })
                        ->orWhereHas("subDealer", function ($QQQ) {
                            return $QQQ->withTrashed();
                        });
                })
                ->whereHas("feeSharingOrigin", function ($QQQ) use ($year, $quartal, $personel_id) {
                    return $QQQ
                        ->where("personel_id", $personel_id)
                        ->whereYear("confirmed_at", $year)
                        ->whereRaw("quarter(confirmed_at) = ?", $quartal)
                        ->countedFeeAccordingOrigin()
                        ->where("is_returned", false)
                        ->where("is_checked", true);
                })

                ->confirmedOrder()
                ->when($request->has("order_number"), function ($QQQ) use ($request) {
                    return $QQQ->where("order_number", $request->order_number);
                })
                ->leftJoin("invoices as i", "i.sales_order_id", "=", "sales_orders.id")
                ->select("sales_orders.*")

                ->when($request->sort_by_date_confirmation, function ($QQQ) use ($request) {
                    $direction = $request->direction ? $request->direction : "asc";
                    return $QQQ->orderByRaw("if(sales_orders.type = 1, i.created_at, sales_orders.date) {$direction}");
                })
                ->when(!$request->sort_by_date_confirmation, function ($QQQ) {
                    return $QQQ->orderByRaw("if(sales_orders.type = 1, i.created_at, sales_orders.date) ASC");
                })
                ->groupBy("sales_orders.id")
                ->paginate($request->limit > 0 ? $request->limit : 10);

            $fee_position_as_marketing = DB::table('fee_positions')->whereNull("deleted_at")->where("fee_as_marketing", true)->first();

            foreach ($marketing_fee_detail as $order) {

                $is_handover = collect($order->feeSharingOrigin)
                    ->where("handover_status", true)
                    ->first();

                $fee_reguler_netto = collect($order->feeSharingOrigin)
                    ->where("personel_id", $personel_id)
                    ->reject(function ($origin) use ($is_handover) {
                        if ($is_handover) {
                            return $origin->fee_status == "purchaser";
                        }
                    })
                    ->sum("fee_shared");

                $fee_reguler_bruto = collect($order->feeSharingOrigin)
                    ->where("personel_id", $personel_id)
                    ->reject(function ($origin) use ($is_handover) {
                        if ($is_handover) {
                            return $origin->handover_status == true;
                        }
                    })
                    ->map(function ($origin) {
                        $origin->fee_shared = $origin->total_fee * $origin->fee_percentage / 100;
                        return $origin;
                    })
                    ->sum("fee_shared");

                $fee_reguler_bruto_reduce_by_sales_counter = collect($order->feeSharingOrigin)
                    ->where("personel_id", $request->personel_id)
                    ->reject(function ($origin) use ($is_handover) {
                        if ($is_handover) {
                            return $origin->handover_status == true;
                        }
                    })
                    ->map(function ($origin) {
                        $origin->fee_shared = $origin->total_fee * $origin->fee_percentage / 100;
                        return $origin;
                    })
                    ->map(function ($origin) {
                        if ($origin->sc_reduction_percentage > 0) {
                            $origin->fee_shared = $origin->fee_shared - ($origin->fee_shared * $origin->sc_reduction_percentage / 100);
                        }
                        return $origin;
                    })
                    ->sum("fee_shared");

                $fee_reguler_data = collect($order->feeSharingOrigin);

                $order->fee_reguler_total_product = collect($order->salesOrderDetail)->sum("marketing_fee_reguler");
                $order->fee_reguler_bruto = $fee_reguler_bruto;
                $order->fee_reguler_bruto_reduce_by_sales_counter = $fee_reguler_bruto_reduce_by_sales_counter;
                $order->fee_reguler_netto = $fee_reguler_netto;
                $order->fee_reguler_total = $fee_reguler_netto;
                $order->marketing_join_days = $order->personel->join_date ? Carbon::parse($order->personel->join_date)->diffInDays(($order->type == "2" ? $order->created_at->endOfDay() : $order->invoice->created_at->endOfDay()), false) : 0;
                $order->first_delivery_date = $order->firstDeliveryOrder?->date_delivery;
                $order->unsetRelation("feeSharingOrigin");
            }

            return $this->response("00", "success", $marketing_fee_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "message" => $th->getMessage(),
            ]);
        }
    }
}

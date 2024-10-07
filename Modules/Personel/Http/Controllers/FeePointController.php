<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use App\Traits\SupervisorCheck;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\SalesOrder;

class FeePointController extends Controller
{
    use SupervisorCheck;
    use ResponseHandler;
    use MarketingArea;
    use ChildrenList;

    public function __construct(SalesOrder $sales_order, Personel $personel, MarketingFee $marketing_fee, PointMarketing $point_marketing)
    {
        $this->sales_order = $sales_order;
        $this->personel = $personel;
        $this->marketing_fee = $marketing_fee;
        $this->point_marketing = $point_marketing;
    }

    /**
     * fee and potin marketing recap
     *
     * @param Request $request
     * @return void
     */
    public function feeAndPointRecap(Request $request)
    {
        /* get all personel on sub ergion */
        $personels_id = null;
        if ($request->has("sub_region_id")) {
            unset($request["region_id"]);
            $personels_id = $this->personelListByArea($request->sub_region_id);
        } else if ($request->has("region_id")) {
            $personels_id = $this->personelListByArea($request->region_id);
            unset($request["sub_region_id"]);
        }

        $position_list = [
            "Regional Marketing (RM)",
            "Regional Marketing Coordinator (RMC)",
            "Sales Counter (SC)",
            "Assistant MDM",
            "Marketing District Manager (MDM)",
            "Marketing Manager (MM)",
        ];

        $position_id = Position::query()
            ->whereIn("name", $position_list)
            ->get()
            ->pluck("id")
            ->toArray();
        try {

            $personel = $this->personel->query()
                ->with([
                    "areaMarketing" => function ($Q) {
                        return $Q->with([
                            "subRegionWithRegion" => function ($Q) {
                                return $Q->with([
                                    "region",
                                ]);
                            },
                        ]);
                    },
                    "salesOrder" => function ($QQQ) use ($request) {
                        return $QQQ
                            ->where("status", "confirmed")
                            ->with([
                                "invoice",
                                "sales_order_detail",
                            ])
                            ->when(!$request->year, function ($QQQ) {
                                return $QQQ->whereYear("created_at", Carbon::now());
                            })

                            /* filter by year */
                            ->when($request->has("year"), function ($QQQ) use ($request) {
                                return $QQQ->whereYear("created_at", $request->year);
                            })
                            ->select("sales_orders.*", DB::raw("QUARTER(sales_orders.created_at) as quarter"));
                    },
                    "position",
                    "currentMarketingFee",
                    "currentPointMarketing",
                ])

            /* filter sub region */
                ->when($request->has("sub_region_id"), function ($qqq) use ($personels_id) {
                    return $qqq->whereIn("id", $personels_id);
                })

            /* filter region */
                ->when($request->has("region_id"), function ($qqq) use ($personels_id) {
                    return $qqq->whereIn("id", $personels_id);
                })

            /* filter by personel id */
                ->when($request->has("personel_id"), function ($qqq) use ($request) {
                    return $qqq->where("id", $request->personel_id);
                })

            /* filter by personel name */
                ->when($request->has("name"), function ($qqq) use ($request) {
                    return $qqq->where("name", "like", "%" . $request->name . "%");
                })

                ->whereIn("position_id", $position_id)
                ->paginate($request->limit ? $request->limit : 5);

            /* fee sharing percentage base position */
            $fee_position = FeePosition::query()
                ->get();

            $recap = [];
            $detail = [
                "marketing" => [
                    "detail" => null,
                    "region" => null,
                    "sub_region" => null,
                ],
                "total_fee" => [
                    "reguler" => [
                        "Q1" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                        "Q2" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                        "Q3" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                        "Q4" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                    ],
                    "target" => [
                        "Q1" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                        "Q2" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                        "Q3" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                        "Q4" => [
                            "active" => 0,
                            "total" => 0,
                        ],
                    ],
                ],
                "point" => [
                    "active" => 0,
                    "total" => 0,
                ],
            ];

            $marketing_fee = $personel
                ->each(function ($marketing) use ($request) {
                    if (collect($marketing->currentMarketingFee)->count() < 4) {
                        for ($i = 1; $i < 5; $i++) {
                            $this->marketing_fee->firstOrCreate([
                                "personel_id" => $marketing->id,
                                "year" => now()->format("Y"),
                                "quarter" => $i,
                            ]);
                        }

                    }

                    if (!$marketing->currentPointMarketing) {
                        $this->point_marketing->firstOrCreate([
                            "personel_id" => $marketing->id,
                            "year" => $request->year ? $request->year : now()->format("Y"),
                        ], [
                            "status" => "not_redeemed",
                        ]);
                    }
                })
                ->groupBy("id")
                ->map(function ($marketing, $personel_id) use (&$detail) {
                    $detail["marketing"]["detail"] = $marketing[0];
                    $detail["marketing"]["region"] = ($marketing[0]->areaMarketing ? $marketing[0]->areaMarketing->subRegionWithRegion->region : null);
                    $detail["marketing"]["sub_region"] = ($marketing[0]->areaMarketing ? $marketing[0]->areaMarketing->subRegionWithRegion : null);

                    for ($i = 1; $i < 5; $i++) {

                        /* fee reguler */
                        $marketing_fee_per_quarter = collect($marketing[0]->currentMarketingFee)->where("quarter", $i)->first();
                        $detail["total_fee"]["reguler"]["Q" . $i]["total"] = $marketing_fee_per_quarter ? $marketing_fee_per_quarter->fee_reguler_total : 0;
                        $detail["total_fee"]["reguler"]["Q" . $i]["active"] = $marketing_fee_per_quarter ? $marketing_fee_per_quarter->fee_reguler_settle : 0;

                        /* fee target */
                        $detail["total_fee"]["target"]["Q" . $i]["total"] = $marketing_fee_per_quarter ? $marketing_fee_per_quarter->fee_target_total : 0;
                        $detail["total_fee"]["target"]["Q" . $i]["active"] = $marketing_fee_per_quarter ? $marketing_fee_per_quarter->fee_target_settle : 0;
                    }

                    $marketing_point = $marketing[0]->currentPointMarketing;
                    $detail["point"]["active"] = $marketing_point ? $marketing_point->marketing_point_redeemable : 0;
                    $detail["point"]["total"] = $marketing_point ? $marketing_point->marketing_point_total : 0;
                    return $detail;
                });

            return $this->response("00", "success, marketing fee point recap", $marketing_fee);

            // foreach ($personel as $key => $marketing) {
            //     $personel[$key]->sales_order_grouped = $marketing->salesOrder->groupBy("quarter");

            //     /* get fee for marketing position */
            //     $fee = collect($fee_position)->where("position_id", $marketing->position_id)->first();

            //     $marketing_detail = $marketing;
            //     $marketing_detail = collect($marketing_detail)->forget("sales_order");
            //     $marketing_detail = collect($marketing_detail)->forget("sales_order_grouped");
            //     $marketing_detail = collect($marketing_detail)->forget("area_marketing");

            //     $detail["marketing"]["detail"] = $marketing_detail;
            //     $detail["marketing"]["region"] = ($marketing->areaMarketing ? $marketing->areaMarketing->subRegionWithRegion->region : null);
            //     $detail["marketing"]["sub_region"] = ($marketing->areaMarketing ? $marketing->areaMarketing->subRegionWithRegion : null);
            //     $recap[$marketing->id] = $detail;

            //     foreach ($marketing->sales_order_grouped as $quarter => $order) {
            //         $total_fee_reguler = 0;
            //         $active_fee_reguler = 0;
            //         $total_fee_target = 0;
            //         $active_fee_target = 0;
            //         $total_point = 0;
            //         $active_point = 0;
            //         foreach ($order as $val) {
            //             $total_fee_reguler += collect($val->sales_order_detail)->sum("marketing_fee_reguler");
            //             $total_fee_target += collect($val->sales_order_detail)->sum("marketing_fee");
            //             $total_point += collect($val->sales_order_detail)->sum("marketing_point");
            //             if ($val->type == "2") {
            //                 $active_fee_reguler += collect($val->sales_order_detail)->sum("marketing_fee_reguler");
            //                 $active_fee_target += collect($val->sales_order_detail)->sum("marketing_fee");
            //                 $active_point += collect($val->sales_order_detail)->sum("marketing_point");
            //             } else {
            //                 if ($val->invoice) {
            //                     if ($val->invoice->payment_status == "settle") {
            //                         $active_fee_reguler += collect($val->sales_order_detail)->sum("marketing_fee_reguler");
            //                         $active_fee_target += collect($val->sales_order_detail)->sum("marketing_fee");
            //                         $active_point += collect($val->sales_order_detail)->sum("marketing_point");
            //                     }
            //                 }
            //             }
            //         }

            //         /* calculate fee percentage */
            //         if ($fee) {
            //             $total_fee_reguler = $total_fee_reguler == 0 ? $total_fee_reguler : $total_fee_reguler * $fee->fee / 100;
            //             $active_fee_reguler = $active_fee_reguler == 0 ? $active_fee_reguler : $active_fee_reguler * $fee->fee / 100;
            //             $total_fee_target = $total_fee_target == 0 ? $total_fee_target : $total_fee_target * $fee->fee / 100;
            //             $active_fee_target = $active_fee_target == 0 ? $active_fee_target : $active_fee_target * $fee->fee / 100;
            //         }

            //         $recap[$marketing->id]["total_fee"]["reguler"]["Q" . $quarter]["total"] = $total_fee_reguler;
            //         $recap[$marketing->id]["total_fee"]["reguler"]["Q" . $quarter]["active"] = $active_fee_reguler;
            //         $recap[$marketing->id]["total_fee"]["target"]["Q" . $quarter]["total"] = $total_fee_target;
            //         $recap[$marketing->id]["total_fee"]["target"]["Q" . $quarter]["active"] = $active_fee_target;
            //         $recap[$marketing->id]["point"]["active"] = $active_point;
            //         $recap[$marketing->id]["point"]["total"] = $total_point;
            //     }
            // }

            return $this->response("00", "success, marketing fee point recap", $recap);
        } catch (\Throwable$th) {
            return $this->response("01", "failed, cannot display marketing fee point recap", $th->getMessage());
        }
    }

    /**
     * marketing achievement target group by region on graphic
     *
     * @param Request $request
     * @return void
     */
    public function marketingAchievementTargetForGraphic(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $first_year = Carbon::now()->format("Y");
        $second_year = Carbon::now()->subYear()->format("Y");
        if ($request->has("first_year")) {
            $first_year = $request->first_year;
            $second_year = (int) $first_year - 1;
        }

        if ($request->has("second_year")) {
            $second_year = $request->second_year;
        }

        $global_detail = [];

        $passed_marketing = [
            $first_year => 0,
            $second_year => 0,
        ];

        $all_marketing = [
            $first_year => 0,
            $second_year => 0,
        ];

        try {
            $district_on_region = null;
            $personel_on_district = [];
            $personel_region = [];

            /* get all marketing on region if request has region_id */
            if ($request->has("region_id")) {
                $personel_region = $this->marketingListByAreaId($request->region_id);
            }

            /**
             * marketing count per month
             */
            $marketing_list = $this->personel->query()->select("id")
                // ->whereHas("salesOrder")
                ->with([
                    "marketingStatusChangeLog" => function ($QQQ) {
                        return $QQQ->orderBy("created_at");
                    },
                ])
                ->where(function ($QQQ) use ($second_year) {
                    return $QQQ
                        ->whereYear("join_date", ">=", $second_year)
                        ->orWhereNull("join_date");
                })
                ->where(function ($QQQ) use ($first_year) {
                    return $QQQ
                        ->whereYear("resign_date", "<=", $first_year)
                        ->orWhereNull("resign_date");
                })
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    $marketing_list_region = $this->marketingListByAreaId($request->region_id);
                    return $QQQ->whereIn("id", $marketing_list_region);
                })
                ->get();

            // region dan fee jumlah marketing == 6
            $months = [
                '01' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Jan",
                ],
                '02' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Feb",
                ],
                '03' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Mar",
                ],
                '04' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Apr",
                ],
                '05' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "May",
                ],
                '06' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Jun",
                ],
                '07' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Jul",
                ],
                '08' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Aug",
                ],
                '09' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Sep",
                ],
                '10' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Oct",
                ],
                '11' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Nov",
                ],
                '12' => [
                    $second_year => [
                        "all_marketing" => $all_marketing[$second_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => $all_marketing[$first_year],
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    "month" => "Dec",
                ],
            ];

            foreach ($months as $month => $detail) {
                $months[$month][$second_year]["all_marketing"] = collect($marketing_list)
                    ->filter(function ($value, $key) use ($second_year, $month) {
                        if (count($value->marketingStatusChangeLog) !== 0) {
                            $active_date = collect($value->marketingStatusChangeLog)
                                ->filter(function ($val, $key) use ($second_year, $month) {
                                    return
                                        ($val->created_at >= $second_year . "-" . $month . "-01" && $val->created_at <= $second_year . "-" . $month . "-31")
                                        &&
                                        (
                                        ($val->status == "3" && $val->created_at > $second_year . "-" . $month . "-01")
                                        ||
                                        in_array($val->status, ["1", "2"])
                                    );
                                })
                                ->count();

                            if ($active_date >= 1) {
                                return
                                    (($value->resign_date >= $second_year . "-" . $month . "-31") || $value->resign_date == null)
                                    &&
                                    (($value->join_date >= $second_year . "-01-01" && $value->join_date <= $second_year . "-" . $month . "-31") || $value->join_date == null)
                                    &&
                                    ($value->created_at >= $second_year . "-" . $month . "-01" && $value->created_at <= $second_year . "-" . $month . "-31")
                                    &&
                                    (
                                    ($value->status == "3" && $value->created_at > $second_year . "-" . $month . "-01")
                                    ||
                                    in_array($value->status, ["1", "2"])
                                );
                            }
                        }
                        return
                            (($value->resign_date >= $second_year . "-" . $month . "-31") || $value->resign_date == null)
                            &&
                            (($value->join_date >= $second_year . "-01-01" && $value->join_date <= $second_year . "-" . $month . "-31") || $value->join_date == null);
                    })
                    ->count();

                $months[$month][$first_year]["all_marketing"] = collect($marketing_list)
                    ->filter(function ($value, $key) use ($first_year, $month, $detail) {
                        if (count($value->marketingStatusChangeLog) !== 0) {
                            $active_date = collect($value->marketingStatusChangeLog)
                                ->filter(function ($val, $key) use ($first_year, $month) {
                                    return
                                        ($val->created_at >= $first_year . "-" . $month . "-01" && $val->created_at <= $first_year . "-" . $month . "-31")
                                        &&
                                        (
                                        ($val->status == "3" && $val->created_at > $first_year . "-" . $month . "-01")
                                        ||
                                        in_array($val->status, ["1", "2"])
                                    );
                                })
                                ->count();

                            if ($active_date >= 1) {
                                return
                                    (($value->resign_date >= $first_year . "-" . $month . "-31") || $value->resign_date == null)
                                    &&
                                    (($value->join_date >= $first_year . "-01-01" && $value->join_date <= $first_year . "-" . $month . "-31") || $value->join_date == null)
                                    &&
                                    ($value->created_at >= $first_year . "-" . $month . "-01" && $value->created_at <= $first_year . "-" . $month . "-31")
                                    &&
                                    (
                                    ($value->status == "3" && $value->created_at > $first_year . "-" . $month . "-01")
                                    ||
                                    in_array($value->status, ["1", "2"])
                                );
                            }
                        }

                        return
                            (($value->resign_date >= $first_year . "-" . $month . "-31") || $value->resign_date == null)
                            &&
                            (($value->join_date >= $first_year . "-01-01" && $value->join_date <= $first_year . "-" . $month . "-31") || $value->join_date == null);
                    })
                    ->count();

                $months[$month][$second_year]["year"] = $second_year;
                $months[$month][$first_year]["year"] = $first_year;
            }

            $sales_orders = $this->sales_order->query()
                ->with([
                    "invoiceHasOne",
                    "sales_order_detail",
                    "personel" => function ($QQQ) {
                        return $QQQ                  
                            ->where("status", "1")
                            ->whereHas("areaMarketing",function($query){
                                return $query->with([
                                    "areaMarketing" => function ($QQQ) {
                                        return $QQQ->with([
                                            "subRegionWithRegion" => function ($QQQ) {
                                                return $QQQ->with([
                                                    "region",
                                                ]);
                                            },
                                        ]);
                                    },
                                ]);
                            });
                    },
                ])
                ->where("status", "confirmed")
                ->whereHas("personel", function ($QQQ) {
                    return $QQQ
                        ->where("status", "1")
                        ->whereHas("areaMarketing");
                })

            /* default year */
                ->when(!$request->first_year, function ($QQQ) {
                    return $QQQ
                        ->whereYear("created_at", Carbon::now())
                        ->orWhereYear("created_at", Carbon::now()->subYear());
                })

            /* filter by first year */
                ->when($request->has("first_year"), function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereYear("created_at", $request->first_year)
                        ->when(!$request->has("second_year"), function ($QQQ) use ($request) {
                            return $QQQ->orWhereYear("created_at", (int) $request->first_year - 1);
                        })

                        /* filter by first year */
                        ->when($request->has("second_year"), function ($QQQ) use ($request) {
                            return $QQQ
                                ->orWhereYear("created_at", $request->second_year);
                        });
                })

            /* filter by region */
                ->when($request->has("region_id"), function ($QQQ) use ($personel_region) {
                    return $QQQ->whereIn("personel_id", $personel_region);
                })

            /* list supervisor */
                ->when(auth()->user()->hasAnyRole("Regional Marketing Coordinator (RMC)", "Assistant MDM", "Marketing District Manager (MDM)"), function ($QQQ) {
                    $personel_list = $this->getChildren(auth()->user()->personel_id);
                    return $QQQ->whereIn("personel_id", $personel_list);
                })

                ->orderBy("created_at")
                ->get();

            $sales_order_groupBy_year = $sales_orders->groupBy([
                function ($val) {return $val->created_at->format("Y");},
                function ($val) {return $val->personel_id;},
            ]);

            $test = [];
            $target = [];
            foreach ($sales_order_groupBy_year as $year => $personel_order) {
                foreach ($personel_order as $personel_id => $orders) {
                    $all_marketing[$year] += 1;
                    $achievement = [
                        $first_year => 0,
                        $second_year => 0,
                    ];

                    $personel_target = 0;
                    foreach ($orders as $order) {
                        if ($order->type == "2") {
                            $achievement[$year] += $order->total;
                        } else {
                            if ($order->invoice) {
                                $achievement[$year] += $order->invoice->total;
                            }
                        }

                        $personel_target = $order->personel ? $order->personel->target : 0;
                    }

                    if ($achievement[$year] >= $personel_target) {
                        $passed_marketing[$year] += 1;
                    }
                }
            }

            /* recap per year */
            $global_detail["data"][$second_year]["all_marketing"] = $all_marketing[$second_year];
            $global_detail["data"][$second_year]["marketing_pass_target"] = $passed_marketing[$second_year];
            $global_detail["data"][$second_year]["percentage"] = $all_marketing[$second_year] !== 0 ? $passed_marketing[$second_year] * 100 / $all_marketing[$second_year] : 0;

            $global_detail["data"][$first_year]["all_marketing"] = $all_marketing[$first_year];
            $global_detail["data"][$first_year]["marketing_pass_target"] = $passed_marketing[$first_year];
            $global_detail["data"][$first_year]["percentage"] = $all_marketing[$first_year] > 0 ? $passed_marketing[$first_year] * 100 / $all_marketing[$first_year] : 0;

            $first_year_percentage = $all_marketing[$first_year] > 0 ? $passed_marketing[$first_year] * 100 / $all_marketing[$first_year] : 0;
            $second_year_percentage = $all_marketing[$second_year] > 0 ? $passed_marketing[$second_year] * 100 / $all_marketing[$second_year] : 0;
            $global_detail["data"]["comparison_percentage"] = $first_year_percentage - $second_year_percentage;

            /**
             * recap per month if region id send
             */
            if ($request->has("region_id")) {
                $achievements = [
                    '01' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '02' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '03' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '04' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '05' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '06' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '07' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '08' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '09' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '10' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '11' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                    '12' => [
                        $second_year => [
                            "achievement" => 0,
                        ],
                        $first_year => [
                            "achievement" => 0,
                        ],
                    ],
                ];

                $sales_order_groupBy_month = $sales_orders->groupBy([
                    function ($val) {return $val->created_at->format("m");},
                    function ($val) {return $val->created_at->format("Y");},
                    function ($val) {return $val->personel_id;},
                ]);

                foreach ($sales_order_groupBy_month as $month => $years) {
                    foreach ($years as $year => $personel_order) {
                        foreach ($personel_order as $personel_id => $orders) {
                            $personel_target = 0;
                            foreach ($orders as $order) {
                                $region_detail = $order->personel ? $order->personel->areaMarketing->subRegionWithRegion->region->id : null;
                                if ($order->type == "2") {
                                    $achievements[$month][$year]["achievement"] += $order->total;
                                } else {
                                    if ($order->invoice) {
                                        $achievements[$month][$year]["achievement"] += $order->invoice->total;
                                    }
                                }

                                $personel_target = $order->personel ? $order->personel->target : 0;
                            }

                            if ($achievements[$month][$year]["achievement"] >= $personel_target) {
                                $months[$month][$year]["marketing_pass_target"] += 1;
                            }
                        }
                    }
                    $months[$month][$first_year]["percentage"] = $all_marketing[$first_year] !== 0 ? $months[$month][$first_year]["marketing_pass_target"] * 100 / $all_marketing[$first_year] : 0;
                    $months[$month][$second_year]["percentage"] = $all_marketing[$second_year] !== 0 ? $months[$month][$second_year]["marketing_pass_target"] * 100 / $all_marketing[$second_year] : 0;

                }

                return $this->response(
                    "00",
                    "recap per month on spesific region",
                    [
                        "detail_per_year" => $global_detail,
                        "detail_per_month_on_spesific_region" => $months,
                    ]
                );
            }

            /* recap per region */
            $sales_ordera_groupBy_region = $sales_orders->groupBy([
                function ($val) {return $val->personel ? $val->personel->areaMarketing->subRegionWithRegion->region->id : null;},
                function ($val) {return $val->created_at->format("Y");},
                function ($val) {return $val->personel_id;},
            ]);

            $detail_per_region = [
                "region" => null,
                "recap" => [
                    $second_year => [
                        "all_marketing" => 0,
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                    $first_year => [
                        "all_marketing" => 0,
                        "marketing_pass_target" => 0,
                        "percentage" => 0,
                    ],
                ],
            ];

            $passed_marketing = [
                $first_year => 0,
                $second_year => 0,
            ];

            $all_marketing = [
                $first_year => 0,
                $second_year => 0,
            ];

            $recap_per_region = [];

            foreach ($sales_ordera_groupBy_region as $region => $years) {
                $recap_per_region[$region] = $detail_per_region;

                /* catch region detail */
                $region_detail = null;
                foreach ($years as $year => $personel_order) {
                    foreach ($personel_order as $personel_id => $orders) {
                        $achievement = [
                            $first_year => 0,
                            $second_year => 0,
                        ];
                        $personel_target = 0;
                        $all_marketing[$year] += 1;
                        foreach ($orders as $order) {
                            $region_detail = $order->personel ? $order->personel->areaMarketing->subRegionWithRegion->region : null;
                            if ($order->type == "2") {
                                $achievement[$year] += $order->total;
                            } else {
                                if ($order->invoice) {
                                    $achievement[$year] += $order->invoice->total;
                                }
                            }

                            $personel_target = $order->personel ? $order->personel->target : 0;
                        }

                        if ($achievement[$year] >= $personel_target) {
                            $passed_marketing[$year] += 1;
                        }
                    }

                    $recap_per_region[$region]["region"] = $region_detail;
                    $recap_per_region[$region]["recap"][$year]["all_marketing"] = $all_marketing[$year];
                    $recap_per_region[$region]["recap"][$year]["marketing_pass_target"] = $passed_marketing[$year];
                    $recap_per_region[$region]["recap"][$year]["percentage"] = $all_marketing[$year] !== 0 ? $passed_marketing[$year] * 100 / $all_marketing[$year] : 0;
                }
            }

            return $this->response(
                "00",
                "recap pe year and per region",
                [
                    "recap_per_year" => $global_detail,
                    "recap_per_region" => collect($recap_per_region)->filter(function ($user, $key) {
                        return $user["region"] != null;
                    }),
                ]
            );

            /**
             * pending code
             * ==================================================================================================
             */
            $personels = $this->personel->query()
                ->with([
                    "salesOrder" => function ($QQQ) use ($request) {
                        return $QQQ
                            ->with("invoice")
                            ->where("status", "confirmed")
                            ->when(!$request->first_year, function ($QQQ) {
                                return $QQQ
                                    ->whereYear("created_at", Carbon::now());
                                // ->orWhereYear("created_at", Carbon::now()->subYear());
                            })

                            /* filter by first year */
                            ->when($request->has("first_year"), function ($QQQ) use ($request) {
                                return $QQQ
                                    ->whereYear("created_at", $request->first_year)
                                    ->when(!$request->has("second_year"), function ($QQQ) use ($request) {
                                        return $QQQ->orWhereYear("created_at", (int) $request->first_year - 1);
                                    })

                                    /* filter by first year */
                                    ->when($request->has("second_year"), function ($QQQ) use ($request) {
                                        return $QQQ
                                            ->orWhereYear("created_at", $request->second_year);
                                    });
                            })
                            ->orderBy("created_at");
                    },
                    "areaMarketing" => function ($QQQ) {
                        return $QQQ->with([
                            "subRegionWithRegion" => function ($QQQ) {
                                return $QQQ->with([
                                    "region",
                                ]);
                            },
                        ]);
                    },
                ])
                ->where("status", "1")
                ->get();

            foreach ($personels as $key => $personel) {
                $personels[$key]->sales_order_grouped = collect($personel->salesOrder)->groupBy([
                    function ($val) {return $val->created_at->format("Y");},
                ]);

                $achievement = [
                    $first_year => 0,
                    $second_year => 0,
                ];

                foreach ($personel->sales_order_grouped as $year => $sales_orders) {
                    $all_marketing[$year] += 1;
                    foreach ($sales_orders as $order) {
                        if ($order->type == "2") {
                            $achievement[$year] += $order->total;
                        } else {
                            if ($order->invoice) {
                                $achievement[$year] += $order->invoice->total;
                            }
                        }
                    }
                    if ($personel->target >= $achievement[$year]) {
                        $passed_marketing[$year] += 1;
                    }
                }

            }
            $global_detail["data"][$first_year]["all_marketing"] = $all_marketing[$first_year];
            $global_detail["data"][$first_year]["marketing_pass_target"] = $passed_marketing[$first_year];
            $global_detail["data"][$first_year]["percentage"] = $passed_marketing[$first_year] * 100 / $all_marketing[$first_year];

            $global_detail["data"][$second_year]["all_marketing"] = $all_marketing[$second_year];
            $global_detail["data"][$second_year]["marketing_pass_target"] = $passed_marketing[$second_year];
            $global_detail["data"][$first_year]["percentage"] = $passed_marketing[$second_year] * 100 / $all_marketing[$second_year];

            $global_detail["data"]["comparison_percentage"] = $passed_marketing[$second_year] * 100 / $all_marketing[$second_year] - $passed_marketing[$first_year] * 100 / $all_marketing[$first_year];

            return [
                "personel" => $personels,
                "recap" => $global_detail,
            ];

            return $this->response("00", "success to get marketing achievement target recap", $sales_order);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed', $th->getMessage());
        }
    }
}

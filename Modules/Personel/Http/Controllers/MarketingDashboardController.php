<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ChildrenList;
use App\Traits\DistributorTrait;
use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\UserAccessHistory;
use Modules\DataAcuan\Actions\MarketingArea\GetApplicatorAreaAction;
use Modules\DataAcuan\Entities\ProductMandatory;
use Modules\ForeCast\Entities\ForeCast;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\Store;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Repositories\PersonelRepository;
use Modules\PlantingCalendar\Entities\PlantingCalendar;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Traits\SalesOrderTrait;

class MarketingDashboardController extends Controller
{
    use ResponseHandlerV2;
    use DistributorTrait;
    use SalesOrderTrait;
    use ChildrenList;

    public function __construct(
        SalesOrderDetail $sales_order_detail,
        PlantingCalendar $planting_calendar,
        ProductMandatory $product_mandatory,
        SalesOrder $sales_order,
        ForeCast $fore_cast,
        Personel $personel,
        Invoice $invoice,
        Dealer $dealer,
        Store $store,
    ) {
        $this->sales_order_detail = $sales_order_detail;
        $this->planting_calendar = $planting_calendar;
        $this->product_mandatory = $product_mandatory;
        $this->sales_order = $sales_order;
        $this->fore_cast = $fore_cast;
        $this->personel = $personel;
        $this->invoice = $invoice;
        $this->dealer = $dealer;
        $this->store = $store;
    }

    public function __invoke(Request $request)
    {

        $personel_ids = [];
        $area_ids = [];
        $district_ids = [];
        $year = $request->has("date") ? Carbon::createFromDate($request->date, 01, 01)->format("Y") : now()->format("Y");
        // $startDate = $request->has("date") ? Carbon::createFromDate($request->date, 01, 01)->startOfYear()->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        // $endDate = $request->has("date") ? Carbon::createFromDate($request->date, 01, 01)->endOfYear()->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        try {
            if ($request->has("record_access")) {
                $access_history = new UserAccessHistory();
                $access_history->fill($request->record_access);
                $access_history->save();
            }
            $target_marketing = 0;
            $interval = CarbonPeriod::create($startDate, '1 month', $endDate);
            $interval = collect($interval)->map(fn($date) => $date->format("Y-m"));

            /**
             * list marketing
             */
            if ($request->has('personel_id')) {
                $personel_ids = [$request->personel_id];
                $target_marketing = $this->personel->findOrFail($request->personel_id)->target;
            } elseif ($request->has("scope_supervisor")) {

                $personel_ids = $this->getChildren($request->scope_supervisor);
                $target_marketing = $this->personel->whereIn("id", $personel_ids)->get()->sum("target");
            } elseif ($request->has("scope_applicator")) {
                $personel_ids = [];
                $applicator_areas = (new GetApplicatorAreaAction)($request->scope_applicator);
                $area_ids = $applicator_areas->pluck("id")->toArray();
                $district_ids = $applicator_areas->pluck("district_id")->toArray();
            } else {
                $personel_ids = $this->personel->query()
                    ->whereHas("position", function ($QQQ) {
                        return $QQQ->whereIn("name", marketing_positions());
                    })
                    ->get()
                    ->map(fn($marketing) => $marketing->id);
            }

            $sales_orders_data = $this->sales_order->query()
                ->with([
                    "invoice" => function ($QQQ) use ($startDate, $endDate) {
                        return $QQQ
                            ->whereBetween("created_at", [$startDate, $endDate])
                            ->orderBy("created_at");
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                ])

            /* filter distriutor  */
                ->when(collect($request->order_type)->contains(fn($type) => $type <= 2) && !collect($request->order_type)->contains(fn($type) => $type >= 3), function ($QQQ) {
                    return $QQQ->where("type", 1);
                })
                ->when(collect($request->order_type)->contains(fn($type) => $type >= 3) && !collect($request->order_type)->contains(fn($type) => $type <= 2), function ($QQQ) {
                    return $QQQ->where("type", 2);
                })
                ->when(collect($request->order_type)->contains(fn($type) => $type >= 1 && $type <= 4), function ($QQQ) {
                    return $QQQ->whereIn("type", [1, 2]);
                })

            /* filter model */
                ->when($request->has("model"), function ($QQQ) use ($request) {
                    return $QQQ->whereIn("model", $request->model);
                })
                ->whereIn('personel_id', $personel_ids)
                ->considerOrderStatusForRecap()
                ->salesOrderBetweenToDate($startDate, $endDate)
                ->get();

            /* filter distributor, retailer, direct, indirect */
            $sales_orders = $this->filterDirectIndirectDistributorRetailer($sales_orders_data, $request->has("order_type") ? $request->order_type : [1, 2, 3, 4]);

            /*
            |-----------------------------
            | Forecast achievement
            | for amrketing or spv
            |------------------------
             */
            $forecast_achievement = null;
            if ($request->has("personel_id") || $request->has("scope_supervisor")) {

                $forecasts = $this->fore_cast->query()
                    ->with("personelList")
                    ->whereIn('personel_id', $personel_ids)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->orderBy("date")
                    ->get()
                    ->groupBy([
                        function ($val) {
                            return Carbon::parse($val->date)->format("Y-m");
                        },
                    ])
                    ->map(function ($forecast, $month) {
                        $detail = [
                            "total_nominal" => collect($forecast)->sum("nominal"),
                            "date_filter" => $month,
                            "personel_target" => $forecast[0]->personelList->target,
                        ];
                        return $detail;
                    })
                    ->values();

                $forecast_from_orders = $sales_orders
                    ->groupBy([
                        function ($val) {
                            if ($val->type == "2") {
                                return Carbon::parse($val->date)->format("Y-m");
                            }
                            return Carbon::parse($val->invoice->created_at)->format("Y-m");
                        },
                    ])
                    ->filter(fn($order_per_month, $month) => $month == now()->format("Y-m"))
                    ->map(function ($order_per_month, $month) {
                        $detail = [
                            "total_nominal" => collect($order_per_month)->sum(function ($order) {
                                if ($order->type == "2") {
                                    return $order->total;
                                }
                                return $order->invoice->total;
                            }),
                            "total_direct" => collect($order_per_month)->sum(function ($order) {
                                if ($order->type == "1") {
                                    return $order->invoice->total;
                                }
                            }),
                            "total_indirect" => collect($order_per_month)->sum(function ($order) {
                                if ($order->type == "2") {
                                    return $order->total;
                                }
                            }),
                            "total_nominal_settle" => collect($order_per_month)
                                ->filter(fn($order) => $this->isSettle($order))
                                ->sum(function ($order) {
                                    if ($order->type == "2") {
                                        return $order->total;
                                    }
                                    return $order->invoice->total;
                                }),
                            "date_filter" => $month,
                        ];

                        return $detail;
                    })
                    ->values();

                $forecast_achievement = [];
                foreach ($interval as $date) {
                    $forecast = collect($forecasts)->where('date_filter', $date)->first();
                    $personel_target = $forecast ? $forecast["personel_target"] : $target_marketing;
                    $total_order = collect($forecast_from_orders)->where('date_filter', $date)->first();
                    $achievement_all = ($total_order ? $total_order["total_direct"] + $total_order["total_indirect"] : 0);
                    $achievement_settle = ($total_order ? $total_order["total_nominal_settle"] : 0);
                    $forecast_achievement[$date] = [
                        "forecast" => $forecast,
                        "invoice" => $total_order ? $total_order["total_direct"] : 0,
                        "indirect" => $total_order ? $total_order["total_indirect"] : 0,
                        "achievement_all" => $achievement_all,
                        "achievement_settle" => $achievement_settle,
                        "achieved" => $achievement_all < $personel_target ? "Belum Tercapai" : "Sudah Tercapai",
                        "percentage" => number_format(($achievement_all > 0 ? $achievement_all / $personel_target * 100 : 0), 2, '.', ''),
                        "target" => $personel_target,
                    ];
                }
            }

            /*
            |-----------------------------------
            | PLANTING CALENDAR
            |------------------------
             */
            $date = Carbon::parse(now()->format("M"));
            Carbon::setLocale('id_ID');
            $month = strtolower($date->translatedFormat('M'));
            $month = match ($month) {
                "agt" => "aug",
                "des" => "dec",
                default => $month
            };
            $planting_calendars = $this->planting_calendar
                ->with([
                    "area",
                    "plant",
                ])
                ->where($month, "1")
                ->when(!$request->has("scope_applicator"), function ($QQQ) use ($personel_ids) {
                    return $QQQ->whereHas("user", function ($QQQ) use ($personel_ids) {
                        return $QQQ->whereIn('personel_id', $personel_ids);
                    });
                })
                ->when($request->has("scope_applicator"), function ($QQQ) use ($area_ids) {
                    return $QQQ->whereIn("area_id", $area_ids);
                })
                ->where("year", $year)
                ->take($request->limit ? $request->limit : 5)
                ->get();

            /*
            |-----------------------------------
            | PROFORMA
            |-----------------------
             */

            $invoices = null;
            if (!$request->has("scope_applicator")) {

                $invoices = $sales_orders
                    ->where("type", "1")
                    ->filter(function ($order) {
                        return $order->invoice->payment_status != "settle";
                    })
                    ->sortBy("invoice.created_at")
                    ->map(function ($order) {
                        $order->year = confirmation_time($order)->format("Y");
                        return $order;
                    })
                    ->take($request->limit ? $request->limit : 5)
                    ->map(function ($order) {
                        $invoice = $order->invoice;
                        $invoice->sales_order = collect($order)->except("invoice");
                        return $invoice;
                    })
                    ->values();
            }

            /*
            |-----------------------------------
            | INACTIVE DEALER
            |-----------------------
             */

            $inactive_days = DB::table('fee_follow_ups')
                ->whereNull("deleted_at")
                ->orderBy("follow_up_days")
                ->first();

            $days = $inactive_days ? (int) $inactive_days->follow_up_days : 0;

            /* indirect sale in last 60/45 days */
            $sales_orders_indirect = DB::table('sales_orders')
                ->whereNull("deleted_at")
                ->where("type", "2")
                ->whereNotNull("date")
                ->where("date", ">", now()->subDays((int) $days))
                ->orderBy("date", "desc")
                ->get()
                ->pluck("store_id");

            /* direct sale in last 60/45 days */
            $sales_orders_direct = DB::table('sales_orders as s')
                ->whereNull("s.deleted_at")
                ->whereNull("i.deleted_at")
                ->leftJoin("invoices as i", "i.sales_order_id", "=", "s.id")
                ->where("s.type", "1")
                ->whereIn("s.status", ["confirmed"])
                ->where("i.created_at", ">", now()->subDays((int) $days))
                ->orderBy("i.created_at", "desc")
                ->select("s.store_id")
                ->get()
                ->pluck("store_id");

            /* new dealer */
            $dealer_new = DB::table('dealers')
                ->whereNull("deleted_at")
                ->when(!$request->has("scope_applicator"), function ($QQQ) use ($personel_ids) {
                    return $QQQ->whereIn("personel_id", $personel_ids);
                })
                ->when($request->has("scope_applicator"), function ($QQQ) use ($district_ids) {
                    $dealer_ids = DB::table('address_with_details')
                        ->whereNull("deleted_at")
                        ->where("type", "dealer")
                        ->whereIn("district_id", $district_ids)
                        ->get()
                        ->pluck("parent_id");

                    return $QQQ->whereIn("id", $dealer_ids);
                })
                ->whereDate("created_at", ">", Carbon::now()->subDays($days))
                ->get()
                ->pluck("id");

            /* dealer who have purchases in last 60/45 days */
            $active_dealer = $sales_orders_indirect->concat($sales_orders_direct)->concat($dealer_new)->flatten()->unique()->toArray();

            $dealers = $this->dealer->query()
                ->with([
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->quartalOrder(now()->format("Y"), now()->quarter);
                    }, "agencyLevel",
                ])

                ->when(!$request->has("scope_applicator"), function ($QQQ) use ($personel_ids) {
                    return $QQQ->whereIn("personel_id", $personel_ids);
                })
                ->when($request->has("scope_applicator"), function ($QQQ) use ($district_ids) {
                    $dealer_ids = DB::table('address_with_details')
                        ->whereNull("deleted_at")
                        ->where("type", "dealer")
                        ->whereIn("district_id", $district_ids)
                        ->get()
                        ->pluck("parent_id");

                    return $QQQ->whereIn("id", $dealer_ids);
                })

                ->whereNotIn("id", $active_dealer)
                ->when($request->with_trashed, function ($QQQ) {
                    return $QQQ->withTrashed();
                })
                ->limit($request->limit ? $request->limit : 5)
                ->get()
                ->map(function ($dealer) use ($days) {
                    $last_order = collect($dealer->salesOrder)
                        ->sortBy(function ($order) {
                            if ($order->type == "2") {
                                return $order->date;
                            }

                            return $order->invoice->created_at;
                        })
                        ->first();

                    /* active status */
                    $days = 0;
                    $active_status = true;
                    if ($last_order) {
                        $days = $last_order ? confirmation_time($last_order)->startOfDay()->diffInDays(Carbon::now()) : 0;
                        if ($days == 0) {
                            $days = 1;
                        }
                    } else {
                        $created_day = null;

                        $created_day = Carbon::create($dealer->created_at);

                        $days = $created_day->diffInDays(Carbon::now());
                        if ($days == 0) {
                            $days = 1;
                        }
                    }

                    $follow_up_days = DB::table("fee_follow_ups")
                        ->whereNull("deleted_at")
                        ->orderBy("follow_up_days")
                        ->first();

                    $follow_up_days_base_account = $follow_up_days->follow_up_days;

                    if (!auth()->user()->hasAnyRole("support", "super-admin", "Marketing Support", "Operational Manager", "Sales Counter (SC)", 'Operational Manager', 'Distribution Channel (DC)')) {
                        $follow_up_days_base_account -= 15;
                    }

                    if ($days > 0) {
                        if ($days > $follow_up_days_base_account) {
                            $active_status = false;
                        } else {
                            if ($dealer->deleted_at !== null) {
                                $active_status = false;
                            }
                            $active_status = true;
                        }
                    } else {
                        $active_status = false;
                    }

                    $dealer->active_status = $active_status;
                    $dealer->days = $days;
                    $dealer->active_status_references = $follow_up_days_base_account;

                    /* active on year */
                    $active_status_one_year = "-";
                    if ($last_order != null) {
                        $check = Carbon::now()->between(confirmation_time($last_order), confirmation_time($last_order)->addYear());
                        $active_status_one_year = $check;
                    }
                    $dealer->active_status_one_year = $active_status_one_year;

                    $dealer = $dealer->unsetRelation("salesOrderOnly");

                    $dealer->last_order = $last_order ? ($last_order->type == "2" ? Carbon::parse($last_order->date)->format("Y-m-d h:m:s") : $last_order->invoice->created_at->format("Y-m-d h:m:s")) : null;
                    $dealer->days_last_order = $last_order ? confirmation_time($last_order)->startOfDay()->diffInDays(Carbon::now(), false) : null;
                    return $dealer;
                    $last_order ? confirmation_time($last_order)->startOfDay()->diffInDays(Carbon::now(), false) : null;
                })
                ->sortBy("last_order")
                ->values();

            /*
            |-----------------------------------
            | KIOS
            |-----------------------
             */
            $stores = $this->store->query()
                ->with([
                    "district",
                    "city",
                    "province",
                ])

                ->when(!$request->has("scope_applicator"), function ($QQQ) use ($personel_ids) {
                    return $QQQ->whereIn("personel_id", $personel_ids);
                })
                ->when($request->has("scope_applicator"), function ($QQQ) use ($district_ids, $request) {
                    $applicator = $this->personel->findOrFail($request->scope_applicator);
                    return $QQQ
                        ->whereIn("district_id", $district_ids)
                        ->where("personel_id", $applicator->supervisor_id);
                })
                ->whereIn("status", ["transfered", "accepted"])
                ->limit($request->limit ? $request->limit : 5)
                ->get();

            /*
            |-----------------------------------
            | PRODUCT MANDATORY RECAP
            |-----------------------
             */
            $product_mandatories = null;
            if ($request->has("personel_id") || $request->has("scope_supervisor")) {

                $sales_order_detail = $this->sales_order_detail
                    ->with([
                        "product",
                        "sales_order" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice",
                            ]);
                        },
                        "product_mandatory" => function ($QQQ) {
                            return $QQQ
                                ->with([
                                    "productMandatory" => function ($QQQ) {
                                        return $QQQ->with([
                                            "productGroup",
                                            "product",
                                        ]);
                                    },
                                    "product",
                                ])
                                ->where("period_date", Carbon::now()->format("Y"));
                        },
                    ])
                    ->whereHas("product_mandatory", function ($QQQ) {
                        return $QQQ
                            ->where("period_date", Carbon::now()->format("Y"))
                            ->whereHas("productMandatory");
                    })
                    ->whereIn("sales_order_id", $sales_orders->pluck("id")->toArray())
                    ->get()
                    ->sortBy(function ($order_detail) {
                        if ($order_detail->sales_order->type == "2") {
                            return Carbon::parse($order_detail->sales_order->date);
                        }
                        return $order_detail->sales_order->invoice->created_at;
                    })
                    ->map(function ($order_detail) {
                        $order_detail->product_group_id = $order_detail?->product_mandatory?->productMandatory?->product_group_id;
                        $order_detail->month = $order_detail->sales_order->type == "2" ? Carbon::parse($order_detail->sales_order->date)->format("m") : $order_detail->sales_order->invoice->created_at->format("m");
                        return $order_detail;
                    });

                $mandatoryProductRepository = new PersonelRepository;

                /* recap achievement product mandatory */
                $product_mandatories = $this->product_mandatory
                    ->with([
                        "productMember" => function ($QQQ) {
                            return $QQQ->with("product");
                        },
                        "productGroup",
                    ])
                    ->whereHas("productGroup", function ($QQQ) {
                        return $QQQ->with("product");
                    })
                    ->where("period_date", now()->format("Y"))
                    ->get()
                    ->groupBy("product_group_id")
                    ->map(function ($product_mandatory, $group_id) use ($sales_order_detail, $mandatoryProductRepository, $personel_ids) {
                        $currentMonth = date('m');
                        $previousMonths = implode(',', array_map(function ($i) {
                            return str_pad($i, 2, '0', STR_PAD_LEFT);
                        }, range(1, $currentMonth - 1)));

                        $personelId = "'" . implode("','", $personel_ids) . "'";

                        $current_month = $mandatoryProductRepository->dashboardMandatoryProduct($group_id, $personelId, now()->format("Y"), now()->format("m"))[0]->volume ?? null;

                        $month_before = $mandatoryProductRepository->dashboardMandatoryProduct($group_id, $personelId, now()->format("Y"), $previousMonths)[0]->volume ?? null;

                        $achievement = $mandatoryProductRepository->dashboardMandatoryProduct($group_id, $personelId, now()->format("Y"), "01,02,03,04,05,06,07,08,09,10,11,12")[0]->volume ?? null;

                        $detail = [
                            "name" => $product_mandatory[0]->productGroup->name,
                            "month_before" => $month_before,
                            "month_now" => $current_month,
                            "target" => $product_mandatory[0]->target,
                            "target_in" => $product_mandatory[0]->productMember->pluck("product")->first()->metric_unit,
                            "product" => $product_mandatory[0]->productMember->pluck("product"),
                            "progress" => $achievement > 0 ? ($achievement / $product_mandatory[0]->target * 100) : 0,
                        ];

                        return $detail;
                    });
            }

            $marketing_dashboard = [
                "baterai" => $forecast_achievement,
                "planting_calendar" => $planting_calendars,
                "proforma" => $invoices,
                "inactive_dealer" => $dealers,
                "store" => $stores,
                "product_mandatory_recap" => $product_mandatories,
            ];

            return $this->response("00", "success, get Achievement", $marketing_dashboard);
        } catch (\Throwable $th) {
            return $this->response("01", "failed, to get data", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }
}

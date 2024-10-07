<?php

namespace Modules\KiosDealer\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\MarketingArea;
use App\Models\ExportRequests;
use App\Traits\ResponseHandler;
use App\Traits\DistributorStock;
use App\Traits\SuperVisorCheckV2;
use App\Traits\GmapsLinkGenerator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Modules\KiosDealer\Entities\Store;
use Modules\DataAcuan\Entities\Product;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\DealerTemp;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Entities\GradingBlock;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\KiosDealer\Entities\ExportDealer;
use Modules\KiosDealerV2\Entities\SubDealerV2;
use Modules\KiosDealer\Entities\DealerGrading;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\DataAcuan\Entities\FeeFollowUp;
use Modules\KiosDealer\Rules\DealerAddressRule;
use Modules\KiosDealer\Entities\ViewTransaction;
use Modules\KiosDealer\Events\DeletedDealerEvent;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\KiosDealer\Entities\DealerDataHistory;
use Modules\KiosDealer\Entities\DealerFileHistory;
use Modules\KiosDealer\Http\Requests\DealerRequest;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\KiosDealerV2\Events\DeliveryAddressEvent;
use Modules\KiosDealer\Entities\DealerAddressHistory;
use Modules\Distributor\ClassHelper\DistributorRevoke;
use Modules\Distributor\ClassHelper\DistributorSuspend;
use Modules\KiosDealerV2\Entities\DistributorSuspended;
use Modules\KiosDealer\Events\DealerChangeHistoryEvent;
use Modules\KiosDealer\Events\DealerNotifAcceptedEvent;
use Modules\KiosDealer\Http\Requests\DealerGradingRequest;
use Modules\KiosDealer\Http\Controllers\DealerLogController;
use Modules\KiosDealer\Events\DealerNotifChangeAcceptedEvent;
use Modules\KiosDealerV2\Entities\DistributorProductSuspended;

class DealerController extends Controller
{
    use ResponseHandler, SuperVisorCheckV2, MarketingArea;
    use GmapsLinkGenerator;
    use DistributorStock;

    public function __construct(Dealer $dealer, DealerV2 $dealerv2, Personel $personel, DealerLogController $log, DealerGrading $dealer_grading, SubDealerV2 $sub_dealer, SalesOrderDetail $sales_order_detail, DistributorSuspended $distributor_suspend, AdjustmentStock $adjustment_stock, Product $product)
    {
        $this->sub_dealer = $sub_dealer;
        $this->dealer = $dealer;
        $this->dealerv2 = $dealerv2;
        $this->user = auth()->id();
        $this->personel = $personel;
        $this->log = $log;
        $this->dealer_grading = $dealer_grading;
        $this->distributor_suspend = $distributor_suspend;
        $this->sales_order_detail = $sales_order_detail;
        $this->adjustment_stock = $adjustment_stock;
        $this->product = $product;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        ini_set('max_execution_time', 500);
        if ($request->has("sub_region_id")) {
            unset($request["region_id"]);
        }
        if ($request->has("personel_branch")) {
            unset($request["scope_supervisor"]);
        }
        try {
            $status = $this->status($request);
            $dealers = null;
            if ($request->has('agency_level') || $request->has("is_distributor")) {
                $dealers = $this->dealerv2->query()
                    ->with([
                        'addressDetail.marketingAreaDistrict',
                        'personel',
                        'personel.position',
                        'agencyLevel',
                        'changeAgencyLog',
                        'dealer_file',
                        'handover',
                        'adress_detail',
                        'salesOrder',
                        'statusFee',
                    ])

                    ->when($request->has('non_area_marketing') || $request->non_area_marketing == true, function ($q) {
                        $q->doesntHave('addressDetail.marketingAreaDistrict');
                    })

                    /* distributor filter */
                    ->when($request->has("is_distributor"), function ($q) use ($request) {
                        return $q->where("is_distributor", $request->is_distributor);
                    })

                    ->when($request->is_blocked == true, function ($query) {
                        return $query->whereNull("blocked_at");
                    })

                    /* distributor agency */
                    ->when($request->has("agency_level"), function ($q) use ($request) {
                        return $q->whereIn("agency_level_id", $request->agency_level);
                    })
                    ->orderBy("name");
                if ($request->has('noPaginate')) {
                    $dealers = $dealers->get();
                } else {
                    $dealers = $dealers->paginate($request->limit ? $request->limit : 5);
                }
            } else {

                $MarketingAreaDistrict = MarketingAreaDistrict::when($request->has("applicator_id"), function ($query) use ($request) {
                    return $query->where("applicator_id", $request->applicator_id);
                })->get()->map(function ($data) {
                    return $data->id;
                });

                $personel_marketing = $this->personel->where('name', 'like', '%' . $request->personel . '%')->pluck('id')->all();
                $dealers = $this->dealer->query()
                    ->with([
                        'adressDetail.marketingAreaDistrict',
                        'haveContestRunning',
                        'personel',
                        'personel.position',
                        'agencyLevel',
                        'dealer_file',
                        'adressDetail',
                        'statusFee',
                        'dealerBank',
                        'ownerBank',
                        'grading',
                        'suggestedGrading',
                        'contestParticiapant',
                        "activeContractContest",
                        'consideredSalesOrder' => function ($QQQ) {
                            return $QQQ
                                ->with([
                                    "invoice" => function ($QQQ) {
                                        return $QQQ->with([
                                            "payment",
                                        ]);
                                    },
                                ])
                                ->salesByYear(now()->year)
                                ->consideredOrder();
                        },
                        'salesOrderOnly' => function ($QQQ) {
                            return $QQQ
                                ->with([
                                    "invoiceOnly" => function ($QQQ) {
                                        return $QQQ->with([
                                            "payment",
                                        ]);
                                    },
                                ])
                                ->where(function ($QQQ) {
                                    return $QQQ
                                        ->where(function ($QQQ) {
                                            return $QQQ
                                                ->where("type", "1")
                                                ->where(function ($QQQ) {
                                                    return $QQQ
                                                        ->whereHas("invoiceOnly", function ($QQQ) {
                                                            return $QQQ->whereYear("created_at", Carbon::now());
                                                        })
                                                        ->orWhere(function ($QQQ) {
                                                            return $QQQ
                                                                ->where("status", "submited")
                                                                ->whereDoesntHave("invoiceOnly")
                                                                ->whereYear("created_at", Carbon::now());
                                                        });
                                                });
                                        })
                                        ->orWhere(function ($QQQ) {
                                            return $QQQ
                                                ->where("type", "2")
                                                ->whereYear("date", Carbon::now());
                                        });
                                });
                        },
                    ])

                    ->when($request->has('non_area_marketing') || $request->non_area_marketing == true, function ($q) {
                        $q->doesntHave('adressDetail.marketingAreaDistrict');
                    })

                    ->when($request->has('start_date') && $request->has('end_date'), function ($Q) use ($request) {
                        return $Q->whereBetween("created_at", [$request->start_date, $request->end_date]);
                    })

                    ->when($request->is_blocked == true, function ($query) {
                        return $query->whereNull("blocked_at");
                    })
                    /* filter by name */
                    ->when($request->has('name'), function ($Q) use ($request) {
                        return $Q->where("name", "like", "%" . $request->name . "%");
                    })

                    ->when($request->has('filter'), function ($Q) use ($request) {
                        return $Q->filterAll($request->filter);
                    })

                    /* filter by status */
                    ->whereIn('status', $status)

                    /* filter by personel name */
                    ->when($request->has("personel"), function ($Q) use ($personel_marketing) {
                        return $Q->whereIn('personel_id', $personel_marketing);
                    })

                    /* filter by sales order status */
                    ->when($request->has("sales_order_status"), function ($Q) use ($request) {
                        return $this->filterBySalesOrderStatus($Q, $request);
                    })

                    /* filter by owner name */
                    ->when($request->has("owner"), function ($Q) use ($request) {
                        return $Q->where('owner', 'like', '%' . $request->owner . '%');
                    })

                    /* filter by column dinamic */
                    ->when($request->has("filters"), function ($Q) use ($request) {
                        return $this->filterSearch($Q, $request);
                    })

                    /* filter by child */
                    ->when($request->has("hasChild"), function ($Q) use ($request) {
                        return $this->hasChild($Q, $request);
                    })

                    /* filter by personel id / marketing */
                    ->when($request->has("personel_id"), function ($Q) use ($request) {
                        $is_mm = DB::table('personels as p')
                            ->join("positions as po", "p.position_id", "po.id")
                            ->whereIn("po.name", position_mm())
                            ->where("p.id", $request->personel_id)
                            ->where("p.status", "1")
                            ->first();

                        return $Q
                            ->where(function ($QQQ) use ($request, $is_mm) {
                                return $QQQ
                                    ->where("personel_id", $request->personel_id)
                                    ->when($is_mm, function ($QQQ) {
                                        return $QQQ->orWhereNull("personel_id");
                                    });
                            });
                    })

                    /* filter by supervisor */
                    ->when($request->scope_supervisor, function ($Q) use ($request) {
                        return $Q->supervisor(auth()->user()->personel_id);
                    })

                    /* filter by distribuot area */
                    ->when($request->has("distributor_area_by_district"), function ($Q) use ($request) {
                        return $Q->distributorByArea($request->distributor_area_by_district);
                    })

                    ->when($request->has("district_id"), function ($Q) use ($request) {
                        return $Q->distributorByAreaDistrict($request->district_id);
                    })

                    /* filter by region */
                    ->when($request->has("region_id"), function ($QQQ) use ($request) {
                        return $QQQ->region($request->region_id);
                    })

                    /* filter by sub region */
                    ->when($request->has("sub_region_id"), function ($QQQ) use ($request) {
                        return $QQQ->subRegion($request->sub_region_id);
                    })

                    /* filter distributor by marketing area personel */
                    ->when($request->has("distributor_by_area_personel"), function ($QQQ) use ($request) {
                        return $QQQ->distributorByPersonelArea($request->distributor_by_area_personel);
                    })

                    /* filter sales order by personel branch */
                    ->when($request->personel_branch, function ($QQQ) {
                        return $QQQ->PersonelBranch();
                    })
                    ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                        $sort_type = "asc";
                        if ($request->has("order_type")) {
                            $sort_type = $request->order_type;
                        }
                        if ($request->sorting_column == 'marketing_name') {
                            return $QQQ->orderBy(Personel::select('name')->whereColumn('personels.id', 'dealers.personel_id'), $request->order_type);
                        } elseif ($request->sorting_column) {
                            return $QQQ->orderBy($request->sorting_column, $sort_type);
                        } else {
                            return $QQQ->orderBy("updated_at", "desc");
                        }
                    })
                    ->when($request->has("applicator_id"), function ($query) use ($MarketingAreaDistrict, $request) {
                        $MarketingApplicator = Personel::findOrFail($request->applicator_id)->supervisor_id;
                        return $query->whereHas("areaDistrictStore", function ($Q) use ($MarketingAreaDistrict) {
                            return $Q->whereIn("marketing_area_districts.id", $MarketingAreaDistrict);
                        })->where("personel_id", $MarketingApplicator);
                    })
                    ->when($request->unmatch_grade_with_suggested_grade_only, function ($QQQ) use ($request) {
                        return $QQQ->unmatchGradeWithSuggestedGrade($request->unmatch_grade_with_suggested_grade_only);
                    })->when($request->has('activity'), function($q) use ($request) {

                        $activity = $request->activity;

                        $subquery = DB::table('sales_orders as so')
                            ->select(
                                'so.id',
                                'so.store_id',
                                DB::raw('COUNT(so.id) AS count_sales'),
                                DB::raw('MAX(
                                    CASE
                                        WHEN so.type = 2 AND so.status IN ("confirmed", "pending", "returned") THEN COALESCE(so.date, so.updated_at)
                                        WHEN so.type = 2 AND so.status = "canceled" THEN so.created_at
                                        WHEN so.type = 1 AND so.status IN ("confirmed", "pending", "returned") THEN COALESCE(i.created_at, so.updated_at)
                                        WHEN so.type = 2 AND so.date IS NOT NULL THEN so.date
                                        ELSE so.created_at
                                    END
                                ) AS last_order')
                            )
                            ->leftJoin('invoices as i', function ($join) {
                                $join->on('i.sales_order_id', '=', 'so.id')
                                    ->whereNull('i.deleted_at');
                            })
                            ->whereNull('so.deleted_at')
                            ->whereIn('so.status', ['confirmed', 'pending', 'returned'])
                            ->where('so.model', '=', 1)
                            ->groupBy('so.store_id');

                        return $q->leftJoinSub($subquery, 'ss', function ($join) {
                            $join->on('dealers.id', '=', 'ss.store_id');
                        })
                        ->select('dealers.*', 'ss.*')
                        ->addSelect(DB::raw("(TO_DAYS(NOW()) - TO_DAYS(ss.last_order)) AS days_since_last_purchase"))
                        ->where(function($q) use($activity){
                            $followUpDays = FeeFollowUp::orderBy("follow_up_days", 'asc')->first();
                            if (auth()->user()->personel->position == "Marketing Support") {
                                $max = $followUpDays->follow_up_days;
                            }else{
                                $max = $followUpDays->follow_up_days - 15;
                            }

                            if (in_array(1, $activity)) {
                                $q->orWhere(function($q) use($max){
                                    $q->where("count_sales", ">=", 0)->where("days_since_last_purchase", "<=", $max)->whereNull("blocked_at");
                                });
                            }

                            if (in_array(2, $activity)) {
                                $q->orWhere(function($q) use($max){
                                    $q->where("count_sales", ">", 0)->where("days_since_last_purchase", ">=", $max)->whereNull("blocked_at");
                                });
                            }

                            if (in_array(3, $activity)) {
                                $q->orWhereNotNull("blocked_at");
                            }
                        });
                    });

                if ($request->has('noPaginate')) {
                    $dealers = $dealers->get();
                } else {

                    if (!$request->sorting_column2) {

                        $dealers = $dealers->paginate($request->limit ? $request->limit : 10);
                    } else {
                        $dealers = $dealers->get();
                    }
                }
            }

            foreach ($dealers as $dealer) {
                $indirect_amount_order = $dealer->consideredSalesOrder
                    ->where("type", "2")
                    ->sum("total");

                $direct_amount_order = $dealer->consideredSalesOrder
                    ->where("type", "1")
                    ->sum(function ($col) {
                        return $col->invoice->total + $col->invoice->ppn;
                    });

                $dealer->on_contest = $dealer->haveContestRunning ? true : false;
                $dealer->amount_order = $indirect_amount_order + $direct_amount_order;
                $dealer->direct_amount_order = $direct_amount_order;
                $dealer->indirect_amount_order = $indirect_amount_order;

                $dealer->count_order = collect($dealer->consideredSalesOrder)->count();

                $total_payment = 0;
                $direct_total_payment = collect($dealer->consideredSalesOrder)
                    ->where("type", "1")
                    ->where("invoice", "!=", null)
                    ->map(function ($order, $key) use ($total_payment) {
                        $total_payment = collect($order->invoice->payment)->sum("nominal");
                        return $total_payment;
                    })->sum();

                $dealer->paid_amount = $direct_total_payment + $indirect_amount_order;
                $dealer->unpaid_amount = $direct_amount_order - $direct_total_payment;

                /* last order attribute */
                $last_order = $dealer
                    ->consideredSalesOrder
                    ->map(function ($order) {
                        $order["confirmed_at"] = confirmation_time($order);
                        return $order;
                    })
                    ->sortByDesc("confirmed_at")
                    ->first()
                    ?->confirmed_at;

                $dealer->last_order = $last_order;

                /* days last order */
                $dealer->days_last_order = $last_order ? Carbon::createFromFormat('Y-m-d', $last_order->format("Y-m-d"))->diffInDays(Carbon::createFromFormat('Y-m-d', Carbon::now()->format("Y-m-d")), false) : 0;

                /* active status */
                $days = 0;
                $active_status = true;
                if ($last_order) {
                    $days = $last_order ? Carbon::createFromFormat('Y-m-d', $last_order->format("Y-m-d"))->diffInDays(Carbon::createFromFormat('Y-m-d', Carbon::now()->format("Y-m-d")), false) : 0;
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
                $dealer->follow_up_days_base_account = $follow_up_days_base_account;
                $dealer->days = $days;

                /* active on year */
                $active_status_one_year = "-";
                if ($last_order != null) {
                    $check = Carbon::now()->between($last_order, Carbon::createFromFormat('Y-m-d H:i:s', $last_order)->addYear());
                    $active_status_one_year = $check;
                }
                $dealer->active_status_one_year = $active_status_one_year;

                $dealer = $dealer->unsetRelation("salesOrderOnly");
                $dealer = $dealer->unsetRelation("consideredSalesOrder");
            }

            if ($request->sorting_column2 == 'last_order') {
                $currentPage = LengthAwarePaginator::resolveCurrentPage();
                $pageLimit = $request->limit > 0 ? $request->limit : 10;

                if ($request->order_type == 'desc') {
                    $dealers = collect($dealers)->sortByDesc('last_order')->all();
                } else {
                    $dealers = collect($dealers)->sortBy('last_order')->all();
                }

                // slice the current page items
                $currentItems = collect($dealers)->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

                // you may not need the $path here but might be helpful..
                $path = LengthAwarePaginator::resolveCurrentPath();

                // Build the new paginator
                $dealers = new LengthAwarePaginator($currentItems, count($dealers), $pageLimit, $currentPage, ['path' => $path]);
            }

            if ($request->has('noPaginate')) {
                $dealers = $dealers->values()->all();
                if ($request->has("is_active") == true) {
                    $dealers = collect($dealers);
                }
            }

            if ($request->has("is_active") == true) {
                $dealers = $dealers->filter(function ($item, $k) {

                    return $item->active_status_one_year === true;
                    // return $item;
                });

                if ($request->has('noPaginate')) {
                    $dealers = $dealers->values()->all();
                } else {

                    $currentPage = LengthAwarePaginator::resolveCurrentPage();
                    $pageLimit = $request->limit > 0 ? $request->limit : 15;

                    if ($request->sorting_column2 == 'last_order') {
                        if ($request->order_type == 'desc') {
                            $dealers = collect($dealers)->sortByDesc('last_order')->all();
                        } else {
                            $dealers = collect($dealers)->sortBy('last_order')->all();
                        }
                    }

                    // slice the current page items
                    $currentItems = collect($dealers)->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

                    // you may not need the $path here but might be helpful..
                    $path = LengthAwarePaginator::resolveCurrentPath();

                    // Build the new paginator
                    $dealers = new LengthAwarePaginator($currentItems, count($dealers), $pageLimit, $currentPage, ['path' => $path]);
                }
            }

            return $this->response('00', 'Dealers index', $dealers);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed', $th->getMessage());
        }
    }

    public function paginate($items, $perPage = 15, $page = null)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    public function filterSearch($model, $request)
    {
        if ($request->has('filters')) {
            foreach ($request->filters as $i => $search) {
                if (is_array($search["value"])) {
                    foreach ($search["value"] as $key => $arraySearch) {
                        if ($key == 0) {
                            $model = $model->where($search['column'], $search["operator"], $arraySearch);
                        }
                        $model = $model->orWhere($search['column'], $search["operator"], $arraySearch);
                    }
                } else {
                    if ($i == 0) {
                        $model = $model->where($search['column'], $search["operator"], $search["value"]);
                    } else {
                        $model = $model->orWhere($search['column'], $search["operator"], $search["value"]);
                    }
                }
            }
        }
        return $model;
    }

    /**
     * check child is exist
     *
     * @param [type] $model
     * @param [type] $request
     * @return boolean
     */
    public function hasChild($model, $request)
    {
        if ($request->has('hasChild')) {
            $childQuery = $request->hasChild;
            return $model->has($request->hasChild)->withTrashed(false);
        }
        return $model;
    }

    /**
     * filter dealer by confirmed sales order
     */
    public function filterBySalesOrderStatus($model, $request)
    {
        if ($request->has("personel_id")) {
            $model = $model->whereIn("personel_id", [$request->personel_id]);
        }

        $childQuery = $request->sales_order_status;
        $model = $model->whereHas('salesOrder', function ($q) use ($childQuery) {
            $q->where('status', $childQuery);
        });

        return $model;
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return "ok";
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required',
            'owner' => 'required|string|max:255',
            'owner_address' => 'required|string|max:255',
            'address' => [
                'required',
            ],
            'owner_ktp' => 'required',
            'owner_telephone' => 'required',
            'name' => 'required',
            'status' => [
                new DealerAddressRule($request),
            ],
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        try {
            DB::beginTransaction();

            if ($request->latitude && $request->longitude) {
                $request->merge([
                    "gmaps_link" => $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude),
                ]);
            }

            /* default agnecy level */
            $agency_level = DB::table('agency_levels')->where('name', 'R3')->first();

            /* default grading (putih)*/
            $grading_id = DB::table('gradings')->where("default", true)->first();

            $address = $request->address;
            $owner_address = $request->owner_address;
            $personel_id = auth()->user()->personel_id;
            $status_fee = $this->statusFee()->id;
            $dealer_id = $this->dealerIdGeneartor();

            $dealer = $this->dealer->create([
                'dealer_id' => $dealer_id,
                'prefix' => $request->prefix,
                'name' => $request->name,
                'sufix' => $request->sufix,
                'telephone' => $request->telephone,
                'second_telephone' => $request->second_telephone,
                'owner_ktp' => $request->owner_ktp,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'gmaps_link' => $request->latitude && $request->longitude ? $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude) : null,
                'personel_id' => $personel_id,
                'address' => $address,
                'owner' => $request->owner,
                'owner_address' => $owner_address,
                'owner_telephone' => $request->owner_telephone,
                'owner_npwp' => $request->owner_npwp,
                'agency_level_id' => $agency_level->id,
                "email" => $request->email,
                'entity_id' => $request->entity_id,
                'last_grading' => Carbon::now(),
                'status' => $request->status,
                'status_color' => $request->status_color,
                'status_fee' => $status_fee,
                'grading_id' => $grading_id->id,
                "bank_account_number" => $request->bank_account_number,
                "bank_account_name" => $request->bank_account_name,
                "bank_id" => $request->bank_id,
                "owner_bank_account_number" => $request->owner_bank_account_number,
                "owner_bank_account_name" => $request->owner_bank_account_name,
                "owner_bank_id" => $request->owner_bank_id,
            ]);
            $this->dealerLog($dealer->id, "create");

            // jika ada tambah data dealer maka createorfirst ke tabel export request

            // jika tidak ada type dealer yang requested maka insert

            ExportRequests::updateOrCreate([
                "type" => "dealer",
                "status" => "requested",
            ], [
                "created_at" => now(),
            ]);

            if ($request->status == 'accepted') {
                DealerNotifAcceptedEvent::dispatch($dealer);
            }

            DB::commit();
            return $this->response('00', 'Dealer saved', $dealer);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response('01', 'failed to save dealer', [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace()
            ]);
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
            $dealer = $this->dealer->withTrashed()->findOrFail($id);
            $dealer = $this->dealer->query()
                ->with([
                    'dealer_file',
                    'agencyLevel',
                    'changeAgencyLog',
                    'entity',
                    'personel',
                    'adress_detail',
                    'salesOrderConfirmed' => function ($QQQ) {
                        return $QQQ
                            ->whereHas("invoice", function ($QQQ) {
                                return $QQQ
                                    ->with([
                                        "payment",
                                    ])
                                    ->whereYear("created_at", Carbon::now())
                                    ->orderBy("created_at", "desc");
                            })
                            ->with([
                                "invoice" => function ($QQQ) {
                                    return $QQQ
                                        ->with([
                                            "payment",
                                        ])
                                        ->whereYear("created_at", Carbon::now())
                                        ->orderBy("created_at", "desc");
                                },
                                'salesCounter',
                                'sales_order_detail',
                            ]);
                    },
                    "subRegionDealer" => function ($QQQ) {
                        return $QQQ
                            ->with([
                                "subRegion" => function ($QQQ) {
                                    return $QQQ
                                        ->with([
                                            "personel",
                                        ]);
                                },
                            ]);
                    },
                    'statusFee',
                    "regionDealer",
                    "subDealer",
                    'dealerBank',
                    'ownerBank',
                    'lastGrading',
                    'suggestedGrading',
                    'contractDistributor' => function ($QQQ) {
                        return $QQQ
                            ->where("contract_start", "<=", now()->format("Y-m-d"))
                            ->where("contract_end", ">=", now()->format("Y-m-d"));
                    },
                ])
                ->withAggregate("dealerTemp", "status")
                ->where('id', $id)
                ->withTrashed()
                ->first();

            $dealercollect = collect($dealer->salesOrderConfirmed)->sortByDesc(function ($item, $key) use ($dealer) {
                return isset($item->invoice->created_at) ? $item->invoice->created_at : 'null';
            });

            $cek = [];
            foreach ($dealercollect as $key => $value) {
                $cek[] = $value;
            }

            $dealer->active_distributor = false;
            if ($dealer->contractDistributor->count() > 0) {
                $dealer->active_distributor = true;
            }

            $dealer->on_contest = $dealer->haveContestRunning ? true : false;
            return $this->response('00', 'dealer detail', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display dealer detail', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return "edit";
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(DealerRequest $request, $id)
    {
        $personel_id = $request->personel_id;
        try {
            $dealer = $this->dealer->findOrFail($id);
            $errors = [];

            /* pending at the moment */

            /**
             * latitude and longitude check
             */
            // if (!$dealer->latitude && !$request->latitude) {
            //     $errors["latitude"] = [
            //         "validation.required",
            //     ];}

            // if (!$dealer->longitude && !$request->longitude) {
            //     $errors["longitude"] = [
            //         "validation.required",
            //     ];
            // }

            // if (collect($errors)->count()) {
            //     return $this->response("04", "invalid data send", $errors, 422);
            // }

            if ($request->latitude && $request->longitude) {
                $request->merge([
                    "gmaps_link" => $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude),
                ]);
            } else {
                $request->merge([
                    "gmaps_link" => $dealer->gmaps_link,
                ]);
            }

            // if($request->status == 'submission of changes' && $dealer->status == "filed"){
            //     $dealer_change_history = new DealerChangeHistory();

            //     $dealer_change_history->dealer_id = $dealer->id;
            //     $dealer_change_history->dealer_temp_id = $dealer->dealerTemp->id;
            //     $dealer_change_history->submited_at = $dealer->dealerTemp->submited_at;
            //     $dealer_change_history->submited_by = $dealer->dealerTemp->submited_by;
            //     $dealer_change_history->save();

            // }


            if ($request->status == 'accepted' && $dealer->status == "submission of changes" && ($dealer->dealerTemp->dealer_id == $dealer->id)) {

                // $dealer_change_history = DealerChangeHistory::where("dealer_temp_id", $dealer->dealerTemp->id)->first();
                DealerChangeHistoryEvent::dispatch($dealer);
            }


            if ($request->has("suggested_grading_id")) {
                $request = $request->except("suggested_grading_id");
            }

            $dealer->fill($request->all());
            $dealer->save();

            $this->dealerLog($id, "confirmed dealer on submission of changes");
            $dealer = $this->dealer->findOrFail($id);

            $personel_id_auth = auth()->user()->personel_id;

            $personel_support = Personel::whereNull("deleted_at")->whereHas('position', function ($qqq) {
                return $qqq->whereIn('name', [
                    'administrator',
                    'Support Bagian Distributor',
                    'Support Bagian Kegiatan',
                    'Support Distributor',
                    'Support Kegiatan',
                    'Support Supervisor',
                    'Marketing Support',
                ]);
            })->pluck('id')->toArray();

            $personel_detail = Personel::where('id', $personel_id_auth)->with([
                "areaMarketing" => function ($Q) {
                    return $Q->with([
                        "subRegionWithRegion" => function ($Q) {
                            return $Q->with([
                                "region",
                            ]);
                        },
                    ]);
                },
            ])->first();

            $Users = User::whereNull("deleted_at")->whereIn('personel_id', $personel_support)->pluck('id')->toArray();

            $notif = $personel_detail->areaMarketing ? $personel_detail->areaMarketing->subRegionWithRegion : "-";

            $details = [
                'title_notif' => $request->status == 'submission of changes' ? 'Pengajuan Perubahan Dealer ' : 'Pengajuan Dealer Baru',
                'marketing_name' => $personel_detail->name,
                'area' => $notif,
                'id_data' => $id,
                'kode_notif' => 'pengajuan-perubahan-dealer',
            ];

            // if ($request->status == 'submission of changes' || $request->status == 'filed') {
            //     if (auth()->user()->hasAnyRole(
            //         'marketing staff',
            //         'Regional Marketing (RM)',
            //         'Regional Marketing Coordinator (RMC)',
            //         'Marketing District Manager (MDM)',
            //         'Assistant MDM',
            //         'Marketing Manager (MM)',
            //         'Sales Counter (SC)',
            //         'Operational Manager',
            //         'Distribution Channel (DC)',
            //         'User Jember'
            //     )) {
            //         foreach ($Users as $key => $value) {
            //             $member = User::find($value);
            //             $member->notify(new DealerSubmission($details));
            //         }
            //     }
            // }

            $export_request_check = DB::table('export_requests')->where("type", "dealer")->where("status", "requested")->first();

            if (!$export_request_check) {
                ExportRequests::Create([
                    "type" => "dealer",
                    "status" => "requested",
                    "created_at" => now(),
                    "updated_at" => now(),
                ]);
            }

            // if ($request->status == "accepted") {
            //     DealerNotifAcceptedEvent::dispatch($dealer);
            // }

            /* delivery address generator */
            $delivery_address = DeliveryAddressEvent::dispatch($dealer);

            return $this->response('00', 'Dealer updated', $dealer);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to update dealer", [
                "message" => "failed to update dealer " . $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function updateGrading(DealerGradingRequest $request, $dealer_id)
    {
        $request->validate([
            "grading_id" => "required",
        ]);

        try {
            $dealer = $this->dealer->withTrashed()->findOrFail($dealer_id);
            $dealer->suggested_grading_id = null;
            $dealer->grading_id = $request->grading_id;
            $dealer->save();

            $grading = DB::table('gradings')
                ->where('id', $dealer->grading_id)
                ->whereNull('deleted_at')
                ->first();

            // acuan blokir
            $dealerblokir = GradingBlock::distinct()
                ->get("grading_id")
                ->map(function ($val) {
                    return $val->grading_id;
                })
                ->toArray();

            /**
             * if grading is on blocked grade
             * then dealer will blocked
             */
            if (in_array($grading->id, $dealerblokir)) {
                $dealer->grading_block_id = $request->grading_id;
                $dealer->is_block_grading = true;
                $dealer->blocked_at = Carbon::now()->format("Y-m-d");
                $dealer->blocked_by = Auth::user()->personel_id;

                $dealer->update();
            }

            /**
             * if grading is not on blocked grade
             * if dealer blocked because grading
             * then dealer will restore,
             * if not dealer will still
             * blocked
             */
            else {
                if ($dealer->grading_block_id) {
                    $dealer->grading_block_id = null;
                    $dealer->is_block_grading = false;
                    $dealer->blocked_at = null;
                    $dealer->blocked_by = null;
                }

                $dealer->save();
            }

            // $lastGrading = DealerGrading::latest()->where("dealer_id", $dealer_id)->first();
            $user_id = auth()->id();
            $dealer->attachGrading()
                ->attach($request->grading_id, [
                    "custom_credit_limit" => $request->custom_credit_limit,
                    "user_id" => $user_id,
                ]);

            $this->dealerLog($dealer_id, "update grading dealer");
            $dealer = $this->dealer->query()
                ->withTrashed()
                ->with('dealer_file', 'agencyLevel', 'entity', 'personel', 'adress_detail', 'salesOrder', 'statusFee', 'salesOrderConfirmed')
                ->where('id', $dealer_id)
                ->first();
            return $this->response('00', 'Dealer updated', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update dealer grade', $th->getMessage());
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
            $dealer = $this->dealer->findOrFail($id);

            DB::beginTransaction();

            /* event after delete */
            $deleted_dealer = DeletedDealerEvent::dispatch($dealer);

            $dealer->delete();
            $this->dealerLog($dealer->id, "delete/deactivate");

            /**
             * create new export dealer
             */
            ExportRequests::updateOrCreate([
                "type" => "dealer",
                "status" => "requested",
            ], [
                "created_at" => now(),
            ]);

            DB::commit();
            return $this->response('00', 'dealer deleted', $dealer);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response('01', 'failed to delete dealer', $th->getMessage());
        }
    }

    public function updateDealerStatus(Request $request, $id)
    {
        try {
            $dealer = $this->dealer->findOrFail($id);
            if ($request->has("status")) {
                $dealer->status = $request->status;

                $personel_id_auth = auth()->user()->personel_id;

                $personel_support = Personel::whereNull("deleted_at")->whereHas('position', function ($qqq) {
                    return $qqq->whereIn('name', [
                        'administrator',
                        'Support Bagian Distributor',
                        'Support Bagian Kegiatan',
                        'Support Distributor',
                        'Support Kegiatan',
                        'Support Supervisor',
                        'Marketing Support',
                    ]);
                })->pluck('id')->toArray();

                $personel_detail = Personel::where('id', $personel_id_auth)->with([
                    "areaMarketing" => function ($Q) {
                        return $Q->with([
                            "subRegionWithRegion" => function ($Q) {
                                return $Q->with([
                                    "region",
                                ]);
                            },
                        ]);
                    },
                ])->first();

                $Users = User::whereNull("deleted_at")->whereIn('personel_id', $personel_support)->pluck('id')->toArray();
                $notif = $personel_detail->areaMarketing ? $personel_detail->areaMarketing->subRegionWithRegion : "-";

                $details = [
                    'title_notif' => $request->status == 'submission of changes' ? 'Pengajuan Perubahan Dealer ' : 'Pengajuan Dealer Baru',
                    'marketing_name' => $personel_detail->name,
                    'area' => $notif,
                    'id_data' => $id,
                    'kode_notif' => 'pengajuan-perubahan-dealer',
                ];

                // if ($request->status == 'submission of changes' || $request->status == 'filed') {
                //     if (auth()->user()->hasAnyRole(
                //         'marketing staff',
                //         'Regional Marketing (RM)',
                //         'Regional Marketing Coordinator (RMC)',
                //         'Marketing District Manager (MDM)',
                //         'Assistant MDM',
                //         'Marketing Manager (MM)',

                //     )) {
                //         foreach ($Users as $key => $value) {
                //             $member = User::find($value);
                //             $member->notify(new DealerSubmission($details));
                //         }
                //     }
                // }
            }
            if ($request->status_color) {
                $dealer->status_color = $request->status_color;
            }
            if ($request->has("is_distributor")) {
                $dealer->is_distributor = $request->is_distributor;
            }
            $dealer->save();
            $this->dealerLog($dealer->id, "delete status update");
            return $this->response('00', 'dealer status updated', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update dealer status', $th->getMessage());
        }
    }

    public function updateDealerAgencyLevel(Request $request, $id)
    {
        try {
            $dealer = $this->dealer->findOrFail($id);
            if ($request->has("agency_level_id")) {

                $find_agency_level = DB::table('agency_levels')->where('id', $request->agency_level_id)->first();

                $get_dealer = $this->dealer->query()
                    ->with([
                        "subRegionDealer" => function ($QQQ) {
                            return $QQQ
                                ->with([
                                    "subRegion",
                                ]);
                        },
                        "regionDealer",
                    ])->findOrFail($id);

                $last_agency_level = $get_dealer->agency_level_id;

                if ($find_agency_level->name != 'D1' || $find_agency_level->name != 'D2') {
                    $dealer->agency_level_id = $request->agency_level_id;
                    $dealer->save();
                }

                if ($find_agency_level->name == 'D1') {
                    $personel_id = $get_dealer->regionDealer->personel_id;
                    $dealer->agency_level_id = $request->agency_level_id;
                    $dealer->personel_id = $personel_id;
                    $dealer->save();
                }

                if ($find_agency_level->name == 'D2') {
                    $personel_id = $get_dealer->subRegionDealer->subRegion->personel_id;
                    $dealer->agency_level_id = $request->agency_level_id;
                    $dealer->personel_id = $personel_id;
                    $dealer->save();
                }

                if ($last_agency_level !== $get_dealer->agency_level_id) {
                    $get_dealer->agencyLog()->attach(
                        $get_dealer->agency_level_id
                    );
                }
            }

            return $this->response('00', 'dealer agency level updated', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update dealer agency level', $th->getMessage());
        }
    }

    public function blockDealer(Request $request, $id)
    {
        try {

            $validate = Validator::make($request->all(), [
                "blocked_by" => "required|max:255",
            ]);

            if ($validate->fails()) {
                return $this->response("04", "invalid data send", $validate->errors());
            }

            $dealer = $this->dealer->findOrFail($id);
            if (is_null($dealer->blocked_at)) {
                $dealer->blocked_by = $request->blocked_by;
                $dealer->blocked_at = Carbon::now();
                $dealer->save();
            } else {
                $dealer->blocked_by = null;
                $dealer->blocked_at = null;
                $dealer->save();
            }

            return $this->response('00', 'dealer blocked updated', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update dealer blocked updated', $th->getMessage());
        }
    }

    public function getChildren($personel_id)
    {
        $personels_id = [$personel_id];
        $personel = $this->personel->find($personel_id);

        foreach ($personel->children as $level1) { //mdm
            $personels_id[] = $level1->id;
            if ($level1->children != []) {
                foreach ($level1->children as $level2) { //assistant mdm
                    $personels_id[] = $level2->id;
                    if ($level2->children != []) {
                        foreach ($level2->children as $level3) { //rmc
                            $personels_id[] = $level3->id;
                            if ($level3->children != []) {
                                foreach ($level3->children as $level4) { //rm
                                    $personels_id[] = $level4->id;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $personels_id;
    }

    public function checkBawahan($request, $personel_id)
    {
        $dealers = null;
        $personel = $this->personel->find($personel_id);
        $personels_id = [$personel_id];
        $status = $this->status($request);
        if ($personel->children == []) {
            $dealers = $this->dealer->query()
                ->with('personel', 'agencyLevel', 'dealer_file', 'handover', 'adress_detail', 'salesOrder', 'salesOrderConfirmed', 'statusFee')
                ->where('personel_id', $personel_id)
                ->whereIn('status', $status)
                ->orderBy((($request->has('sorting_column')) ? $request->sorting_column : 'updated_at'), (($request->has('order_type')) ? $request->order_type : 'desc'));
            if ($request->has('name')) {
                $dealers->where("name", "like", "%" . $request->name . "%");
            }
            $dealers = $this->filterSearch($dealers, $request);

            if ($request->has("sales_order_status")) {
                $dealers = $this->filterBySalesOrderStatus($dealers, $request);
            }

            if ($request->has('noPaginate')) {
                $dealers = $dealers->get();
            } else {
                $dealers = $dealers->paginate(30);
            }
        } else {
            $personels_id = $this->getChildren($personel_id);
            if ($request->has("personel_id")) {
                $personels_id = [$request->personel_id];
            }
            $dealers = $this->dealer->query()
                ->with('personel', 'agencyLevel', 'dealer_file', 'handover', 'adress_detail', 'salesOrder', 'salesOrderConfirmed', 'statusFee')
                ->whereIn('personel_id', $personels_id)
                ->whereIn('status', $status)
                ->orderBy((($request->has('sorting_column')) ? $request->sorting_column : 'updated_at'), (($request->has('order_type')) ? $request->order_type : 'desc'));
            if ($request->has('name')) {
                $dealers->where("name", "like", "%" . $request->name . "%");
            }

            $dealers = $this->filterSearch($dealers, $request);

            if ($request->has("sales_order_status")) {
                $dealers = $this->filterBySalesOrderStatus($dealers, $request);
            }

            if ($request->has('noPaginate')) {
                $dealers = $dealers->get();
            } else {
                $dealers = $dealers->paginate(30);
            }
        }
        return $dealers;
    }

    /**
     * all dealers
     */
    public function getAllDealers(Request $request)
    {
        try {
            $personel_id = auth()->user()->personel_id;
            if (auth()->user()->hasAnyRole(is_all_data())) {
                $dealers = $this->dealer->query()
                    ->with('agencyLevel')
                    ->when($request->has("is_distributor"), function ($QQQ) {
                        return $QQQ->where("is_distributor", "1");
                    })
                    ->orderBy('updated_at', 'desc')
                    ->when($request->is_blocked == true, function ($query) {
                        return $query->whereNull("blocked_at");
                    })
                    ->when($request->name, function ($QQQ) use ($request) {
                        return $QQQ->where("name", "like", "%" . $request->name . "%");
                    })
                    ->when($request->has("owner"), function ($QQQ) use ($request) {
                        return $QQQ->where("owner", "like", "%" . $request->owner . "%");
                    })
                    ->when($request->has("dealer_id"), function ($QQQ) use ($request) {
                        return $QQQ->where("dealer_id", "like", "%" . $request->dealer_id . "%");
                    })
                    ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                        $is_mm = DB::table('personels as p')
                            ->join("positions as po", "p.position_id", "po.id")
                            ->whereIn("po.name", position_mm())
                            ->where("p.id", $request->personel_id)
                            ->where("p.status", "1")
                            ->first();

                        return $QQQ
                            ->where(function ($QQQ) use ($request, $is_mm) {
                                return $QQQ
                                    ->where("personel_id", $request->personel_id)
                                    ->when($is_mm, function ($QQQ) {
                                        return $QQQ->orWhereNull("personel_id");
                                    });
                            });
                    })
                    ->when($request->is_blocked == true, function ($query) {
                        return $query->whereNull("blocked_at");
                    })
                    ->when($request->by_name_or_owner_or_cust_id, function ($QQQ) use ($request) {
                        return $QQQ->byNameOrOwnerOrDealerId($request->by_name_or_owner_or_cust_id);
                    })
                    ->get();
            } else {

                $supervisor_id = Personel::findOrFail($personel_id)->supervisor_id;

                $MarketingAreaDistrict = MarketingAreaDistrict::when($request->has("applicator_id"), function ($query) use ($request) {
                    return $query->where("applicator_id", $request->applicator_id);
                })->get()->map(function ($data) {
                    return $data->id;
                });

                $personels_id = $this->getChildren($personel_id);
                $dealers = $this->dealer->query()
                    ->with('agencyLevel')
                    ->when(!$request->has("applicator_id"), function ($query) use ($personels_id) {
                        return $query->whereIn('personel_id', $personels_id);
                    })
                    ->when($request->has("is_distributor"), function ($QQQ) {
                        return $QQQ->where("is_distributor", "1");
                    })
                    ->when($request->is_blocked == true, function ($query) {
                        return $query->whereNull("blocked_at");
                    })
                    ->when($request->name, function ($QQQ) use ($request) {
                        return $QQQ->where("name", "like", "%" . $request->name . "%");
                    })
                    ->when($request->has("owner"), function ($QQQ) use ($request) {
                        return $QQQ->where("owner", "like", "%" . $request->owner . "%");
                    })
                    ->when($request->has("dealer_id"), function ($QQQ) use ($request) {
                        return $QQQ->where("dealer_id", "like", "%" . $request->dealer_id . "%");
                    })
                    ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                        return $QQQ->where("personel_id", $request->personel_id);
                    })
                    ->when($request->has("as_supervisor"), function ($QQQ) use ($request, $supervisor_id) {
                        return $QQQ->where("personel_id", $supervisor_id);
                    })
                    ->when($request->by_name_or_owner_or_cust_id, function ($QQQ) use ($request) {
                        return $QQQ->byNameOrOwnerOrDealerId($request->by_name_or_owner_or_cust_id);
                    })
                    ->when($request->has("applicator_id"), function ($query) use ($MarketingAreaDistrict, $supervisor_id, $request) {
                        $MarketingApplicator = Personel::findOrFail($request->applicator_id)->supervisor_id;
                        return $query->whereHas("areaDistrictStore", function ($Q) use ($MarketingAreaDistrict) {
                            return $Q->whereIn("marketing_area_districts.id", $MarketingAreaDistrict);
                        })->where("personel_id", $MarketingApplicator);
                    })
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }
            return $this->response('00', 'all dealer', $dealers);
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function status($request)
    {
        $status = [
            $request->filed,
            $request->submission_of_changes,
            $request->accepted,
            $request->rejected,
            $request->draft,
        ];

        foreach ($status as $key => $stat) {
            if ($stat == null) {
                unset($status[$key]);
            }
        }

        if ($status == []) {
            $status = [
                'filed',
                'submission of changes',
                'accepted',
                'rejected',
                'draft',
            ];
        }
        return $status;
    }

    /**
     * check if dealeris inactive (soft delated)
     *
     * @param [type] $request
     * @return void
     */
    public function inactiveCheck(Request $request)
    {
        try {
            $dealer = $this->dealer->query()
                ->where('telephone', $request->telephone)
                ->first();

            return $this->response("00", "dealer phone check", $dealer);
        } catch (\Throwable $th) {
            return $this->response("01", "failed dealer phone check", $th->getMessage());
        }
    }

    /**
     * check if data is exist and not deleted
     *
     * @return void
     */
    public function existCheck($request)
    {
        $dealer = $this->dealer->query()
            ->where('telephone', $request->telephone)
            ->orWhere('owner_ktp', $request->owner_ktp)
            ->first();

        return $dealer;
    }

    /**
     * check if data is exist and not deleted
     *
     * @return void
     */
    public function existNoTelpCheck($request)
    {
        $dealer = $this->dealer->query()
            ->where('telephone', $request->telephone)
            ->first();

        return $dealer;
    }

    /**
     * check if dealeris existCheckDealerSubDealer (soft delated)
     *
     * @param [type] $request
     * @return void
     */
    public function existCheckDealerSubDealer(Request $request)
    {
        $dealer = $this->dealer->query()
            ->where('telephone', $request->telephone)
            ->when($request->dealer_id && !$request->sub_dealer_id, function ($QQQ) use ($request) {
                return $QQQ
                    ->where(function ($QQQ) use ($request) {
                        return $QQQ
                            ->where("id", "!=", $request->dealer_id)
                            ->whereHas("dealerTemp", function ($QQQ) use ($request) {
                                return $QQQ->where("id", "!==", $request->dealer_id);
                            });
                    });
            })
            ->first();

        if ($request->dealer_id) {
            if ($dealer) {
                return $this->response("00", "dealer phone check", $dealer);
            } else {
                return $this->response("00", "dealer or sub dealer with these phone number not found", null);
            }
        }

        $sub_dealer = $this->sub_dealer->query()
            ->with([
                "subDealerTemp",
            ])
            ->where('telephone', $request->telephone)
            ->when($request->sub_dealer_id, function ($QQQ) use ($request) {
                return $QQQ
                    ->where(function ($QQQ) use ($request) {
                        return $QQQ
                            ->where("id", "!=", $request->sub_dealer_id)
                            ->whereHas("subDealerTemp", function ($QQQ) use ($request) {
                                return $QQQ
                                    ->where("id", "!==", $request->sub_dealer_id);
                            });
                    });
            })
            ->first();

        return $this->response("00", "sub dealer phone check", $sub_dealer);
        if ($request->sub_dealer_id) {
            if ($sub_dealer) {
            } else {
                return $this->response("00", "dealer or sub dealer with these phone number not found", null);
            }
        }
    }

    public function existCheckDealerSubDealerOther(Request $request)
    {

        if ($request->filled('dealer_id')) {
            $dealer_temp_id = DealerTemp::where("id", $request->dealer_id)->first();
            $dealer_temp_id = $dealer_temp_id ? $dealer_temp_id->dealer_id : $request->dealer_id;
            $validator = Validator::make($request->all(), [
                'dealer_id' => 'required',
                'telephone' => 'required|unique:dealers,telephone,' . $dealer_temp_id,
            ]);
            $data = $this->dealer->where('telephone', $request->telephone)->where("id", "!=", $dealer_temp_id)->select(['id', 'prefix', 'telephone', 'name', 'dealer_id', 'address']);
        } else {
            $sub_dealer_id = SubDealerTemp::where("id", $request->sub_dealer_id)->first();
            $sub_dealer_id = $sub_dealer_id ? $sub_dealer_id->sub_dealer_id : $request->sub_dealer_id;
            $validator = Validator::make($request->all(), [
                'telephone' => 'required|unique:sub_dealers,telephone,' . $sub_dealer_id,
            ]);
            $data = $this->sub_dealer->where('telephone', $request->telephone)->where("id", "!=", $sub_dealer_id)->select(['id', 'prefix', 'telephone', 'name', 'sub_dealer_id', 'address']);
        }

        if ($validator->fails()) {
            $dealer = $data->first();
            return $this->response("00", ($request->filled('dealer_id') ? 'Dealer' : 'Sub Dealer') . " phone check", $dealer);
        }

        return $this->response("00", "dealer or sub dealer with these phone number not found", null);
    }

    public function existCheckNoTelpDealer(Request $request)
    {
        $dealer = null;
        $dealer = $this->dealer->query()
            ->where('telephone', $request->telephone)->first();

        if ($dealer) {
            return $this->response("00", "dealer phone check", $dealer);
        }

        return $this->response("00", "dealer with these phone number not found", null);
    }

    public function existCheckNoTelpSubDealer(Request $request)
    {
        $dealer = null;
        $dealer = $this->sub_dealer->query()
            ->where('telephone', $request->telephone)->first();

        if ($dealer) {
            return $this->response("00", "sub dealer phone check", $dealer);
        }

        return $this->response("00", "sub dealer with these phone number not found", null);
    }

    public function existDraftDealerTemp(Request $request)
    {
        $id = $request->dealer_id ?? $request->sub_dealer_id;

        $response = null;
        $dealer = DealerTemp::where(function($q) use($id){
            $q->where("dealer_id", $id)->orWhere("sub_dealer_id", $id)->orWhere("store_id", $id);
        })->whereIn("status", [
            "draft",
            "submission of changes"
        ])->first();

        if ($dealer) {
            $dealer = $dealer->toArray();
            $fromTransfer = null;
            $transfer = false;
            if ($dealer['store_id'] != null) {
                $transfer = true;
                $fromTransfer = "kios";
            }elseif ($dealer['sub_dealer_id'] != null) {
                $transfer = true;
                $fromTransfer = "sub_dealer";
            }

            $response = array_merge($dealer, [
                "on_transfer" => $transfer,
                "on_transfer_from" => $fromTransfer
            ]);
        }

        $subDealer = SubDealerTemp::where(function($q) use($id){
            $q->where("sub_dealer_id", $id)->orWhere("store_id", $id);
        })->whereIn("status", [
            "draft",
            "submission of changes"
        ])->first();

        if ($subDealer) {
            $subDealer = $subDealer->toArray();

            $response = array_merge($subDealer, [
                "on_transfer" => $subDealer['store_id'] == null ? false : true,
                "on_transfer_from" => $subDealer['store_id'] == null ? false : "kios"
            ]);
        }


        if ($subDealer) {
            return $this->response("00", "dealer temp draft or submission of change exist", $response);
        }

        if ($response) {
            return $this->response("00", "sub dealer temp draft or submission of change exist", $response);
        }

        return $this->response("00", "dealer temp with status draft not found", null);
    }

    /**
     * dealer log
     *
     * @param [type] $dealer_id
     * @param [type] $activity (crud)
     * @return void
     */
    public function dealerLog($dealer_id, $activity)
    {
        $request = new Request;
        $request["dealer_id"] = $dealer_id;
        $request["activity"] = $activity;
        $this->log->store($request);
    }

    /**
     * generate dealer_id
     *
     * @return void
     */
    public function dealerIdGeneartor()
    {
        try {
            $dealer = $this->dealer->query()
                ->withTrashed()
                ->with('dealer_file')
                ->orderBy('dealer_id', 'desc')
                ->first();

            $dealer_id = $dealer->dealer_id;
            $new_dealer_id = (int) $dealer_id + 1;
            return $new_dealer_id;
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to generate dealer_id', $th->getMessage());
        }
    }

    public function statusFee()
    {
        try {
            $status_fee = StatusFee::where("name", "R")->first();
            return $status_fee;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function dealerPayment(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "dealer_id" => "required|max:255",
            "payment_method_id.*" => "distinct",
        ]);

        if ($validate->fails()) {
            return $this->response("04", "invalid data send", $validate->errors());
        }

        try {
            if (auth()->user()->hasAnyRole(
                'administrator',
                'super-admin',
                'marketing staff',
                'Marketing Support',
                'Regional Marketing (RM)',
                'Regional Marketing Coordinator (RMC)',
                'Marketing District Manager (MDM)',
                'Assistant MDM',
                'Marketing Manager (MM)',
                'Sales Counter (SC)',
                'Operational Manager',
                'Support Bagian Distributor',
                'Support Distributor',
                'Support Bagian Kegiatan',
                'Support Kegiatan',
                'Support Supervisor',
                'Distribution Channel (DC)',
                'User Jember'
            )) {
                $dealer = $this->dealer->findOrFail($request->dealer_id);
                $dealer->dealerPayment()->sync($request->payment_method_id);
                return $this->response("00", "dealer payment saved", $dealer);
            }
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save dealer payment", $th->getMessage());
        }
    }

    /**
     * update custom credit limit
     */
    public function updateCustomCreditLimit(Request $request, $dealer_id)
    {
        try {
            $grading = $this->dealer_grading->query()
                ->where("dealer_id", $dealer_id)
                ->orderBy("created_at", "desc")
                ->first();

            // kalau gk ada kita buatkan aja

            if (!$grading) {
                $dealer = $this->dealer->findOrFail($dealer_id);

                $grading_update = $this->dealer_grading->create([
                    "custom_credit_limit" => $request->custom_credit_limit,
                    "grading_id" => $dealer->grading_id,
                    "dealer_id" => $dealer_id,
                    "user_id" => $this->user,
                ]);
                return $this->response("00", "success, dealer custom credit limit saved", $grading_update);
            } else {
                $grading->custom_credit_limit = $request->custom_credit_limit;
                $grading->save();
                return $this->response("00", "success, dealer custom credit limit saved", $grading);
            }
        } catch (\Throwable $th) {
            return $this->response("01", "failed to update custom credit limit", $th);
        }
    }

    /**
     * export dealer
     *
     * @return void
     */
    public function export(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $datenow = Carbon::now()->format('d-m-Y');

        /* get all district on this sub region */
        $district_id = $this->districtListByAreaId($request->region_id);

        /* get dealer by dstrict address */
        $dealer_id = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->when($request->has("region_id"), function ($QQQ) use ($district_id) {
                return $QQQ->whereIn("district_id", $district_id);
            })
            ->where("type", "dealer")
            ->get()
            ->pluck("parent_id")
            ->toArray();
        $data = ExportDealer::query()
            ->whereIn('store_id', $dealer_id)->get();

        return $this->response("00", "success", $data);
        // return (new DealerByRegionExport($request->region_id));
        //$data = (new DealerByRegionExport($request->region_id))->store('dealers_'.$datenow.'.xlsx', 's3');
        // if($request->region_id) {
        //    return (new DealerByRegionExport($request->region_id))->download('dealers_'.$datenow.'.xlsx');
        // }
        // if ($data) {
        //     // return (new SalesOrderIndirectExport())->download('list_indirect_'.$datenow.'.xlsx');
        //     return response()->json([
        //         "status" => "ok",
        //     ]);
        // }
    }

    public function exportv2(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $datenow = Carbon::now()->format('d-m-Y');
        $data = DealerV2::with('agencyLevel', 'grading', 'personel', 'addressDetail.marketingAreaDistrict.subRegion.personel')
            ->withoutAppends()
            ->when($request->has('non_area_marketing') || $request->non_area_marketing == true, function ($q) {
                $q->doesntHave('addressDetail.marketingAreaDistrict');
            })
            ->get()
            ->map(function ($q) {
                $address = $q->addressDetail->where('type', 'dealer')->first() ?? [];
                $groupRmc = !empty($address->marketingAreaDistrict->subRegion->personel) ? $address->marketingAreaDistrict->subRegion->personel->name : '-';
                $groupMdm = !empty($address->marketingAreaDistrict->subRegion->region->personel) ? $address->marketingAreaDistrict->subRegion->region->personel->name : '-';
                return [
                    "dealer_id" => $q->dealer_id,
                    "cust_id" => "CUST-" . $q->dealer_id,
                    "toko" => $q->name,
                    "agency_level" => optional($q->agencyLevel)->name,
                    "grade" => optional($q->grading)->name,
                    "is_distributor" => $q->is_distributor,
                    "marketing" => optional($q->personel)->name,
                    "owner" => $q->owner,
                    "telephone" => $q->owner_telephone,
                    "dealer_address" => $q->address,
                    "propinsi" => !empty($address) ? $address->province->name : '',
                    "kota_kabupaten" => !empty($address) ? $address->city->name : '',
                    "kecamatan" => !empty($address) ? $address->district->name : '',
                    "status" => $q->status ?? '-',
                    "group_rmc" => $groupRmc,
                    "group_mdm" => $groupMdm,
                    "owner_ktp" => $q->owner_ktp,
                    "owner_npwp" => $q->owner_npwp,
                    "owner_address" => $q->owner_address,
                    "owner_telephone" => '0' . $q->owner_telephone,
                ];
            });
        return $this->response("00", "success", $data);
    }

    public function exportThreeFarmer()
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $datenow = Carbon::now()->format('Y-m-d');

        $data = DB::table("personels as p")->selectRaw("p.id,p.name,ps.name as jabatan,(SELECT count(id) FROM stores WHERE personel_id = p.id and deleted_at is NULL and created_at < '" . $datenow . "') as kios,
        (SELECT IFNULL(SUM(petani),0) FROM (SELECT store_id,IF(COUNT(c.id) >= 3, 1, 0) as petani FROM core_farmers as c WHERE c.deleted_at IS NULL group by store_id) as a WHERE store_id in
        (SELECT id FROM stores as k WHERE personel_id = p.id and deleted_at IS NULL and created_at < '" . $datenow . "')) as kios_3petani")->leftJoin("positions as ps", "ps.id", "p.position_id")
            ->whereRaw("p.status < 3 and ps.name in('Regional Marketing (RM)','Regional Marketing Coordinator (RMC)','Marketing District Manager (MDM)','Marketing Manager (MM)','Sales Counter (SC)')")->orderBy("p.name", "asc")->get();

        return $this->response("00", "success", $data);
    }

    public function exportStorePerMarketing(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes

        $data = Store::query()
            ->selectRaw("p.name as marketing, stores.name as kiosk, stores.telephone, IFNULL((select count(id) from core_farmers where deleted_at IS NULL and store_id=stores.id group BY store_id),' - ') as farmers")
            ->leftJoin("personels as p", "p.id", "stores.personel_id")
            ->when($request->marketing_id, function ($query) use ($request) {
                return $query->where("p.id", $request->marketing_id);
            })
            ->orderBy("p.name", "asc")->get();

        return $this->response("00", "success", $data);
    }

    public function saleorOrderLast4Month()
    {
        $salesorderlast4month = $this->dealer->query()
            ->whereHas('salesOrder')->get();
        return $this->response('00', 'success, get grading dealer', $salesorderlast4month);
    }

    public function exportDealerSubDealerperYear(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $dealerSubDealer = ViewTransaction::when($request->has("year"), function ($query) use ($request) {
            return $query->whereYear("created_at", $request->year);
        })->when(!$request->has("year"), function ($query) use ($request) {
            return $query->whereYear("created_at", $request->year);
        })->when($request->has("store_id"), function ($query) use ($request) {
            return $query->where("store_id", $request->store_id);
        })
            ->when($request->has("region_id"), function ($query) use ($request) {
                return $query->where("region_id", $request->region_id);
            })
            ->get()->map(function ($item, $k) {
                return (object) [
                    "toko_id" => $item['store_id'],
                    "toko_name" => $item['toko_name'],
                    "owner" => $item['owner'],
                    "agency_level" => $item['agency_level'],
                    "grading" => $item['grading'],
                    "marketing" => $item['marketing'],
                    "region" => $item['region'],
                    "sub_region" => $item['sub_region'],
                    "total_transaksi" => $item['total'],
                    "ppn" => $item['ppn'],
                    "pembayaran" => $item['payment'],
                    "kurang_pembayaran" => $item['underpayment'],

                ];
            });
        return $this->response('00', 'success, View Transaction Shop per Year', $dealerSubDealer);
    }

    public function dealerSubDealer(Request $request)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes

            if ($request->has("sub_region_id")) {
                unset($request["region_id"]);
            }

            $sub_dealers = $this->sub_dealer->query()

                /* filter by region */
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    return $QQQ->region($request->region_id);
                })

                /* filter by sub region */
                ->when($request->has("sub_region_id"), function ($QQQ) use ($request) {
                    return $QQQ->subRegion($request->sub_region_id);
                })

                ->when($request->has("dealer_id"), function ($q) use ($request) {
                    return $q->where("sub_dealer_id", $request->dealer_id);
                })
                ->when($request->has("name"), function ($q) use ($request) {
                    return $q->where("name", "like", "%" . $request->name . "%");
                })
                ->select("id", "prefix", "name", "sufix", "owner", "sub_dealer_id", "created_at", "sub_dealer_id as store_id", "telephone", DB::raw("if(sub_dealer_id, 'sub_dealer', 'sub_dealer') as store_type"));

            $dealers = $this->dealer->query()->select("id", "prefix", "name", "sufix", "owner", "dealer_id", "created_at", "dealer_id as store_id", "telephone", DB::raw("if(dealer_id, 'dealer', 'dealer') as store_type"))
                ->when($request->has("dealer_id"), function ($q) use ($request) {
                    return $q->where("sub_dealer_id", $request->dealer_id);
                })
                /* filter by region */
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    return $QQQ->region($request->region_id);
                })

                /* filter by sub region */
                ->when($request->has("sub_region_id"), function ($QQQ) use ($request) {
                    return $QQQ->subRegion($request->sub_region_id);
                })
                ->when($request->has("name"), function ($q) use ($request) {
                    return $q->where("name", "like", "%" . $request->name . "%");
                })
                ->union($sub_dealers)
                ->get();

            return $this->response("00", "dealer sub dealer list", $dealers);
        } catch (\Throwable $th) {
            return $this->response("01", "failed dealer sub dealer list", $th->getMessage());
        }
    }

    public function syncStockDistributor(Request $request, $id)
    {
        try {
            $dealer = $this->dealer->query()->with([
                "distributorContractActive",
            ])
                ->whereHas("distributorContractActive")
                ->findOrFail($id);

            $distributor_product_during_contract = $this->sales_order_detail->query()
                ->whereHas("salesOrder", function ($QQQ) use ($dealer) {
                    return $QQQ
                        ->distributorSalesDuringContractBydate($dealer->id, $dealer->distributorContractActive->contract_start, $dealer->distributorContractActive->contract_end)
                        ->where(function ($QQQ) {
                            return $QQQ
                                ->consideredStatusDistributorSalesPending()
                                ->orWhere(function ($QQQ) {
                                    return $QQQ->consideredOrder();
                                });
                        });
                })
                ->whereHas("product")
                ->get()
                ->pluck("product_id")
                ->unique()
                ->values();

            $first_stock_distibutor = $this->adjustment_stock->query()
                ->where("is_first_stock", true)
                ->where("contract_id", $dealer->distributorContractActive->id)
                ->whereNotIn("product_id", $distributor_product_during_contract)
                ->whereHas("product")
                ->get()
                ->pluck("product_id")
                ->unique();

            $product_insufficient_stock = collect();

            $product_to_check = $distributor_product_during_contract->concat($first_stock_distibutor)
                ->values()
                ->unique()
                ->each(function ($product_id) use ($dealer, &$product_insufficient_stock) {

                    /* check current stock */
                    $current_stock = $this->distributorProductCurrentStockAdjusmentBased($dealer->id, $product_id);
                    $system_stock = $this->stockSystem($dealer->id, $product_id);

                    $product = $this->product->find($product_id);
                    if ($current_stock->current_stock > $system_stock->current_stock_system || $current_stock->current_stock < 0) {

                        $product = $this->product->findOrFail($product_id);
                        $product_insufficient_stock->push([
                            "product" => collect($product)->only(["id", "name", "size"]),
                            "current_stock" => $current_stock->current_stock,
                            "current_stock_system" => $system_stock->current_stock_system,
                        ]);

                        /**
                         * distributor product will suspended
                         */
                        DistributorSuspend::suspendDistributorProduct($dealer->id, $product_id);

                        /**
                         * distributor sales during contract will pending if confirmed
                         * or onhold if submited
                         */
                        DistributorSuspend::pendingOrderFromInsufficientStock($dealer->distributorContractActive, $dealer->id, $product_id);
                    } elseif ($current_stock->current_stock <= $system_stock->current_stock_system) {

                        /**
                         * revoke distributor from suspend, basically
                         * if stock was sufficient
                         */
                        DistributorRevoke::revokeDistributorProductSuspend($dealer->id, $product_id);

                        /**
                         * revoke distributor sales duting contract
                         * pending to confirmed and onhold to
                         * submited
                         */
                        DistributorRevoke::revokeOrderFromSufficientStock($dealer->distributorContractActive, $dealer->id, $product_id);
                    }
                });

            /**
             * if there has no product that sales by distributor or first stock
             * during contract, but there are product suspend
             * all product suspend for this distributor
             * will revoke without exception
             */
            $distributor_product_suspend = DistributorProductSuspended::query()
                ->with([
                    "product",
                ])
                ->whereHas("distributorSuspended", function ($QQQ) use ($dealer) {
                    return $QQQ->whereHas("distributor", function ($QQQ) use ($dealer) {
                        return $QQQ->where("id", $dealer->id);
                    });
                })
                ->get()
                ->each(function ($product_suspend) use ($dealer) {

                    /**
                     * revoke distributor from suspend, basically
                     * if stock was sufficient
                     */
                    DistributorRevoke::revokeDistributorProductSuspend($dealer->id, $product_suspend->product_id);
                });

            return $this->response("00", "Success sync distributor", [
                "product_insufficient_stock" => $product_insufficient_stock,
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to sync dealer", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    public function detail($id)
    {
        try {
            $dealer = $this->dealer->query()
                ->with([
                    'personel',
                    'personel.position',
                    'agencyLevel',
                    'adress_detail',
                    "subDealer",
                    'dealerBank',
                    'ownerBank',
                ])
                ->where('id', $id)
                ->withTrashed()
                ->firstOrFail();
            return $this->response('00', 'dealer detail', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display dealer detail', $th->getMessage());
        }
    }
}

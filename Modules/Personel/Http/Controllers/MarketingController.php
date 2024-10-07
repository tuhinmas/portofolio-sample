<?php

namespace Modules\Personel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ChildrenList;
use App\Traits\DistributorTrait;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use App\Traits\SupervisorCheck;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\ForeCast\Entities\ForeCast;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\Marketing;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Repositories\PersonelRepository;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Traits\SalesOrderTrait;

class MarketingController extends Controller
{
    use ResponseHandler, ChildrenList, SupervisorCheck, MarketingArea;
    use FeeMarketingTrait;
    use DistributorTrait;
    use SalesOrderTrait;

    public function __construct(
        LogMarketingFeeCounter $log_marketing_fee_counter,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        MarketingAreaDistrict $district,
        SalesOrder $sales_order,
        SubDealer $sub_dealer,
        SubRegion $sub_region,
        Marketing $marketing,
        ForeCast $fore_cast,
        DealerV2 $dealerv2,
        Invoice $invoice,
        Dealer $dealer,
        Region $region,
    ) {
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->sales_order = $sales_order;
        $this->sub_dealer = $sub_dealer;
        $this->sub_region = $sub_region;
        $this->marketing = $marketing;
        $this->fore_cast = $fore_cast;
        $this->dealerv2 = $dealerv2;
        $this->district = $district;
        $this->dealer = $dealer;
        $this->region = $region;
    }

    /**
     * list marketing
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        ini_set('max_execution_time', 500);
        $position_name = [
            "Aplikator",
            "Marketing Manager (MM)",
            "Marketing District Manager (MDM)",
            "Assistant MDM",
            "Regional Marketing Coordinator (RMC)",
            "Regional Marketing (RM)"
        ];

        $position_id = DB::table('positions')->whereNull("deleted_at")
            ->whereIn("name", $position_name)
            ->get()
            ->pluck("id");

        if ($request->has("personel_branch")) {
            unset($request["scope_supervisor"]);
        }
        try {
            $marketing = null;
            $personels_id = null;
            $personel_id = null;
            if (auth()->user()->hasAnyRole(is_all_data())) {
                $marketing = $this->marketing
                    ->with([
                        "position",
                        "areaAplicator.subRegionWithRegion"
                    ])
                    ->whereHas("position", function ($query) use ($position_id) {
                        return $query->whereIn("name", [
                            "Aplikator",
                            "Marketing Manager (MM)",
                            "Marketing District Manager (MDM)",
                            "Assistant MDM",
                            "Regional Marketing Coordinator (RMC)",
                            "Regional Marketing (RM)",
                        ]);
                    })
                    ->when($request->has("dealer_count"), function ($q) use ($request) {
                        return $q->withCount(['dealer' => function ($query) {
                            $query->whereNull("deleted_at");
                        }]);
                    })
                    ->when($request->has("position"), function ($qqq) use ($request) {
                        // $personel_id = $this->marketingListByAreaId($request->region_id);
                        return $qqq->whereIn("position_id", $request->position);
                    })
                    ->when($request->has("sub_dealer_count"), function ($q) use ($request) {
                        return $q->withCount(['subDealer']);
                    })
                    ->when($request->has("dealer_active"), function ($q) use ($request) {
                        return $q->withCount(['dealerv2 as dealer_active_count' => function ($query) {
                            $query->whereNull("deleted_at")->whereHas('salesOrders', function ($q) {
                                return $q
                                    ->where(function ($parameter) {
                                        return $parameter
                                            ->where("type", "1")
                                            ->whereHas("invoiceOnly", function ($QQQ) {
                                                return $QQQ->where("created_at", ">", Carbon::now()->subDays(365));
                                            });
                                    })
                                    ->orWhere(function ($parameter) {
                                        return $parameter
                                            ->where("type", "2")
                                            ->where("created_at", ">", Carbon::now()->subDays(365));
                                    });
                            })->orWhereDoesntHave('salesOrders')->where("created_at", ">", Carbon::now()->subDays(365));
                        }]);
                    })

                    ->when($request->dealer_inactive, function ($q) use ($request) {

                        return $q->withCount(['dealerv2 as dealer_inactive_count' => function ($query) {
                            $query
                                ->whereNull("deleted_at")
                                ->where(function ($QQQ) {
                                    return $QQQ
                                        ->whereHas('salesOrders', function ($QQQ) {
                                            return $QQQ
                                                ->where("created_at", "<=", Carbon::now()->subDays(365));
                                        })
                                        ->orWhereDoesntHave('salesOrders');
                                });
                        }]);
                    })

                    ->when($request->has("sub_dealer_active"), function ($q) use ($request) {
                        return $q->withCount(['subDealer as sub_dealer_active_count' => function ($query) {
                            $query->whereNull("deleted_at");
                        }]);
                    })

                    ->when($request->has("sub_dealer_inactive"), function ($q) use ($request) {
                        return $q->withCount(['subDealer as sub_dealer_inactive_count' => function ($query) {
                            $query->whereNotNull('deleted_at');
                        }]);
                    })
                    ->when($request->has("core_farmer_count"), function ($q) use ($request) {
                        return $q->withCount(['coreFarmerHasMany as core_farmer_count']);
                    })
                    ->when($request->has("kios_with_more_3_farmer"), function ($q) use ($request) {

                        return $q->withCount(['storeCoreFarmerMore3']);
                    })

                    /* filter by name */
                    ->when($request->has("name"), function ($q) use ($request) {
                        return $q->where("name", "like", "%" . $request->name . "%");
                    })

                    ->when($request->has("personel_id"), function ($q) use ($request) {
                        return $q->where("id", $request->personel_id);
                    })

                    /* filter by region */
                    ->when($request->has("region_id"), function ($qqq) use ($request) {
                        $personel_id = $this->marketingListByAreaId($request->region_id);
                        return $qqq->whereIn("id", $personel_id);
                    })

                    /* filter by subregion */
                    ->when($request->has("sub_region_id"), function ($qqq) use ($request) {
                        $personel_id = $this->marketingListByAreaId($request->sub_region_id);
                        return $qqq->whereIn("id", $personel_id);
                    })

                    ->when(!$request["all-personel"], function ($QQQ) {
                        return $QQQ->marketingHasArea();
                    })

                    /* scope list target marketing */
                    ->when($request->list_for_target === true, function ($QQQ) use ($request) {
                        return $QQQ->listOnTarget();
                    })

                    /* filter by status */
                    ->when($request->has("status"), function ($QQQ) use ($request) {
                        return $QQQ->whereIn("status", $request->status);
                    })

                    /* filter sales order by personel branch */
                    ->when($request->personel_branch, function ($QQQ) {
                        return $QQQ->PersonelBranch();
                    })

                    /* filter by name or join date */
                    ->when($request->has("join_date"), function ($QQQ) use ($request) {
                        return $QQQ->where(function ($QQQ) use ($request) {
                            return $QQQ
                                ->where("join_date", "like", "%" . $request->name_or_join_date . "%");
                        });
                    })

                    /* filter by name or region / sub region */
                    ->when($request->has("name_or_region_or_sub_region"), function ($QQQ) use ($request) {
                        return $QQQ->byNameRegionSubRegion($request->name_or_region_or_sub_region);
                    })

                    /* order by group rmc */
                    ->when($request->sort_by_sub_region_name, function ($QQQ) use ($request) {
                        $direction = $request->direction ? $request->direction : 'asc';
                        return $QQQ
                            ->withAggregate("groupRmcFromDistrict", "name")
                            ->withAggregate("subRegion", "name")
                            ->orderByRaw("if(group_rmc_from_district_name is not NULL, group_rmc_from_district_name, sub_region_name) {$direction}");
                    })

                    /* sort by group mdm */
                    ->when($request->sort_by_region_name, function ($QQQ) use ($request) {
                        $direction = $request->direction ? $request->direction : 'asc';
                        return $QQQ
                            ->withAggregate("groupMdmFromDistrict", "marketing_area_regions.name")
                            ->withAggregate("groupMdmFromSubRegion", "marketing_area_regions.name")
                            ->withAggregate("region", "name")
                            ->orderByRaw("if(group_mdm_from_district_marketing_area_regionsname is not NULL, group_mdm_from_district_marketing_area_regionsname, if(group_mdm_from_sub_region_marketing_area_regionsname is not null, group_mdm_from_sub_region_marketing_area_regionsname, region_name)) {$direction}");
                    })

                    ->when($request->sort_by_join_date, function ($QQQ) use ($request) {
                        $direction = $request->direction ? $request->direction : 'asc';
                        return $QQQ
                            ->orderBy("join_date", $direction);
                    })

                    ->when($request->sort_by_marketing_name, function ($QQQ) use ($request) {
                        $direction = $request->direction ? $request->direction : 'asc';
                        return $QQQ
                            ->orderBy("name", $direction);
                    })

                    ->when($request->sort_by_active_store, function ($QQQ) use ($request) {
                        $direction = $request->direction ? $request->direction : 'asc';
                        return $QQQ
                            ->orderBy("active_store", $direction);
                    })

                    ->when($request->has("kios_count"), function ($q) {
                        return $q->withCount(['storeTransferedandAccepted as kios_count']);
                    })
                    ->when($request->has("kios_count"), function ($q) {
                        return $q->with(['store']);
                    })
                    ->when($request->has("core_farmer_count"), function ($q) {
                        return $q->withCount(['coreFarmerHasMany as core_farmer_count']);
                    })
                    ->when($request->has("kios_with_more_3_farmer"), function ($q) use ($request) {
                        return $q->withCount(['storeCoreFarmerMore3']);
                    });

                if ($request->has("disabled_pagination")) {
                    $data_cek = $marketing->get();
                } else {
                    $data_cek = $marketing->get();


                    if ($request->total_sales_last_quartal) {
                        if ($request->direction == "asc") {
                            $data_cek = $data_cek->sortBy("total_sales_last_quartal");
                        } else {
                            $data_cek = $data_cek->sortByDesc("total_sales_last_quartal");
                        }
                    }

                    if ($request->total_sales_last_quartal) {
                        if ($request->direction == "asc") {
                            $data_cek = $data_cek->sortBy("total_sales_this_quartal");
                        } else {
                            $data_cek = $data_cek->sortByDesc("total_sales_this_quartal");
                        }
                    }
                    // dd($data_cek);

                    $currentPage = LengthAwarePaginator::resolveCurrentPage();
                    $pageLimit = $request->limit > 0 ? $request->limit : 15;

                    // slice the current page items
                    $currentItems = $data_cek->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

                    // you may not need the $path here but might be helpful..
                    $path = LengthAwarePaginator::resolveCurrentPath();

                    // Build the new paginator
                    $marketing = new LengthAwarePaginator($currentItems, count($data_cek), $pageLimit, $currentPage, ['path' => $path]);
                }
            } else {
                $personel_id = auth()->user()->personel_id;
                $personels_id = $this->getChildren($personel_id);
                $personel_area = $this->districtListMarketing($personel_id);
                $marketing = $this->marketing
                    ->with([
                        "position",
                        "areaAplicator.subRegionWithRegion"
                    ])
                    ->whereHas("position", function ($query) use ($position_id) {
                        return $query->whereIn("name", [
                            "Aplikator",
                            "Marketing Manager (MM)",
                            "Marketing District Manager (MDM)",
                            "Assistant MDM",
                            "Regional Marketing Coordinator (RMC)",
                            "Regional Marketing (RM)",
                        ]);
                    })
                    ->when($request->has("dealer_count"), function ($q) use ($request) {
                        return $q->withCount(['dealerv2 as dealer' => function ($query) {
                            $query->whereNull("deleted_at");
                        }]);
                    })
                    ->when($request->has("sub_dealer_count"), function ($q) use ($request) {
                        return $q->withCount(['subDealer']);
                    })
                    ->when($request->has("dealer_active"), function ($q) use ($request) {
                        return $q->withCount(['dealerv2 as dealer_active_count' => function ($query) {
                            $query->whereNull("deleted_at")->whereHas('salesOrders')->orWhereDoesntHave('salesOrders')->where("created_at", ">", Carbon::now()->subDays(365));
                        }]);
                    })
                    ->when($request->has("personel_id"), function ($q) use ($request) {
                        return $q->where("id", $request->personel_id);
                    })
                    ->when($request->has("position"), function ($qqq) use ($request) {
                        // $personel_id = $this->marketingListByAreaId($request->region_id);
                        return $qqq->whereIn("position_id", $request->position);
                    })

                    ->when($request->has("dealer_inactive"), function ($q) use ($request) {
                        return $q
                            ->withCount(['dealerv2 as dealer_inactive_count' => function ($query) {
                                $query
                                    ->whereNull("deleted_at")
                                    ->whereHas('salesOrders', function ($q) {
                                        return $q
                                            ->where(function ($parameter) {
                                                return $parameter
                                                    ->where("type", "1")
                                                    ->whereHas("invoiceOnly", function ($QQQ) {
                                                        return $QQQ->where("created_at", "<=", Carbon::now()->subDays(365));
                                                    });
                                            })
                                            ->orWhere(function ($parameter) {
                                                return $parameter
                                                    ->where("type", "2")
                                                    ->where("created_at", "<=", Carbon::now()->subDays(365));
                                            });
                                    })
                                    ->orWhereDoesntHave('salesOrders')->where("created_at", "<=", Carbon::now()->subDays(365));
                            }]);
                    })

                    ->when($request->has("sub_dealer_active"), function ($q) use ($request) {
                        return $q->withCount(['subDealer as sub_dealer_active_count' => function ($query) {
                            $query->whereNull("deleted_at");
                        }]);
                    })

                    ->when($request->has("sub_dealer_inactive"), function ($q) use ($request) {
                        return $q->withCount(['subDealer as sub_dealer_inactive_count' => function ($query) {
                            $query->whereNotNull('deleted_at');
                        }]);
                    })

                    ->when($request->has("kios_count"), function ($q) use ($request, $personel_area, $personel_id) {
                        return $q->withCount(['storeTransferedandAccepted as kios_count']);
                    })
                    ->when($request->has("kios_count"), function ($q) use ($request, $personel_area, $personel_id) {
                        return $q->with(['store']);
                    })
                    ->when($request->has("core_farmer_count"), function ($q) use ($request) {
                        return $q->withCount(['coreFarmerHasMany as core_farmer_count']);
                    })
                    ->when($request->has("kios_with_more_3_farmer"), function ($q) use ($request) {

                        return $q->withCount(['storeCoreFarmerMore3']);
                    })

                    ->whereIn("personels.id", $personels_id)

                    /* filter by name */
                    ->when($request->has("name"), function ($q) use ($request) {
                        return $q->where("name", "like", "%" . $request->name . "%");
                    })

                    /* filter by region */
                    ->when($request->has("region_id"), function ($qqq) use ($request) {
                        $personel_list = $this->marketingListByAreaId($request->region_id);
                        return $qqq->whereIn("id", $personel_list);
                    })

                    /* filter by subregion */
                    ->when($request->has("sub_region_id"), function ($qqq) use ($request) {
                        $personel_list = $this->marketingListByAreaId($request->sub_region_id);
                        return $qqq->whereIn("id", $personel_list);
                    })

                    /* scope list target marketing */
                    ->when($request->list_for_target === true, function ($QQQ) use ($request) {
                        return $QQQ->listOnTarget();
                    })

                    /* filter sales order by personel branch */
                    ->when($request->personel_branch, function ($QQQ) {
                        return $QQQ->PersonelBranch();
                    })

                    /* filter by status */
                    ->when($request->has("status"), function ($QQQ) use ($request) {
                        return $QQQ->whereIn("status", $request->status);
                    })

                    /* filter by name or join date */
                    ->when($request->has("name_or_join_date"), function ($QQQ) use ($request) {
                        return $QQQ->where(function ($QQQ) use ($request) {
                            return $QQQ
                                ->where("name", "like", "%" . $request->name_or_join_date . "%")
                                ->orWhere("join_date", "like", "%" . $request->name_or_join_date . "%");
                        });
                    })

                    /* order by group rmc */
                    ->when($request->sort_by_sub_region_name, function ($QQQ) use ($request) {
                        $direction = $request->direction ? $request->direction : 'asc';
                        return $QQQ
                            ->withAggregate("groupRmcFromDistrict", "name")
                            ->withAggregate("subRegion", "name")
                            ->orderByRaw("if(group_rmc_from_district_name is not NULL, group_rmc_from_district_name, sub_region_name) {$direction}");
                    })

                    /* sort by group mdm */
                    ->when($request->sort_by_region_name, function ($QQQ) use ($request) {
                        $direction = $request->direction ? $request->direction : 'asc';
                        return $QQQ
                            ->withAggregate("groupMdmFromDistrict", "marketing_area_regions.name")
                            ->withAggregate("groupMdmFromSubRegion", "marketing_area_regions.name")
                            ->withAggregate("region", "name")
                            ->orderByRaw("if(group_mdm_from_district_marketing_area_regionsname is not NULL, group_mdm_from_district_marketing_area_regionsname, if(group_mdm_from_sub_region_marketing_area_regionsname is not null, group_mdm_from_sub_region_marketing_area_regionsname, region_name)) {$direction}");
                    });

                if ($request->has("disabled_pagination")) {
                    $marketing = $marketing->get();
                } else {
                    $marketing = $marketing->paginate($request->limit > 0 ? $request->limit : 15);
                }
            }

            // foreach ($marketing as $data) {
            //     # code...
            // }

            return $this->response("00", "marketing index", $marketing);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing index", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $marketing = $this->marketing->findOrFail($id);
            $marketing = $this->marketing->query()
                ->where("id", $id)
                ->with([
                    "position",
                    "areaAplicator.subRegionWithRegion"
                ])
                ->first();

            $fee = $this->feePoint($id);
            $point = $this->marketingPoin($id);
            $marketing->fee_active = $fee["fee_active"];
            $marketing->fee_total = $fee["fee_total"];
            $marketing->point_active = $point["point_active"];
            $marketing->point_total = $point["point_total"];
            if (auth()->user()->hasAnyRole(
                // 'administrator',
                // 'super-admin',
                // 'Marketing Support',
                'Marketing District Manager (MDM)',
                'Regional Marketing Coordinator (RMC)',
                // 'Marketing Manager (MM)',
                // 'Sales Counter (SC)',
                // 'Operational Manager',
                // 'Support Bagian Distributor',
                // 'Support Distributor',
                // 'Support Bagian Kegiatan',
                // 'Support Kegiatan',
                // 'Support Supervisor',
                // 'Distribution Channel (DC)',
            )) {
                $personel_list = $this->getChildren($id);
                $personel_district = $this->district->query()
                    ->with("personel")
                    ->whereIn("personel_id", $personel_list)
                    ->pluck("personel_id");

                $personel_target_list = $this->marketing->query()
                    ->whereIn("id", $personel_district)
                    ->get();

                $target_supervisor = collect($personel_target_list)->sum("target");
                $marketing->target_as_supervisor = $target_supervisor;
            }

            return $this->response("00", "success to get marketing detail", $marketing);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing detail", $th->getMessage());
        }
    }

    public function feePoint($personel_id)
    {
        $sales_orders = $this->sales_order->query()
            ->with("sales_order_detail", "invoice")
            ->where("status", "confirmed")
            ->whereYear("created_at", Carbon::now())
            ->where("personel_id", $personel_id)
            ->get();

        $point_active = 0;
        $point_total = 0;
        $fee_active = 0;
        $fee_total = 0;

        foreach ($sales_orders as $sales_order) {
            $point_total += collect($sales_order->sales_order_detail)->sum("marketing_point");
            $fee_total += collect($sales_order->sales_order_detail)->sum("marketing_fee");
            $fee_total += collect($sales_order->sales_order_detail)->sum("marketing_fee_reguler");

            if ($sales_order->type == "2") {
                $point_active += collect($sales_order->sales_order_detail)->sum("marketing_point");
                $fee_active += collect($sales_order->sales_order_detail)->sum("marketing_fee");
                $fee_active += collect($sales_order->sales_order_detail)->sum("marketing_fee_reguler");
            }
            if ($sales_order->invoice) {
                if ($sales_order->invoice->payment_status == "settle") {
                    $point_active += collect($sales_order->sales_order_detail)->sum("marketing_point");
                    $fee_active += collect($sales_order->sales_order_detail)->sum("marketing_fee");
                    $fee_active += collect($sales_order->sales_order_detail)->sum("marketing_fee_reguler");
                }
            }
        }

        return [
            "point_active" => $point_active,
            "point_total" => $point_total,
            "fee_active" => $fee_active,
            "fee_total" => $fee_total,
        ];
    }

    public function marketingPoin($personel_id)
    {
        $point_marketing = PointMarketing::where("personel_id", $personel_id)->where("year", Carbon::now()->format('Y'))->first();

        return [
            "point_active" => $point_marketing ? $point_marketing->marketing_point_active : 0,
            "point_total" => $point_marketing ? $point_marketing->marketing_point_total : 0,
        ];
    }

    /**
     * detail marketing: marketing direct sales recap with dealer list
     *
     * @param Request $request
     * @return void
     */
    public function salesRecap(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "personel_id" => "required|max:255",
        ]);

        if ($validate->fails()) {
            return $this->response("04", "invalid data send", $validate->errors());
        }

        $quarter_first = Carbon::now()->subQuarter(3)->startOfQuarter();

        try {
            $dealers = null;
            if (auth()->user()->hasAnyRole(
                'administrator',
                'super-admin',
                'marketing staff',
                'Marketing Support',
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
            )) {
                $dealers = $this->dealerv2->query()
                    ->with([
                        'agencyLevel',
                        'adressDetail',
                        'distributorContractActive',
                        'lastContestByRegistrationDate',
                        'salesOrderOnly' => function ($QQQ) use ($quarter_first, $request) {
                            return $QQQ
                                ->with([
                                    "invoice" => function ($QQQ) use ($quarter_first, $request) {
                                        return $QQQ->with([
                                            "payment",
                                        ]);
                                    },
                                    "dealer" => function ($QQQ) {
                                        return $QQQ->with([
                                            "ditributorContract",
                                        ]);
                                    },
                                ])
                                ->whereHas("invoice")
                                ->where("type", "1")
                                ->consideredStatusConfirmedReturnedPending($quarter_first)
                                ->when($request->has("personel_id"), function ($q) use ($request) {
                                    return $q->where("personel_id", $request->personel_id);
                                });
                        },
                    ])

                    ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                        return $QQQ
                            ->whereHas("salesOrderOnly", function ($QQQ) use ($request) {
                                return $QQQ->where("personel_id", $request->personel_id);
                            });
                    })

                    /* filter customer id */
                    ->when($request->has("dealer_id"), function ($q) use ($request) {
                        return $q->where("dealer_id", $request->dealer_id);
                    })

                    /**
                     * filter distributor
                     * distributor is dealer have a contract bigger then date
                     */
                    ->when($request->by_distributor, function ($QQQ) use ($quarter_first) {
                        return $QQQ->distributorLastFourQuarter($quarter_first);
                    })

                    /**
                     * filter retailer
                     * retailer is dealer does not have active contract now or
                     * dealer does not have any contract in the last four quarters
                     */
                    ->when($request->by_retailer, function ($QQQ) use ($quarter_first) {
                        return $QQQ->retailerLastFourQuarter($quarter_first);
                    })

                    ->withTrashed()
                    ->get();
            } else {
                $personel_id = auth()->user()->personel_id;
                $personels_id = $this->getChildren($personel_id);
                $dealers = $this->dealerv2->query()
                    ->withTrashed()
                    ->with([
                        'agencyLevel',
                        'adressDetail',
                        'lastContestByRegistrationDate',
                        'salesOrderOnly' => function ($QQQ) use ($request, $personels_id) {
                            $quarter_first = Carbon::now()->subQuarter(3)->startOfQuarter();
                            return $QQQ
                                ->with([
                                    "invoice" => function ($QQQ) {
                                        return $QQQ->with([
                                            "payment",
                                        ]);
                                    },
                                    "invoice",
                                    "dealer" => function ($QQQ) {
                                        return $QQQ->with([
                                            "ditributorContract",
                                        ]);
                                    },
                                ])
                                ->whereHas("invoice")
                                ->where("type", "1")
                                ->consideredStatusConfirmedReturnedPending($quarter_first)
                                ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                                    return $QQQ->where("personel_id", $request->personel_id);
                                })

                                ->when(!$request->has("personel_id"), function ($QQQ) use ($personels_id) {
                                    return $QQQ->whereIn("personel_id", $personels_id);
                                });
                        },
                    ])

                    ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                        return $QQQ
                            ->whereHas("salesOrderOnly", function ($QQQ) use ($request) {
                                return $QQQ->where("personel_id", $request->personel_id);
                            });
                    })

                    ->when(!$request->has("personel_id"), function ($QQQ) use ($personels_id) {
                        return $QQQ
                            ->whereHas("salesOrderOnly", function ($QQQ) use ($personels_id) {
                                return $QQQ->whereIn("personel_id", $personels_id);
                            });
                    })

                    /* filter customer id */
                    ->when($request->has("dealer_id"), function ($q) use ($request) {
                        return $q->where("dealer_id", $request->dealer_id);
                    })

                    /**
                     * filter distributor
                     * distributor is dealer have a contract bigger then date
                     */
                    ->when($request->by_distributor, function ($QQQ) use ($quarter_first) {
                        return $QQQ->distributorLastFourQuarter($quarter_first);
                    })

                    /**
                     * filter retailer
                     * retailer is dealer does not have active contract now or
                     * dealer does not have any contract in the last four quarters
                     */
                    ->when($request->by_retailer, function ($QQQ) use ($quarter_first) {
                        return $QQQ->retailerLastFourQuarter($quarter_first);
                    })
                    ->get();
            }

            foreach ($dealers as $dealer) {

                /**
                 * direct recap per quarter
                 */
                $direct_base_quarter = collect($dealer->salesOrderOnly)
                    ->filter(function ($order) use ($request) {

                        /* check order is inside contract */
                        if ($request->by_distributor) {
                            if ($this->isOrderInsideDistributorContract($order)) {
                                return $order;
                            }
                        } else if ($request->by_retailer) {
                            if (!$this->isOrderInsideDistributorContract($order)) {
                                return $order;
                            }
                        } else {
                            return $order;
                        }
                    });

                $dealer->quarter_first = $quarter_first->format("Y-m-d");
                $direct_sale_total_amount_order_based_quarter = $direct_base_quarter
                    ->sum(function ($col) {

                        /* exclude ppn */
                        return $col->invoice->total;
                    });

                /* total PPN */
                $direct_sale_total_ppn_based_quarter = $direct_base_quarter->sum(function ($col) {
                    return $col->invoice->ppn;
                });

                $dealer->direct_sale_total_amount_order_based_quarter = $direct_sale_total_amount_order_based_quarter;
                $dealer->direct_sale_total_amount_order_based_quarter_include_ppn = $direct_sale_total_amount_order_based_quarter + $direct_sale_total_ppn_based_quarter;

                $dealer->count_direct_sale_order_based_quarter = $direct_base_quarter->count();

                $direct_sale_paid_amount_based_quarter = 0;
                if (count($direct_base_quarter) > 0) {
                    $direct_sale_paid_amount_based_quarter = $direct_base_quarter
                        ->map(function ($order, $key) {
                            $total_payment = 0;
                            if (collect($order->invoice->payment)->count() > 0) {
                                $total_payment = collect($order->invoice->payment)->sum("nominal");
                            }
                            return $total_payment;
                        })
                        ->sum();
                }

                $dealer->direct_sale_paid_amount_based_quarter = $direct_sale_paid_amount_based_quarter;

                /* unpaid amount include PPN */
                $dealer->direct_sale_unpaid_amount_based_quarter = $direct_sale_total_amount_order_based_quarter + $direct_sale_total_ppn_based_quarter - $direct_sale_paid_amount_based_quarter;
                $dealer->direct_sale_unpaid_amount_based_quarter_exclude_ppn = $direct_sale_total_amount_order_based_quarter - $direct_sale_paid_amount_based_quarter;
                $direct_sale_last_order = collect($dealer->salesOrderOnly)
                    ->sortByDesc(function ($Q) {
                        return $Q->invoice->created_at;
                    })
                    ->first();

                $dealer->direct_sale_last_order = $direct_sale_last_order ? Carbon::createFromFormat('Y-m-d H:i:s', $direct_sale_last_order->created_at, 'UTC')->setTimezone('Asia/Jakarta')->format("Y-m-d") : null;
                $dealer->point_contest = $dealer->lastContestByRegistrationDate ? $dealer->lastContestByRegistrationDate->redeemable_point : 0;
                $dealer = $dealer->unsetRelation("salesOrderOnly");
                $dealer = $dealer->unsetRelation("lastContestByRegistrationDate");
            }

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;

            // slice the current page items
            $currentItems = collect($dealers)->sortByDesc("direct_sale_last_order")->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $paginator = new LengthAwarePaginator($currentItems, count($dealers), $pageLimit, $currentPage, ['path' => $path]);

            return $this->response("00", "marketing sales recap", $paginator);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing sales recap", [
                "line" => $th->getLine(),
                "message" => $th->getMessage(),
                "file" => $th->getFile(),
            ]);
        }
    }

    /**
     * marketing recap per dealer per quartal
     *
     * @param Request $request
     * @param [type] $dealer_id
     * @return void
     */
    public function salesRecapPerdealerPerQuartal(Request $request, $store_id)
    {
        $dealer = Dealer::find($store_id);
        $sub_dealer = SubDealer::find($store_id);
        try {
            $quarter_first = Carbon::now()->subQuarters(3)->startOfQuarter();
            $sales_orders = $this->sales_order->query()
                ->with([
                    "invoice" => function ($QQQ) {
                        return $QQQ->with([
                            "payment",
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "distributorContract",
                            "ditributorContract",
                        ]);
                    },
                ])
                ->when($request->type, function ($QQQ) use ($request, $quarter_first) {
                    return $QQQ->where("type", $request->type);
                })
                ->consideredStatusConfirmedReturnedPending($quarter_first)
                ->where("personel_id", $request->personel_id ? $request->personel_id : ($dealer ? $dealer->personel_id : ($sub_dealer ? $sub_dealer->personel_id : null)))
                ->where("store_id", $store_id)
                ->get()
                ->map(function ($order) {
                    $order->quarter = confirmation_time($order)->quarter;
                    return $order;
                })
                ->filter(function ($order) use ($request) {

                    /* check order is inside contract */
                    if ($request->by_distributor) {
                        if ($this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else if ($request->by_retailer) {
                        if (!$this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else {
                        return $order;
                    }
                });

            $quartal_list = [];
            $quartal_detail = [
                "quartal" => "0",
                "year" => "0000",
                "total" => 0,
                "total_settle" => 0,
                "total_settle_before_maturity" => 0,
                "total_unsettle" => 0,
                "transaction_count" => 0,
                "transaction_count_settle" => 0,
                "transaction_count_settle_before_maturity" => 0,
                "transaction_count_unsettle" => 0,
                "store_id" => $store_id,
            ];

            for ($i = 0; $i < 4; $i++) {
                $quarter_first = Carbon::now()->subQuarter($i);
                $quartal_detail["year"] = $quarter_first->year;
                $quartal_detail["quartal"] = $quarter_first->quarter;
                array_push($quartal_list, $quartal_detail);
            }

            $quartal_list = collect($quartal_list)
                ->sortBy([
                    ["year", "asc"],
                    ["quartal", "asc"],
                ])
                ->values()
                ->all();

            $sales_order_recap = $sales_orders
                ->groupBy([
                    function ($order) {
                        return $order->quarter;
                    },
                ])
                ->map(function ($order_per_quartal, $quartal) {
                    $detail["total"] = $order_per_quartal->sum(function ($order) {
                        if ($order->type == "2") {
                            return $order->total;
                        }
                        return $order->invoice->total;
                    });

                    $detail["total_settle"] = $order_per_quartal
                        ->filter(fn ($order) => $this->isSettle($order))
                        ->sum(function ($order) {
                            if ($order->type == "2") {
                                return $order->total;
                            }
                            return $order->invoice->total;
                        });

                    $detail["total_settle_before_maturity"] = $order_per_quartal
                        ->filter(fn ($order) => $this->isSettleBeforeMaturity($order))
                        ->sum(function ($order) {
                            if ($order->type == "2") {
                                return $order->total;
                            }
                            return $order->invoice->total;
                        });

                    $detail["total_unsettle"] = $detail["total"] > 0 ? $detail["total"] - $detail["total_settle"] : 0;
                    $detail["transaction_count"] = $order_per_quartal->count();
                    $detail["transaction_count_settle"] = $order_per_quartal->filter(fn ($order) => $this->isSettle($order))->count();
                    $detail["transaction_count_settle_before_maturity"] = $order_per_quartal->filter(fn ($order) => $this->isSettleBeforeMaturity($order))->count();
                    $detail["transaction_count_unsettle"] = $detail["transaction_count"] > 0 ? $detail["transaction_count"] - $detail["transaction_count_settle"] : 0;

                    return $detail;
                });

            $quarter_recap = collect($quartal_list)->map(function ($recap) use ($sales_order_recap) {
                if (in_array($recap["quartal"], $sales_order_recap->keys()->toArray())) {
                    $recap = collect($recap)->merge($sales_order_recap[$recap["quartal"]]);
                }
                return $recap;
            });

            return $this->response("00", "sales order recap per dealer per quartal", $quarter_recap);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get sales recap per dealer per quartal", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    /**
     * indirect sale sub dealer list
     *
     * @param Request $request
     * @return void
     */
    public function indirectSalesRecapBasedQuarter(Request $request)
    {
        $quarter_first = Carbon::now()->subQuarter(3)->startOfQuarter();

        try {
            ini_set('max_execution_time', 500);
            $sub_dealers = $this->sub_dealer->query()
                ->withTrashed()
                ->with([
                    'adressDetail',
                    'salesOrderDealerSubDealer' => function ($QQQ) use ($quarter_first, $request) {
                        return $QQQ
                            ->with([
                                "dealer" => function ($QQQ) {
                                    return $QQQ->with([
                                        "ditributorContract",
                                    ]);
                                },
                            ])
                            ->where("type", "2")
                            ->consideredStatusConfirmedReturnedPending($quarter_first)
                            ->when($request->has("personel_id"), function ($q) use ($request) {
                                return $q->where("personel_id", $request->personel_id);
                            })
                            ->when(!$request->has("personel_id"), function ($QQQ) {
                                if (auth()->user()->hasAnyRole(
                                    'Support Bagian Distributor',
                                    'Distribution Channel (DC)',
                                    'Support Bagian Kegiatan',
                                    'Marketing Manager (MM)',
                                    'Operational Manager',
                                    'Support Distributor',
                                    'Sales Counter (SC)',
                                    'Support Supervisor',
                                    'Marketing Support',
                                    'Support Kegiatan',
                                    'administrator',
                                    'super-admin',
                                )) {
                                    return $QQQ;
                                } else {
                                    $personel_id = auth()->user()->personel_id;
                                    $personels_id = $this->getChildren($personel_id);
                                    return $QQQ->whereIn("personel_id", $personels_id);
                                }
                            });
                    },
                ])

                /* data support and supervisor */
                ->when(!$request->has("personel_id"), function ($QQQ) {
                    if (auth()->user()->hasAnyRole(
                        'Support Bagian Distributor',
                        'Distribution Channel (DC)',
                        'Support Bagian Kegiatan',
                        'Marketing Manager (MM)',
                        'Operational Manager',
                        'Support Distributor',
                        'Sales Counter (SC)',
                        'Support Supervisor',
                        'Marketing Support',
                        'Support Kegiatan',
                        'administrator',
                        'super-admin',
                    )) {
                        return $QQQ;
                    } else {
                        $personel_id = auth()->user()->personel_id;
                        $personels_id = $this->getChildren($personel_id);
                        return $QQQ->whereHas("salesOrderDealerSubDealer", function ($QQQ) use ($personels_id) {
                            return $QQQ->whereIn("personel_id", $personels_id);
                        });
                    }
                })

                /* filter peronel */
                ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("salesOrderDealerSubDealer", function ($QQQ) use ($request) {
                            return $QQQ->where("personel_id", $request->personel_id);
                        });
                })

                /* filter customer id */
                ->when($request->has("dealer_id"), function ($q) use ($request) {
                    return $q->where("sub_dealer_id", $request->dealer_id);
                })

                /**
                 * filter distributor
                 * distributor is dealer have a contract in te last four quarters
                 */
                ->when($request->by_distributor, function ($QQQ) use ($quarter_first) {
                    return $QQQ->distributorLastFourQuarter($quarter_first);
                })

                ->select("id", "personel_id", "prefix", "name", "sufix", "owner", "sub_dealer_id", "created_at", "sub_dealer_id as store_id", "telephone", DB::raw("if(sub_dealer_id, 'sub_dealer', 'sub_dealer') as store_type"));

            $dealers = $this->dealer->query()
                ->withTrashed()
                ->with([
                    'adressDetail',
                    'salesOrderDealerSubDealer' => function ($QQQ) use ($quarter_first, $request) {
                        return $QQQ
                            ->with([
                                "dealer" => function ($QQQ) {
                                    return $QQQ->with([
                                        "ditributorContract",
                                    ]);
                                },
                            ])
                            ->where("type", "2")
                            ->consideredStatusConfirmedReturnedPending($quarter_first)
                            ->when($request->has("personel_id"), function ($q) use ($request) {
                                return $q->where("personel_id", $request->personel_id);
                            })

                            /* support and spv */
                            ->when(!$request->has("personel_id"), function ($QQQ) {
                                if (auth()->user()->hasAnyRole(
                                    'administrator',
                                    'super-admin',
                                    'Marketing Support',
                                    'Marketing Manager (MM)',
                                    'Sales Counter (SC)',
                                    'Operational Manager',
                                    'Support Bagian Distributor',
                                    'Support Distributor',
                                    'Support Bagian Kegiatan',
                                    'Support Kegiatan',
                                    'Support Supervisor',
                                    'Distribution Channel (DC)'
                                )) {
                                    return $QQQ;
                                } else {
                                    $personel_id = auth()->user()->personel_id;
                                    $personels_id = $this->getChildren($personel_id);
                                    return $QQQ->whereHas("salesOrderDealerSubDealer", function ($QQQ) use ($personels_id) {
                                        return $QQQ->whereIn("personel_id", $personels_id);
                                    });
                                }
                            });
                    },
                ])

                /* data support and supervisor */
                ->when(!$request->has("personel_id"), function ($QQQ) {
                    if (auth()->user()->hasAnyRole(
                        'administrator',
                        'super-admin',
                        'Marketing Support',
                        'Marketing Manager (MM)',
                        'Sales Counter (SC)',
                        'Operational Manager',
                        'Support Bagian Distributor',
                        'Support Distributor',
                        'Support Bagian Kegiatan',
                        'Support Kegiatan',
                        'Support Supervisor',
                        'Distribution Channel (DC)'
                    )) {
                        return $QQQ;
                    } else {
                        $personel_id = auth()->user()->personel_id;
                        $personels_id = $this->getChildren($personel_id);
                        return $QQQ->whereHas("salesOrderDealerSubDealer", function ($QQQ) use ($personels_id) {
                            return $QQQ->whereIn("personel_id", $personels_id);
                        });
                    }
                })

                /* filter peronel */
                ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereHas("salesOrderDealerSubDealer", function ($QQQ) use ($request) {
                            return $QQQ->where("personel_id", $request->personel_id);
                        });
                })

                /* filter customer id */
                ->when($request->has("dealer_id"), function ($q) use ($request) {
                    return $q->where("dealer_id", $request->dealer_id);
                })

                /**
                 * filter distributor
                 * distributor is dealer have a contract in te last four quarters
                 */
                ->when($request->by_distributor, function ($QQQ) use ($quarter_first) {
                    return $QQQ->distributorLastFourQuarter($quarter_first);
                })

                /**
                 * filter retailer
                 * retailer is dealer does not have active contract now or
                 * dealer does not have any contract in the last four quarters
                 */
                ->when($request->by_retailer, function ($QQQ) use ($quarter_first) {
                    return $QQQ->retailerLastFourQuarter($quarter_first);
                })
                ->select("id", "personel_id", "prefix", "name", "sufix", "owner", "dealer_id", "created_at", "dealer_id as store_id", "telephone", DB::raw("if(dealer_id, 'dealer', 'dealer') as store_type"))
                ->union($sub_dealers)
                ->get();

            foreach ($dealers as $dealer) {

                /**
                 * indirect sale base quarter
                 **/
                $indirect_base_quarter = collect($dealer->salesOrderDealerSubDealer)
                    ->filter(function ($order) use ($request) {

                        /* check order is inside contract */
                        if ($request->by_distributor) {
                            if ($this->isOrderInsideDistributorContract($order)) {
                                return $order;
                            }
                        } else if ($request->by_retailer) {
                            if (!$this->isOrderInsideDistributorContract($order)) {
                                return $order;
                            }
                        } else {
                            return $order;
                        }
                    });

                $dealer->quarter_first = $quarter_first->format("Y-m-d");
                $dealer->indirect_sale_total_amount_order_based_quarter = collect($indirect_base_quarter)->sum("total");
                $dealer->count_indirect_sale_order_based_quarter = $indirect_base_quarter->count();
                $last_order_indirect_sales = $indirect_base_quarter->sortByDesc("date")->first();
                $dealer->last_order_indirect_sales = $last_order_indirect_sales ? Carbon::createFromFormat('Y-m-d H:i:s', $last_order_indirect_sales->date, 'UTC')->setTimezone('Asia/Jakarta') : null;

                $dealer = $dealer->unsetRelation("salesOrderDealerSubDealer");
            }

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;

            // slice the current page items
            $currentItems = collect($dealers)->sortByDesc("last_order_indirect_sales")->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $paginator = new LengthAwarePaginator($currentItems, count($dealers), $pageLimit, $currentPage, ['path' => $path]);

            return $this->response("00", "indirect sales marketing sales recap", $paginator);
        } catch (\Throwable $th) {
            return $this->response("01", "indirect sales marketing sales recap", $th->getMessage());
        }
    }

    /**
     * group sales order by month and year
     * @param [type] $store_id, $date
     * @return void
     */
    public function productSalesByMarketing(Request $request, $personel_id = null)
    {
        $fiveYearsAgo = Carbon::now()->subYears(5);
        $dealer_personel = [];
        $dealer_id = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->get()->pluck("id")->toArray();
        $sub_dealer_id = DB::table('sub_dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->get()->pluck("id")->toArray();

        if (count($dealer_id) > 0) {
            $dealer_personel = array_merge($dealer_personel, $dealer_id);
        } else if (count($sub_dealer_id) > 0) {
            $dealer_personel = array_merge($dealer_personel, $sub_dealer_id);
        }

        try {
            $sales_orders = $this->sales_order

                /* filter by personel id */
                ->when($request->has("personel_id"), function ($q) use ($request, $dealer_personel) {
                    return $q
                        ->where("personel_id", $request->personel_id);
                    /* pending */
                    // ->whereIn("store_id", $dealer_personel);
                })
                ->consideredOrderFromYear($fiveYearsAgo)
                ->consideredOrder()
                ->with("invoice", "sales_order_detail")
                ->get();

            $sales_order_id = $sales_orders->pluck("id")->toArray();
            $data = $this->sales_order_detail
                ->with([
                    "product" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                    "sales_order" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice",
                        ]);
                    },
                    "salesOrder",
                ])
                ->whereIn('sales_order_id', $sales_order_id)
                ->leftJoin('products', 'product_id', '=', 'products.id')
                ->select("sales_order_details.*", "products.id")
                ->get()
                ->groupBy([
                    function ($val) {
                        return $val->id;
                    },
                    function ($val) {
                        return confirmation_time($val->sales_order)->format('Y');
                    },
                ]);

            $report = [];
            $year_list = [];
            $test = [];
            $year_list["product"] = null;
            $year_list["total"] = null;

            $year = CarbonPeriod::create(Carbon::now()->subYears(4)->format('Y-m-d H:i:s'), '1 years', Carbon::now()->format('Y-m-d H:i:s'));
            $year = collect($year)->map(function ($value) {
                return Carbon::createFromFormat('Y-m-d H:i:s', $value)->format('Y');
            });

            foreach ($year as $value) {
                $year_list[$value] = 0;
            }

            $detail_settle = $year_list;
            $year_list[Carbon::now()->format("Y") . "_lunas"] = 0;
            $year_list[Carbon::now()->format("Y") . "_point_lunas"] = 0;
            $year_list[Carbon::now()->format("Y") . "_point_total"] = 0;

            unset($detail_settle["product"]);
            $year_list["settle"] = $detail_settle;
            $year_list["unsettle"] = $detail_settle;

            $paid_this_year = Carbon::now()->year . "_lunas";
            foreach ($data as $product => $year) {

                $total_qty = 0;
                $total_settle = 0;
                $total_unsettle = 0;
                $test[$product] = $year_list;
                $point_lunas = 0;
                $point_total = 0;

                foreach ($year as $product_on_year => $val) {

                    $test[$product]["product"] = $val[0]->product;

                    if ($product_on_year <= Carbon::now()->subYears(4)->format('Y')) {
                        continue;
                    }

                    $direct_sum = 0;
                    $quantity = 0;
                    $quantity_settle = 0;
                    $quantity_unsettle = 0;
                    foreach ($val as $detail) {
                        if ($detail->sales_order->type == "2") {
                            $total_qty += ($detail->quantity - $detail->returned_quantity);
                            $quantity += ($detail->quantity - $detail->returned_quantity);
                            $total_settle += ($detail->quantity - $detail->returned_quantity);

                            /* qty settle */
                            $quantity_settle += ($detail->quantity - $detail->returned_quantity);

                            /* point_lunas */
                            $point_lunas += $detail->marketing_point;

                            if ((confirmation_time($detail->sales_order) ? confirmation_time($detail->sales_order)->format("Y") : Carbon::parse($detail->sales_order->created_at)->format("Y")) == Carbon::now()->year) {
                                $direct_sum += ($detail->quantity - $detail->returned_quantity);
                            }
                        } else {
                            $total_qty += ($detail->quantity - $detail->returned_quantity);
                            $quantity += ($detail->quantity - $detail->returned_quantity);
                            if ($detail->sales_order->invoice) {
                                if ($detail->sales_order->invoice->payment_status == "settle") {
                                    if (Carbon::parse($detail->sales_order->invoice->created_at)->format("Y") == Carbon::now()->year) {
                                        $direct_sum += ($detail->quantity - $detail->returned_quantity);
                                    }

                                    /* point_lunas */
                                    $point_lunas += $detail->marketing_point;

                                    /* qty settle */
                                    $quantity_settle += ($detail->quantity - $detail->returned_quantity);
                                } else {

                                    /* qty unsettle */
                                    $quantity_unsettle += ($detail->quantity - $detail->returned_quantity);

                                    /* total unsettle */
                                    $total_unsettle += ($detail->quantity - $detail->returned_quantity);
                                }
                            }
                        }

                        /* marketing point total */
                        if (in_array($detail->sales_order->status, ["confirmed", "pending", "returned"])) {
                            $point_total += $detail->marketing_point;
                        }
                    }

                    $test[$product][$product_on_year] = $quantity;
                    $test[$product]["total"] = $total_qty;
                    $test[$product][$paid_this_year] = $direct_sum;
                    $test[$product][Carbon::now()->format("Y") . "_point_lunas"] = $point_lunas;
                    $test[$product][Carbon::now()->format("Y") . "_point_total"] = $point_total;

                    $test[$product]["settle"][$product_on_year] = $quantity_settle;
                    $test[$product]["settle"]["total"] = $total_settle;
                    $test[$product]["unsettle"][$product_on_year] = $quantity_unsettle;
                    $test[$product]["unsettle"]["total"] = $total_unsettle;
                }
            }
            $report = collect($test)->sortBy('total')->reverse()->toArray();
            if ($request->sort_by_total_product && $request->has("direction") && $request->has("year")) {
                if ($request->direction == "asc") {
                    $report = collect($report)->sortBy($request->year)->toArray();
                } else {
                    $report = collect($report)->sortByDesc($request->year)->toArray();
                }
            } elseif ($request->sort_by_total_product && $request->has("year")) {
                $report = collect($report)->sortBy($request->year)->toArray();
            } elseif ($request->sort_by_total_product && $request->has("direction") && empty($request->year)) {
                if ($request->direction == "asc") {
                    $report = collect($report)->sortBy("total")->toArray();
                } else {
                    $report = collect($report)->sortByDesc("total")->toArray();
                }
            }

            return $this->response("00", "product pick up recap on 5 years", $report);
        } catch (\Throwable $th) {
            return $this->response("01", "failed tp recap product distribution", $th->getMessage());
        }
    }

    /**
     * group sales order by month and year
     * product distribution per product
     * @param [type] $store_id, $date
     * @return void
     */
    public function productDistributionPerProductOnStore(Request $request, $personel_id = null)
    {
        $validator = Validator::make($request->all(), [
            "personel_id" => "required",
            "product_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        $fiveYearsAgo = Carbon::now()->subYears(5);
        $dealer_personel = [];
        $dealer_id = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->get()->pluck("id")->toArray();
        $sub_dealer_id = DB::table('sub_dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->get()->pluck("id")->toArray();
        if (count($dealer_id) > 0) {
            $dealer_personel = array_merge($dealer_personel, $dealer_id);
        } else if (count($sub_dealer_id) > 0) {
            $dealer_personel = array_merge($dealer_personel, $sub_dealer_id);
        }
        try {
            $sales_orders = $this->sales_order
                ->with([
                    "invoice",
                    "dealer",
                    "subDealer",
                    "sales_order_detail",
                ])

                /* filter by personel id */
                ->when($request->has("personel_id"), function ($q) use ($request, $dealer_personel) {
                    return $q
                        ->where("personel_id", $request->personel_id);
                })
                ->consideredOrderFromYear($fiveYearsAgo)
                ->whereHas("sales_order_detail", function ($q) use ($request) {
                    $q->where("product_id", $request->product_id);
                })
                ->get();

            $sales_order_id = $sales_orders->pluck("id")->toArray();
            $sales_order_grouped = $sales_orders
                ->groupBy([
                    function ($val) {
                        return $val->store_id;
                    },
                    function ($val) {
                        return confirmation_time($val)->format('Y');
                    },
                ]);
            $report = [];
            $year_list = [];
            $test = [];
            $year_list["store"] = null;
            $year_list["total"] = null;

            for ($i = 4; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->format("Y");
                $year_list[Carbon::now()->subYears($i)->format("Y")] = 0;
            }

            foreach ($sales_order_grouped as $product => $year) {
                $test[$product] = $year_list;
                $new_year_list = $year_list;
                unset($new_year_list["store"]);
                $test[$product]["unsettle"] = $new_year_list;
                $test[$product]["settle"] = $new_year_list;
                $total_qty = 0;
                $total_settle = 0;
                $total_unsettle = 0;
                foreach ($year as $product_on_year => $val) {

                    if ($val[0]->dealer) {
                        $dealer = [
                            "id" => $val[0]->dealer->id,
                            "prefix" => $val[0]->dealer->prefix,
                            "name" => $val[0]->dealer->name,
                            "sufix" => $val[0]->dealer->sufix,
                            "dealer_id" => $val[0]->dealer->dealer_id,
                        ];

                        $test[$product]["store"] = $dealer;
                    } else {
                        if ($val[0]->subDealer) {
                            $sub_dealer = [
                                "id" => $val[0]->subDealer->id,
                                "prefix" => $val[0]->subDealer->prefix,
                                "name" => $val[0]->subDealer->name,
                                "sufix" => $val[0]->subDealer->sufix,
                                "sub_dealer_id" => $val[0]->subDealer->sub_dealer_id,
                            ];
                            $test[$product]["store"] = $sub_dealer;
                        }
                    }

                    $quantity = 0;
                    $quantity_settle = 0;
                    $quantity_unsettle = 0;
                    foreach ($val as $order) {
                        $total_qty += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));
                        $quantity += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));

                        if ($order->type == "2") {
                            $total_settle += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));
                            $quantity_settle += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));
                        } else {
                            if ($order->invoice) {
                                if ($order->invoice->payment_status == "settle") {
                                    $total_settle += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));
                                    $quantity_settle += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));
                                } else {
                                    $total_unsettle += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));
                                    $quantity_unsettle += (collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("quantity") - collect($order->sales_order_detail)->where("product_id", $request->product_id)->sum("returned_quantity"));
                                }
                            }
                        }
                    }

                    $test[$product][$product_on_year] = $quantity;
                    $test[$product]["settle"][$product_on_year] = $quantity_settle;
                    $test[$product]["unsettle"][$product_on_year] = $quantity_unsettle;

                    $test[$product]["total"] = $total_qty;
                    $test[$product]["settle"]["total"] = $total_settle;
                    $test[$product]["unsettle"]["total"] = $total_unsettle;
                }
            }

            $test = collect($test)->sortBy('total')->reverse()->toArray();
            return $this->response("00", "product pick up recap on 5 yearssss", $test);
        } catch (\Throwable $th) {
            return $this->response("01", "failed tp recap product pick up", $th->getMessage());
        }
    }

    /**
     * product distribution per store
     * @param [type] $store_id, $date
     * @return void
     */
    public function productDistributionByStore(Request $request)
    {
        try {
            $data = $this->sales_order
                ->whereIn('store_id', [$request->store_id])
                // ->whereYear("created_at", $request->year)
                ->when($request->has("year"), function ($QQQ) use ($request) {
                    return $QQQ
                        ->where(function ($QQQ) use ($request) {
                            return $QQQ
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) use ($request) {
                                    return $QQQ->whereYear("created_at", $request->year);
                                });
                        })
                        ->orWhere(function ($QQQ) use ($request) {
                            return $QQQ
                                ->where("type", "2")
                                ->whereYear("created_at", $request->year);
                        });
                })
                ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                    return $QQQ->where("personel_id", $request->personel_id);
                })
                ->where('status', "confirmed")
                ->get()
                ->pluck("id")
                ->toArray();

            $sales_order_detail = $this->sales_order_detail
                ->with(
                    "product",
                    "sales_order.invoice"
                )
                ->whereIn('sales_order_id', $data)
                ->leftJoin('products', 'product_id', '=', 'products.id')
                ->select("sales_order_details.*", "products.id as product_id")
                ->get();

            $direct = $sales_order_detail->where("sales_order.type", "1")->groupBy([
                function ($val) {
                    return $val->product_id;
                },
                function ($val) {
                    return $val->sales_order->invoice->created_at->format('M');
                },
            ]);

            $indirect = $sales_order_detail->where("type", "2")->groupBy([
                function ($val) {
                    return $val->product_id;
                },
                function ($val) {
                    return confirmation_time($val->sales_order) ? confirmation_time($val->sales_order)->format('M') : $val->sales_order->created_at->format('M');
                },
            ]);

            $report = [];
            $year_list = [];
            $test = [];
            $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];
            $year_list["product"] = null;
            $year_list["total"] = null;
            $settle_order = [];
            $unsettle_order = [];
            /* direct sale */
            foreach ($direct as $product => $months) {
                $total = 0;
                $total_settle = 0;
                $total_unsettle = 0;
                $test[$product] = $month;
                $test[$product]["total"] = 0;
                $test[$product]["settle"] = $month;
                $test[$product]["unsettle"] = $month;
                foreach ($months as $product_on_month => $val) {
                    $test[$product]["product"] = $val[0]->product;
                    $total_qty = 0;
                    $total_qty_settle = 0;
                    $total_qty_unsettle = 0;

                    $test[$product]["settle"][$product_on_month] = collect($val)->where("sales_order.invoice.payment_status", "settle")->sum("quantity");
                    $test[$product]["unsettle"][$product_on_month] = collect($val)->where("sales_order.invoice.payment_status", "!=", "settle")->sum("quantity");
                    $test[$product][$product_on_month] = collect($val)->sum("quantity");
                    $total += collect($val)->sum("quantity");
                    $total_settle += $test[$product]["settle"][$product_on_month];
                    $total_unsettle += $test[$product]["unsettle"][$product_on_month];
                }

                $test[$product]["total"] = $total;
                $test[$product]["settle"]["total"] = $total_settle;
                $test[$product]["unsettle"]["total"] = $total_unsettle;
            }

            /* indirect sale */
            foreach ($indirect as $product => $months) {
                $total = 0;
                $total_settle = 0;
                $total_unsettle = 0;
                foreach ($months as $product_on_month => $val) {
                    $total_qty = 0;
                    $total_qty_settle = 0;
                    $total_qty_unsettle = 0;

                    $test[$product]["settle"][$product_on_month] = collect($val)->sum("quantity");
                    $test[$product][$product_on_month] += collect($val)->sum("quantity");
                    $total += collect($val)->sum("quantity");
                    $total_settle += $test[$product]["settle"][$product_on_month];
                }

                $test[$product]["total"] += $total;
                $test[$product]["settle"]["total"] += $total_settle;
            }

            $test = collect($test)->sortBy('total')->reverse()->toArray();

            return $this->response("00", "product pick up recap on by store per year", $test);
        } catch (\Throwable $th) {
            return $this->response("01", "failed tp recap product pick up", $th->getMessage());
        }
    }

    /**
     * marketing sales five years group by year
     *
     * @param Request $request
     * @return void
     */
    public function marketingSalesGrafikFiveYear(Request $request)
    {
        try {
            $fiveYearsAgo = Carbon::now()->subYears(5);
            $dealer_personel = [];
            $dealer_id = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->get()->pluck("id");
            if ($dealer_id) {
                $dealer_personel = $dealer_id;
            }
            $sales_orders = $this->sales_order->query()
                // ->whereYear("created_at", ">", $fiveYearsAgo)
                ->where(function ($QQQ) use ($fiveYearsAgo) {
                    return $QQQ
                        ->where(function ($QQQ) use ($fiveYearsAgo) {
                            return $QQQ
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) use ($fiveYearsAgo) {
                                    return $QQQ->whereYear("created_at", ">", $fiveYearsAgo);
                                });
                        })
                        ->orWhere(function ($QQQ) use ($fiveYearsAgo) {
                            return $QQQ
                                ->where("type", "2")
                                ->whereYear("created_at", ">", $fiveYearsAgo);
                        });
                })
                // ->whereYear("created_at", "<=", Carbon::now())
                ->where('status', "confirmed")
                ->whereHas("invoice")
                ->with("invoice")

                /* filter by personel id */
                ->when($request->has("personel_id"), function ($q) use ($request, $dealer_personel) {
                    return $q
                        ->where("personel_id", $request->personel_id)
                        ->whereIn("store_id", $dealer_personel);
                })

                /* filter by type */
                ->when($request->has("type"), function ($q) use ($request) {
                    return $q->whereIn("type", $request->type);
                })

                ->select("sales_orders.*", DB::raw("QUARTER(sales_orders.created_at) as quarter"))
                ->get();

            $direct = $sales_orders->where("type", "1")->groupBy([
                function ($val) {
                    return confirmation_time($val)->format('Y');
                },
                function ($val) {
                    return confirmation_time($val)->format('M');
                },
            ]);

            $indirect = $sales_orders->where("type", "2")->groupBy([
                function ($val) {
                    return confirmation_time($val)->format('Y');
                },
                function ($val) {
                    return confirmation_time($val)->format('M');
                },
            ]);

            $report = [];
            $month = ['Jan' => 0, 'Feb' => 0, 'Mar' => 0, 'Apr' => 0, 'May' => 0, 'Jun' => 0, 'Jul' => 0, 'Aug' => 0, 'Sep' => 0, 'Oct' => 0, 'Nov' => 0, 'Dec' => 0];

            for ($i = 4; $i >= 0; $i--) {
                $report[Carbon::now()->subYears($i)->format("Y")] = $month;
                $report[Carbon::now()->subYears($i)->format("Y")]["total"] = 0;
            }

            /* direct sale */
            foreach ($direct as $year => $month) {
                $total_per_year = 0;
                foreach ($month as $order_on_month => $val) {
                    $total_amount = 0;
                    $report[$year][$order_on_month] = collect($val)->sum("invoice.total");
                    $total_per_year += $report[$year][$order_on_month];
                }
                $report[$year]["total"] = $total_per_year;
            }

            /* indirect sale */
            foreach ($indirect as $year => $month) {
                $total_per_year = 0;
                foreach ($month as $order_on_month => $val) {
                    $total_amount = 0;
                    $report[$year][$order_on_month] += collect($val)->sum("total");
                    $total_per_year += $report[$year][$order_on_month];
                }
                $report[$year]["total"] += $total_per_year;
            }

            return $this->response("00", "marketing sales order recap per dealer per quartal", $report);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing sales recap per dealer per quartal", $th->getMessage());
        }
    }

    /**
     * graphic sales recap this year and last year
     *
     * @param Request $request
     * @return void
     */
    public function marketingSalesGrafikPerQuartal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "first_year" => "required_with:first_quartal",
            "second_year" => "required_with:second_quartal",
            "first_year" => "required_with:second_year",
            "personel_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        $first_year = Carbon::now()->year;
        $second_year = Carbon::now()->year - 1;

        if ($request->has("first_year")) {
            $first_year = $request->first_year;
        }
        if ($request->has("second_year")) {
            $second_year = $request->second_year;
        }

        try {
            $sales_orders = $this->sales_order->query()
                ->with("invoice")
                ->where("status", "confirmed")
                ->where(function ($qqq) use ($second_year, $first_year, $request) {
                    return $qqq
                        ->when($request->has("first_year"), function ($q) use ($request) {
                            /* first_quartal mean selected quartal on first year */
                            $q->when($request->has("first_quartal"), function ($q) use ($request) {
                                return $q
                                    ->where(function ($QQQ) use ($request) {
                                        return $QQQ
                                            ->where("type", "1")
                                            ->whereHas("invoice", function ($QQQ) use ($request) {
                                                return $QQQ
                                                    ->whereYear("created_at", $request->first_year)
                                                    ->whereRaw("quarter(invoices.created_at) = " . $request->first_quartal);
                                            });
                                    })
                                    ->orWhere(function ($QQQ) use ($request) {
                                        return $QQQ
                                            ->where("type", "2")
                                            ->whereRaw("quarter(sales_orders.created_at) = " . $request->first_quartal)
                                            ->whereYear("created_at", $request->first_year);
                                    });
                            });

                            return $q
                                ->where(function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->where("type", "1")
                                        ->whereHas("invoice", function ($QQQ) use ($request) {
                                            return $QQQ->whereYear("created_at", $request->first_year);
                                        });
                                })
                                ->orWhere(function ($QQQ) use ($request) {
                                    return $QQQ
                                        ->where("type", "2")
                                        ->whereYear("created_at", $request->first_year);
                                });
                        })

                        ->when($request->has("second_year"), function ($q) use ($request) {

                            /* second_quartal mean selected quartal on second year */
                            $q->when($request->has("second_quartal"), function ($q) use ($request) {
                                return $q->orWhereYear("created_at", $request->second_year)
                                    ->where("personel_id", $request->personel_id)
                                    ->whereRaw("quarter(sales_orders.created_at) = " . $request->second_quartal);
                            });

                            $q->when(!$request->has("second_quartal"), function ($q) use ($request) {
                                return $q->orWhereYear("created_at", $request->second_year);
                            });
                        })

                        ->when(!$request->has("first_year"), function ($q) use ($first_year, $second_year) {
                            return $q
                                ->where(function ($QQQ) use ($first_year, $second_year) {
                                    return $QQQ
                                        ->where("type", "1")
                                        ->whereHas("invoice", function ($QQQ) use ($first_year, $second_year) {
                                            return $QQQ
                                                ->whereYear("created_at", $first_year)
                                                ->orWhereYear("created_at", $second_year);
                                        });
                                })
                                ->orWhere(function ($QQQ) use ($first_year, $second_year) {
                                    return $QQQ
                                        ->where("type", "2")
                                        ->whereYear("created_at", $first_year)
                                        ->orWhereYear("created_at", $second_year);
                                });
                        });
                })
                ->where("personel_id", $request->personel_id)
                ->get();

            $attribute = [
                [
                    "year" => $request->has("first_year") ? $request->first_year : Carbon::now()->year,
                    "quartal" => $request->has("first_quartal") ? $request->first_quartal : "-",
                    "total" => "0",
                ],
                [
                    "year" => $request->has("second_year") ? $request->second_year : $first_year - 1,
                    "quartal" => $request->has("second_quartal") ? $request->second_quartal : "-",
                    "total" => "0",
                ],
            ];

            $direct = $sales_orders->where("type", "1")->groupBy([
                function ($val) {
                    return confirmation_time($val)->format('Y');
                },
            ]);

            $indirect = $sales_orders->where("type", "2")->groupBy([
                function ($val) {
                    return confirmation_time($val)->format('Y');
                },
            ]);

            /* indirect recap */
            foreach ($indirect as $year => $orders) {
                $total_amount = 0;
                $total_amount = collect($orders)->sum("total");

                foreach ($attribute as $idx => $attr) {
                    if ($attribute[$idx]["year"] == $year) {
                        $attribute[$idx]["total"] = $total_amount;
                    }
                }
            }

            /* direct recap */
            foreach ($direct as $year => $orders) {
                $total_amount = 0;
                $total_amount = collect($orders)->sum(function ($Q) {
                    return $Q->invoice->total;
                });

                foreach ($attribute as $idx => $attr) {
                    if ($attribute[$idx]["year"] == $year) {
                        $attribute[$idx]["total"] += $total_amount;
                    }
                }
            }

            $xxx = [
                "test" => $attribute,
            ];
            return $this->response("00", "marketing sales per quartal", $attribute);
        } catch (\Throwable $th) {
            return $this->response("00", "marketing sales per quartal", $th->getMessage());
        }
    }

    /**
     * graphic sales recap per sub region
     * settle or unsettle
     *
     * @return void
     */
    public function marketingSalesRecapPerSubRegionPerMarketing(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $sales_orders = null;
        $sales_orders_grouped = null;
        $data = [];
        $data_per_marketing = [];
        $sub_region = null;
        $region = null;
        $has_request = false;

        /* get all personel on sub ergion */
        $personels_id = null;
        if ($request->has("sub_region_id")) {
            unset($request->region_id);
            $personels_id = $this->personelListByArea($request->sub_region_id);
        } elseif ($request->has("region_id")) {
            $personels_id = $this->personelListByArea($request->region_id);
            unset($request->sub_region_id);
        } else {
            $personels_id = $this->personelListByArea();
            $has_request = true;
        }

        try {
            if (auth()->user()->hasAnyRole(is_all_data())) {
                $sales_orders = $this->sales_order->query()
                    ->with([
                        "personel" => function ($QQQ) {
                            return $QQQ->with([
                                "position" => function ($Q) {
                                    return $Q->select("id", "name");
                                },
                                "areaMarketing" => function ($Q) {
                                    return $Q->with([
                                        "subRegionWithRegion" => function ($Q) {
                                            return $Q->with([
                                                "region",
                                            ]);
                                        },
                                    ]);
                                },
                            ]);
                        },
                        "invoice",
                        "sales_order_detail",
                        "dealer" => function ($QQQ) {
                            return $QQQ->with([
                                "ditributorContract",
                            ]);
                        },
                    ])
                    ->considerOrderStatusForRecap()

                    /* filter sub region */
                    ->when($request->has("sub_region_id"), function ($qqq) use ($personels_id) {
                        return $qqq->whereIn("personel_id", $personels_id);
                    })

                    /* filter region */
                    ->when($request->has("region_id"), function ($qqq) use ($personels_id) {
                        return $qqq->whereIn("personel_id", $personels_id);
                    })

                    /* if therehas no filter */
                    ->when($has_request == true, function ($QQQ) use ($personels_id) {
                        return $QQQ->whereIn("personel_id", $personels_id);
                    })
                    ->when($request->has("name"), function ($qqq) use ($request) {
                        return $qqq->whereHas("personel", function ($QQQ) use ($request) {
                            return $QQQ->where("name", "like", "%" . $request->name . "%");
                        });
                    })
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($QQQ) {
                                        return $QQQ->whereBetween("created_at", [Carbon::now()->subMonths(3)->startOfMonth(), Carbon::now()]);
                                    });
                            })
                            ->orWhere(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "2")
                                    ->indirectSalesBetweenDate(Carbon::now()->subMonths(3)->startOfMonth()->format("Y-m-d"), Carbon::now()->format("Y-m-d"));
                            });
                    })
                    ->orderBy("created_at")
                    ->get();
            } else {
                $sales_orders = $this->sales_order->query()
                    ->with([
                        "personel" => function ($QQQ) {
                            return $QQQ->with([
                                "position" => function ($Q) {
                                    return $Q->select("id", "name");
                                },
                                "areaMarketing" => function ($Q) {
                                    return $Q->with([
                                        "subRegionWithRegion" => function ($Q) {
                                            return $Q->with([
                                                "region",
                                            ]);
                                        },
                                    ]);
                                },
                            ]);
                        },
                        "invoice",
                        "sales_order_detail",
                        "dealer" => function ($QQQ) {
                            return $QQQ->with([
                                "ditributorContract",
                            ]);
                        },
                    ])
                    ->considerOrderStatusForRecap()

                    /* filter sub region */
                    ->when($request->has("sub_region_id"), function ($qqq) use ($personels_id) {
                        return $qqq->whereIn("personel_id", $personels_id);
                    })

                    /* filter region */
                    ->when($request->has("region_id"), function ($qqq) use ($personels_id) {
                        return $qqq
                            ->whereIn("personel_id", $personels_id);
                    })

                    /* if there has no filter */
                    ->when($has_request, function ($QQQ) use ($personels_id) {
                        $personels_id = $this->getChildren(auth()->user()->personel_id);
                        return $QQQ->whereIn("personel_id", $personels_id);
                    })

                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($QQQ) {
                                        return $QQQ
                                            ->whereBetween("created_at", [Carbon::now()->subMonths(3)->startOfMonth(), Carbon::now()]);
                                    });
                            })
                            ->orWhere(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "2")
                                    ->indirectSalesBetweenDate(Carbon::now()->subMonths(3)->startOfMonth()->format("Y-m-d"), Carbon::now()->format("Y-m-d"));
                            });
                    })
                    ->get();
            }

            $detail = [
                "month" => 0,
                "indirect" => 0,
                "direct" => 0,
                "total" => 0,
            ];

            for ($i = 0; $i < 4; $i++) {
                $detail["month"] = Carbon::now()->startOfMonth()->subMonth($i)->format("M");
                $data[Carbon::now()->startOfMonth()->subMonth($i)->format("M")] = $detail;
            }

            if (!$sales_orders) {
                return $this->response("00", "success but there is no data found", $data);
            }

            $sales_orders = $sales_orders
                ->filter(function ($order) use ($request) {

                    if (!$request->order_type || count($request->order_type) == 0) {
                        return $order;
                    }

                    /* filter distributor */
                    if (in_array(1, $request->order_type)) {
                        if ($this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    }

                    /* filter retailer */
                    if (in_array(2, $request->order_type)) {
                        if (!$this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    }
                });

            $sales_orders_grouped = null;
            if ($request->has("sub_region_id")) {
                $sales_orders_grouped = $sales_orders->groupBy([
                    function ($val) {
                        return $val->personel_id;
                    },
                    function ($order) {
                        return confirmation_time($order)->format('M');
                    },
                ]);

                foreach ($sales_orders_grouped as $marketing => $months) {
                    $data_per_marketing[$marketing]["data"] = $data;
                    foreach ($months as $month => $order) {
                        $total_direct = 0;
                        $total_indirect = 0;
                        foreach ($order as $key => $ord) {
                            if ($ord->type == "2") {
                                $total_indirect += $ord->total;
                            } else {
                                if ($ord->invoice) {
                                    if ($ord->invoice->payment_status == "settle") {
                                        $total_direct += $ord->invoice->total;
                                    } else {
                                        $total_direct += $ord->invoice->total;
                                    }
                                }
                            }

                            $join_date = $ord->personel->join_date;
                            $resign_date = $ord->personel->resign_date;

                            $data_per_marketing[$marketing]["status"] = $join_date <= Carbon::now() && (is_null($resign_date) || $resign_date >= Carbon::now()) ? 'Active' : "Not Active";
                            $data_per_marketing[$marketing]["personel"] = $ord->personel;
                        }

                        $data_per_marketing[$marketing]["data"][$month]["region"] = $ord->personel->areaMarketing->subRegionWithRegion->region->name;
                        $data_per_marketing[$marketing]["data"][$month]["indirect"] = $total_indirect;
                        $data_per_marketing[$marketing]["data"][$month]["direct"] = $total_direct;
                        $data_per_marketing[$marketing]["data"][$month]["total"] = $total_direct + $total_indirect;
                    }
                }
            } elseif ($request->has("region_id")) {
                $sales_orders_grouped = $sales_orders->groupBy([
                    function ($val) {
                        return $val->personel->areaMarketing->subRegionWithRegion->id;
                    },
                    function ($order) {
                        return confirmation_time($order)->format('M');
                    },
                ]);

                foreach ($sales_orders_grouped as $sub_region => $months) {
                    $data_per_marketing[$sub_region]["data"] = $data;
                    foreach ($months as $month => $order) {
                        $total_direct = 0;
                        $total_indirect = 0;
                        foreach ($order as $key => $ord) {
                            if ($ord->type == "2") {
                                $total_indirect += $ord->total;
                            } else {
                                if ($ord->invoice) {
                                    if ($ord->invoice->payment_status == "settle") {
                                        $total_direct += $ord->invoice->total;
                                    } else {
                                        $total_direct += $ord->invoice->total;
                                    }
                                }
                            }

                            $join_date = $ord->personel->join_date;
                            $resign_date = $ord->personel->resign_date;
                            $data_per_marketing[$sub_region]["status"] = $join_date <= Carbon::now() && (is_null($resign_date) || $resign_date >= Carbon::now()) ? 'Active' : "Not Active";
                            $data_per_marketing[$sub_region]["sub_region"] = $ord->personel->areaMarketing->subRegionWithRegion;
                        }
                        $data_per_marketing[$sub_region]["data"][$month]["indirect"] = $total_indirect;
                        $data_per_marketing[$sub_region]["data"][$month]["direct"] = $total_direct;
                        $data_per_marketing[$sub_region]["data"][$month]["total"] = $total_direct + $total_indirect;
                    }
                }
            } else {
                $sales_orders_grouped = $sales_orders->groupBy([
                    function ($val) {
                        return $val->personel_id;
                    },
                    function ($val) {
                        return confirmation_time($val)->format('M');
                    },
                ]);

                foreach ($sales_orders_grouped as $region => $months) {
                    $data_per_marketing[$region]["data"] = $data;
                    foreach ($months as $month => $order) {
                        $total_direct = 0;
                        $total_indirect = 0;
                        foreach ($order as $key => $ord) {
                            if ($ord->type == "2") {
                                $total_indirect += $ord->total;
                            } else {
                                if ($ord->invoice) {
                                    if ($ord->invoice->payment_status == "settle") {
                                        $total_direct += $ord->invoice->total;
                                    } else {
                                        $total_direct += $ord->invoice->total;
                                    }
                                }
                            }
                            $join_date = $ord->personel->join_date;
                            $resign_date = $ord->personel->resign_date;

                            $data_per_marketing[$region]["status"] = personel_status_converter($ord->personel->status);
                            $data_per_marketing[$region]["personel"] = $ord->personel;
                            $data_per_marketing[$region]["region"] = $ord->personel->areaMarketing->subRegionWithRegion->region;
                        }
                        $data_per_marketing[$region]["data"][$month]["indirect"] = $total_indirect;
                        $data_per_marketing[$region]["data"][$month]["direct"] = $total_direct;
                        $data_per_marketing[$region]["data"][$month]["total"] = $total_direct + $total_indirect;
                    }
                }
            }

            /**
             * marketing that does not have purchase in last
             * four month still displayed with zero value
             */
            $marketing_doesnt_have_sales = Personel::query()
                ->with([
                    "position" => function ($Q) {
                        return $Q->select("id", "name");
                    },
                    "areaMarketing" => function ($Q) {
                        return $Q->with([
                            "subRegionWithRegion" => function ($Q) {
                                return $Q->with([
                                    "region",
                                ]);
                            },
                        ]);
                    },
                ])
                ->whereIn("id", $personels_id)
                ->whereNotIn("id", collect($data_per_marketing)->keys())
                ->get()
                ->groupBy("id")
                ->map(function ($marketing) {
                    $recap_month = [];
                    for ($i = 0; $i < 4; $i++) {
                        $recap_month[Carbon::now()->subMonths($i)->startOfMonth()->format("M")] = [
                            "month" => Carbon::now()->subMonths($i)->startOfMonth()->format("M"),
                            "indirect" => 0,
                            "direct" => 0,
                            "total" => 0,
                        ];
                    }

                    $recap["data"] = $recap_month;
                    $recap["status"] = personel_status_converter($marketing->first()->status);
                    $recap["personel"] = $marketing->first();
                    return $recap;
                });

            if ($marketing_doesnt_have_sales->count() > 0) {
                if (count($data_per_marketing) > 0) {
                    $data_per_marketing = collect($data_per_marketing)->merge($marketing_doesnt_have_sales)->toArray();
                } else {
                    $data_per_marketing = $marketing_doesnt_have_sales;
                }
            }
            return $this->response("00", "success to get marketing sales recap per sub region per marketing 4 months ago", $data_per_marketing);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing sales recap per sub region per marketing 4 months ago", $th->getMessage());
        }
    }

    public function marketingSalesRecapPerSubRegionPerMarketingFourMonth(Request $request)
    {
        $sales_orders = null;
        $sales_orders_grouped = null;
        $data = [];
        $data_per_marketing = [];
        $sub_region = null;
        $region = null;
        $has_request = false;

        /* get all personel on sub ergion */
        $personels_id = null;
        if ($request->has("region_id")) {
            $personels_id = $this->personelListByArea($request->region_id);
            unset($request->sub_region_id);
        } else {
            $personels_id = $this->personelListByArea();
            $has_request = true;
        }

        // return $personels_id;
        try {
            if (auth()->user()->hasAnyRole('administrator', 'super-admin', 'Marketing Manager (MM)', "Marketing Support")) {
                $sales_orders = $this->sales_order->query()
                    ->with([
                        "personel" => function ($QQQ) {
                            return $QQQ->with([
                                "areaMarketing" => function ($Q) {
                                    return $Q->with([
                                        "subRegionWithRegion" => function ($Q) {
                                            return $Q->with([
                                                "region",
                                            ]);
                                        },
                                    ]);
                                },
                            ]);
                        },
                        "invoice",
                        "sales_order_detail",
                    ])
                    ->whereIn("status", ["confirmed", "returned", "pending"])

                    /* filter sub region */
                    ->when($request->has("sub_region_id"), function ($qqq) use ($personels_id) {
                        return $qqq->whereIn("personel_id", $personels_id);
                    })

                    /* filter region */
                    ->when($request->has("region_id"), function ($qqq) use ($personels_id) {
                        return $qqq
                            ->whereIn("personel_id", $personels_id);
                    })

                    ->when($request->has("name"), function ($qqq) use ($request) {
                        return $qqq->whereHas("personel", function ($QQQ) use ($request) {
                            return $QQQ->where("name", "like", "%" . $request->name . "%");
                        });
                    })

                    /* if therehas no filter */
                    ->when($has_request == true, function ($QQQ) use ($personels_id) {
                        return $QQQ->whereIn("personel_id", $personels_id);
                    })

                    ->filterDirectIndirectDistributorRetailer($request->has("order_type") ? $request->order_type : [1, 2, 3, 4])

                    ->whereBetween("created_at", [Carbon::now()->subMonths(3)->startOfMonth(), Carbon::now()])
                    ->get();
            } else {
                $sales_orders = $this->sales_order->query()
                    ->with([
                        "personel" => function ($QQQ) {
                            return $QQQ->with([
                                "areaMarketing" => function ($Q) {
                                    return $Q->with([
                                        "subRegionWithRegion" => function ($Q) {
                                            return $Q->with([
                                                "region",
                                            ]);
                                        },
                                    ]);
                                },
                            ]);
                        },
                        "invoice",
                        "sales_order_detail",
                    ])
                    ->whereIn("status", ["confirmed", "returned", "pending"])

                    /* filter region */
                    ->when($request->has("region_id"), function ($qqq) use ($personels_id) {
                        return $qqq
                            ->whereIn("personel_id", $personels_id);
                    })

                    ->when($request->has("name"), function ($qqq) use ($request) {
                        return $qqq->whereHas("personel", function ($QQQ) use ($request) {
                            return $QQQ->where("name", "like", "%" . $request->name . "%");
                        });
                    })

                    /* if therehas no filter */
                    ->when($has_request, function ($QQQ) use ($personels_id) {
                        $personels_id = $this->getChildren(auth()->user()->personel_id);
                        return $QQQ->whereIn("personel_id", $personels_id);
                    })

                    ->filterDirectIndirectDistributorRetailer($request->has("order_type") ? $request->order_type : [1, 2, 3, 4])

                    ->whereBetween("created_at", [Carbon::now()->subMonths(3)->startOfMonth(), Carbon::now()])
                    ->get();
            }

            $detail = [
                "month" => 0,
                "indirect" => 0,
                "direct" => 0,
                "total" => 0,
            ];

            for ($i = 0; $i < 4; $i++) {
                $detail["month"] = Carbon::now()->subMonth($i)->format("M");
                $data[Carbon::now()->subMonth($i)->format("M")] = $detail;
            }

            if (!$sales_orders) {
                return $this->response("00", "success but there is no data found", $data);
            }

            $sales_orders_grouped = null;
            $sales_orders_grouped = $sales_orders->groupBy([
                function ($val) {
                    return $val->personel_id;
                },
                function ($val) {
                    return confirmation_time($val)->format('M');
                },
            ]);

            foreach ($sales_orders_grouped as $marketing => $months) {
                $data_per_marketing[$marketing]["data"] = $data;
                foreach ($months as $month => $order) {
                    $total_direct = 0;
                    $total_indirect = 0;
                    foreach ($order as $key => $ord) {
                        if ($ord->type == "2") {
                            $total_indirect += $ord->total;
                        } else {
                            if ($ord->invoice) {
                                $total_direct += $ord->invoice->total;
                            }
                        }
                        if ($total_direct != 0 && $total_direct != 0) {
                            $data_per_marketing[$marketing]["personel"] = $ord->personel;
                        }
                    }

                    if ($total_direct != 0 && $total_direct != 0) {
                        $data_per_marketing[$marketing]["data"][$month]["region"] = $ord->personel->areaMarketing->subRegionWithRegion->region->name;
                        $data_per_marketing[$marketing]["data"][$month]["indirect"] = $total_indirect;
                        $data_per_marketing[$marketing]["data"][$month]["direct"] = $total_direct;
                        $data_per_marketing[$marketing]["data"][$month]["total"] = $total_direct + $total_indirect;
                    }
                }
            }

            $xxx = [
                "data" => $sales_orders,
                "fix" => $data_per_marketing,
                "test" => $data,
                "grouped" => $sales_orders_grouped,
            ];
            return $this->response("00", "success to get marketing sales recap per region per marketing 4 months ago", $data_per_marketing);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing sales recap per region per marketing 4 months ago", $th->getMessage());
        }
    }

    public function marketingSalesRecapPerSubRegionPerStoresFourMonth(Request $request)
    {
        $sales_orders = null;
        $sales_orders_grouped = null;
        $data = [];


        // return $personels_id;
        try {
            $sales_orders = $this->sales_order->query()
                ->with([
                    'dealer.regionHasOne.subRegionWithRegion',
                    'subDealer.regionHasOne.subRegionWithRegion',
                    "personel",
                    "invoice",
                    "sales_order_detail"
                ])
                ->whereIn("status", ["confirmed", "returned", "pending"])

                /* if therehas no filter */
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    $area = $request->region_id ?: $request->sub_region_id;
                    $area_id = $this->districtListByAreaId($area);
                    // dd($area_id);
                    return $QQQ->where(function ($query) use ($area_id) {
                        return $query->whereHas("dealer", function ($query) use ($area_id) {
                            return $query->whereHas("adressDetail",function($query) use ($area_id){
                                return $query->whereIn("district_id", $area_id)->where("type","dealer");
                            });
                        })->orWhereHas('subDealer', function ($query) use ($area_id) {
                            return $query->whereHas("adressDetail",function($query) use ($area_id){
                                return $query->whereIn("district_id", $area_id)->where("type","sub_dealer");
                            });
                        });
                    });
                })

                ->when($request->has("sub_region_id"), function ($QQQ) use ($request) {
                    $area = $request->sub_region_id;
                    $area_id = $this->districtListByAreaId($area);
                    // dd($area_id);
                    return $QQQ->where(function ($query) use ($area_id) {
                        return $query->whereHas("dealer", function ($query) use ($area_id) {
                            return $query->whereHas("adressDetail",function($query) use ($area_id){
                                return $query->whereIn("district_id", $area_id)->where("type","dealer");
                            });
                        })->orWhereHas('subDealer', function ($query) use ($area_id) {
                            return $query->whereHas("adressDetail",function($query) use ($area_id){
                                return $query->whereIn("district_id", $area_id)->where("type","sub_dealer");
                            });
                        });
                    });
                })

                ->filterDirectIndirectDistributorRetailer($request->has("order_type") ? $request->order_type : [1, 2, 3, 4])
                ->whereBetween("created_at", [Carbon::now()->subMonths(3)->startOfMonth(), Carbon::now()])
                ->get();

            $detail = [
                'month' => 0,
                'indirect' => 0,
                'direct' => 0,
                'total' => 0,
            ];

            $data = [];


            for ($i = 0; $i < 4; $i++) {
                $month = Carbon::now()->subMonth($i);
                $data[$month->format('M')] = $detail;
            }

            $sales_orders_grouped = $sales_orders->groupBy(['store_id', function ($val) {
                return confirmation_time($val)->format('M');
            }]);

            $data_per_stores = [];

            // $sales_orders->groupBy(['store_id'])->map(function($data, $key) use (&$data_per_stores){
            //     $latest_order = $data->sortByDesc("created_at")->first();
            //     // return $latest_order;
            //     $data_per_stores[$key]['latest_transaction'] = [
            //                 'indirect' => $latest_order->type == '2' ? $latest_order->total : 0,
            //                 'direct' => $latest_order->type != '2' && $latest_order->invoice ? $latest_order->invoice->total : 0,
            //                 'total' => $latest_order->type == '2' ? $latest_order->total : ($latest_order->invoice ? $latest_order->invoice->total : 0),
            //     ];
            // });

            foreach ($sales_orders_grouped as $stores => $months) {
                $data_per_stores[$stores]['data'] = $data;
                foreach ($months as $month => $order) {
                    $total_direct = 0;
                    $total_indirect = 0;
                    foreach ($order as $ord) {

                        $data_per_stores[$stores]['store'] = $ord->dealer?->name ?: $ord->subDealer?->name;
                        $data_per_stores[$stores]['store_id'] = $ord->dealer?->dealer_id ?: $ord->subDealer?->sub_dealer_id;
                        $data_per_stores[$stores]['region_name'] =  $ord->dealer?->regionHasOne?->subRegionWithRegion->region->name ?: $ord->subDealer?->regionHasOne?->subRegionWithRegion->region->name;
                        $data_per_stores[$stores]['sub_region_name'] = $ord->dealer?->regionHasOne?->subRegionWithRegion->name ?: $ord->subDealer?->regionHasOne?->subRegionWithRegion->name;
                        $data_per_stores[$stores]['district_id'] = $ord->dealer?->regionHasOne?->district_id ?: $ord->subDealer?->regionHasOne?->district_id;
                        
                        $data_per_stores[$stores]['marketing_name'] = $ord->personel->name;
                        if ($ord->type == '2') {
                            $total_indirect += $ord->total;
                        } else {
                            if ($ord->invoice) {
                                $total_direct += $ord->invoice->total;
                            }
                        }
                    }

                    if ($total_direct != 0 || $total_indirect != 0) {
                        $data_per_stores[$stores]['data'][$month]['indirect'] = $total_indirect;
                        $data_per_stores[$stores]['data'][$month]['direct'] = $total_direct;
                        $data_per_stores[$stores]['data'][$month]['total'] = $total_direct + $total_indirect;
                    }
                }
            }
            return $this->response("00", "success to get marketing sales recap per region per stores 4 months ago", $data_per_stores);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing sales recap per region per marketing 4 months ago", $th->getMessage());
        }
    }

    public function marketingSalesRecapPerYearPerMonth(Request $request)
    {
        try {
            if (auth()->user()->hasAnyRole('administrator', 'super-admin', 'Marketing Manager (MM)', "Marketing Support")) {
                $sales_orders = $this->sales_order->query()
                    ->with([
                        "invoice",
                        "sales_order_detail",
                    ])
                    ->where("status", "confirmed")

                    /* filter by year */
                    ->when($request->has("year"), function ($QQQ) use ($request) {
                        return $QQQ->whereYear("created_at", $request->year);
                    })

                    /* filter by year */
                    ->when(!$request->has("year"), function ($QQQ) {
                        return $QQQ->whereYear("created_at", Carbon::now());
                    })

                    ->get();
            } else {
                $sales_orders = $this->sales_order->query()
                    ->with([
                        "invoice",
                        "sales_order_detail",
                    ])
                    ->where("status", "confirmed")

                    /* filter by year */
                    ->when($request->has("year"), function ($QQQ) use ($request) {
                        return $QQQ->whereYear("created_at", $request->year);
                    })

                    /* filter by year */
                    ->when(!$request->has("year"), function ($QQQ) {
                        return $QQQ->whereYear("created_at", Carbon::now());
                    })
                    ->get();
            }

            $detail = [
                "indirect" => 0,
                "direct" => 0,
            ];

            $months = [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ];

            $data_recap = [];
            foreach ($months as $month) {
                $data_recap[$month] = $detail;
            }

            if (!$sales_orders) {
                return $this->response("00", "success but there is no data found", $data);
            }

            $sales_orders_grouped = null;
            $sales_orders_grouped = $sales_orders->groupBy([
                function ($val) {
                    return confirmation_time($val)->format('M');
                },
            ]);

            foreach ($sales_orders_grouped as $months => $sales) {
                $total_direct = 0;
                $total_indirect = 0;
                foreach ($sales as $sale) {
                    if ($sale->type == "2") {
                        $total_indirect += $sale->total;
                    } else {
                        if ($sale->invoice) {
                            if ($sale->invoice->payment_status == "settle") {
                                $total_direct += $sale->invoice->total;
                            }
                        }
                    }
                }
                // return $months;
                $data_recap[$months]["indirect"] = $total_indirect;
                $data_recap[$months]["direct"] = $total_direct;
                $data_recap[$months]["total"] = $total_direct + $total_indirect;
            }

            $xxx = [
                "data" => $sales_orders,
                "test" => $data_recap,
                "grouped" => $sales_orders_grouped,
            ];
            return $this->response("00", "success to get marketing sales recap per year per month", $data_recap);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing sales recap per year per month", $th->getMessage());
        }
    }

    /**
     *
     *
     * @param Request $request
     * @return void
     */
    public function marketingAchievementRecapFiveYearsPerQuartal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "personel_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        $personel = $this->marketing->findOrFail($request->personel_id);
        $target = $personel->target;
        $dealer_personel = [];

        $dealer_id = DB::table('dealers')->whereNull("deleted_at")->where("personel_id", $request->personel_id)->get()->pluck("id");

        if ($dealer_id) {
            $dealer_personel = $dealer_id;
        }

        try {
            $fiveYearsAgo = Carbon::now()->subYears(3)->startOfYear();

            $achievment = $this->sales_order->query()
                ->with([
                    "invoice",
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                ])
                /* filter by personel id */
                ->when($request->has("personel_id"), function ($q) use ($request, $dealer_personel) {
                    return $q->where("personel_id", $request->personel_id);
                })
                ->consideredOrderFromYear($fiveYearsAgo)
                ->get()
                ->sortBy(function ($order) {
                    if ($order->type == "2") {
                        return $order->date;
                    } else {
                        return $order->invoice->created_at;
                    }
                })
                ->filter(function ($order) use ($request) {

                    /* check order is inside contract */
                    if ($request->by_distributor) {
                        if ($this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else if ($request->by_retailer) {
                        if (!$this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else {
                        return $order;
                    }
                })
                ->groupBy([
                    function ($val) {
                        return confirmation_time($val)->format('Y');
                    },
                    function ($val) {
                        if ($val->type == "2") {
                            return "Q" . Carbon::parse($val->date)->quarter;
                        } else {
                            return "Q" . $val->invoice->created_at->quarter;
                        }
                    },
                ])

                /* sum achievement */
                ->map(function ($order_per_year, $year) {
                    return collect($order_per_year)->map(function ($order_per_quartal, $quartal) {
                        return $order_per_quartal->sum(function ($order) {
                            if ($order->type == "2") {
                                return $order->total;
                            } else {
                                return $order->invoice->total;
                            }
                        });
                    });
                });

            $year_list = [];
            $detail = [
                "Q1" => 0,
                "Q2" => 0,
                "Q3" => 0,
                "Q4" => 0,
                "target" => $target,
                "total" => 0,
                "percentage" => 0,
            ];

            for ($i = 4; $i >= 0; $i--) {
                $year_list[Carbon::now()->subYears($i)->format("Y")] = $detail;
            }

            $recap_marketing = collect($year_list)
                ->map(function ($recap, $year) use (&$achievment) {
                    if (in_array($year, $achievment->keys()->toArray())) {
                        $recap = collect($recap)->merge($achievment[$year]);
                        $recap["total"] = $recap->except("toal", "target", "percentage")->sum();
                        $recap["percentage"] = $recap["target"] > 0 && $recap["total"] > 0 ? $recap["total"] / $recap["target"] * 100 : 0;
                    }
                    return $recap;
                });

            return $this->response("00", "succes to get marketing achievement recap", $recap_marketing);
        } catch (\Throwable $th) {
            return $this->responseAsJson("01", "failed to get marketing fee recap", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ], 500);
        }
    }

    /**
     * marketing recap fee on graphic
     *
     * @param Request $request
     * @return void
     */
    public function marketingFeeRecapPerQuartal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "personel_id" => "required",
            "year" => "required_with:quartal",
            "quartal" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        $personel = $this->marketing->findOrFail($request->personel_id);
        $personel_target = $personel->target;
        $year = $request->year ? $request->year : now()->format("Y");
        $quartal = $request->quartal ? $request->quartal : now()->quarter;

        $quarter_month = [
            "1" => [
                "Jan", "Feb", "Mar",
            ],
            "2" => [
                "Apr", "May", "Jun",
            ],
            "3" => [
                "Jul", "Aug", "Sep",
            ],
            "4" => [
                "Oct", "Nov", "Dec",
            ],

        ];

        $detail = [
            "month" => 0,
            "fore_cast" => 0,
            "target" => 0,
            "achievement" => 0,
            "achievement_settle" => 0,
            "progress" => 0,
            "color" => "FF6262",
            "fee_reguler" => [
                "settle" => 0,
                "unsettle" => 0,
                "total" => 0,
            ],
        ];

        try {

            /**
             * achievement
             */
            $achievement = $this->sales_order->query()
                ->with([
                    "feeSharing",
                    "invoice",
                    "sales_order_detail" => function ($Q) {
                        return $Q->with([
                            "product" => function ($Q) {
                                return $Q->with([
                                    "feeProduct" => function ($Q) {
                                        return $Q->where("year", Carbon::now()->format("Y"));
                                    },
                                ]);
                            },
                        ]);
                    },
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                ])

                /* filter by personel id */
                ->when($request->has("personel_id"), function ($q) use ($request) {
                    return $q->where("personel_id", $request->personel_id);
                })
                ->quartalOrder($year, $quartal, ($request->by_distributor ? "distributor" : ($request->by_retailer ? "retailer" : null)))
                ->get()
                ->sortBy(function ($order) {
                    if ($order->type == "2") {
                        return $order->date;
                    } else {
                        return $order->invoice->created_at;
                    }
                })

                /* filter sales to distriutor and retailer */
                ->filter(function ($order) use ($request) {

                    /* check order is inside contract */
                    if ($request->by_distributor) {
                        if ($this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else if ($request->by_retailer) {
                        if (!$this->isOrderInsideDistributorContract($order)) {
                            return $order;
                        }
                    } else {
                        return $order;
                    }
                })

                ->map(function ($order) {
                    $order->quarter = confirmation_time($order)->quarter;
                    return $order;
                });

            /* personel forecast */
            $personel_fore_cast = ForeCast::query()
                ->where("personel_id", $request->personel_id)
                ->whereYear("date", $request->year)
                ->whereRaw("QUARTER(date) = ?", $request->quartal)
                ->get()
                ->groupBy([
                    function ($val) {
                        return confirmation_time($val)->format('M');
                    },
                ])
                ->map(function ($forecast) {
                    return $forecast->sum("nominal");
                });

            /**
             * FeeMarketingTrait
             */
            $fee_sharing_marketing_total = $this->feeMarketingRegulerTotalQuery($request->personel_id, $year, $quartal)
                ->groupBy([
                    function ($fee) {
                        return Carbon::parse($fee->confirmed_at)->format("M");
                    },
                ])
                ->map(function ($origin_per_month, $month) {

                    /* calculate fee total per month */
                    return $this->feeMarketingRegulerTotalDataMapping($origin_per_month);
                });

            /**
             * FeeMarketingTrait
             */
            $fee_sharing_marketing_active = $this->feeMarketingRegulerActiveQuery($request->personel_id, $year, $quartal)
                ->groupBy([
                    function ($fee) {
                        return Carbon::parse($fee->confirmed_at)->format("M");
                    },
                ])
                ->map(function ($origin_per_month, $month) {

                    /* calculate fee active per month */
                    return $this->feeMarketingRegulerActiveDataMapping($origin_per_month);
                });

            /**
             * marketing achievement
             */
            $recap_fee = [];
            $achievement_per_month = $achievement
                ->groupBy([
                    function ($order) {
                        return confirmation_time($order)->format('M');
                    },
                ])
                ->map(function ($order_per_month) {
                    $detail["achievement"] = $order_per_month->sum(function ($order) {
                        if ($order->type == "2") {
                            return $order->total;
                        }
                        return $order->invoice->total;
                    });

                    $detail["achievement_settle"] = $order_per_month
                        ->filter(fn ($order) => $this->isSettle($order))
                        ->sum(function ($order) {
                            if ($order->type == "2") {
                                return $order->total;
                            }
                            return $order->invoice->total;
                        });
                    return $detail;
                });

            /* template recap */
            collect($quarter_month[$request->quartal])->each(function ($month) use (
                &$detail,
                &$recap_fee,
                $personel_target,
                $personel_fore_cast,
                $achievement_per_month,
                $fee_sharing_marketing_total,
                $fee_sharing_marketing_active,

            ) {
                $detail["month"] = Carbon::parse($month)->month;
                $detail["target"] = $personel_target;
                $detail["achievement"] = in_array($month, $achievement_per_month->keys()->toArray()) ? $achievement_per_month[$month]["achievement"] : 0;
                $detail["achievement_settle"] = in_array($month, $achievement_per_month->keys()->toArray()) ? $achievement_per_month[$month]["achievement_settle"] : 0;
                $detail["fore_cast"] = in_array($month, $personel_fore_cast->keys()->toArray()) ? $personel_fore_cast[$month] : 0;
                $detail["progress"] = $detail["target"] > 0 ? $detail["achievement"] / $detail["target"] * 100 : 100;
                $detail["fee_reguler"]["settle"] = in_array($month, $fee_sharing_marketing_total->keys()->toArray()) ? $fee_sharing_marketing_total[$month] : 0;
                $detail["fee_reguler"]["total"] = in_array($month, $fee_sharing_marketing_active->keys()->toArray()) ? $fee_sharing_marketing_active[$month] : 0;
                $detail["fee_reguler"]["unsettle"] = $detail["fee_reguler"]["total"] > 0 ? $detail["fee_reguler"]["total"] - $detail["fee_reguler"]["settle"] : 0;

                /* colour */
                if ($detail["progress"] >= 0 && $detail["progress"] <= 50) {
                    $color = "FF6262";
                } else if ($detail["progress"] >= 50.1 && $detail["progress"] <= 80) {
                    $color = "EEF225";
                } else if ($detail["progress"] >= 80.1 && $detail["progress"] <= 100) {
                    $color = "78E57C";
                } else {
                    $color = "7CB8FF";
                }

                $detail["color"] = $color;
                $recap_fee[$month] = $detail;
                return $recap_fee;
            });

            /**
             * marketing fee base on marketing fee on DB
             * not from fee acumulation
             */
            $marketing_fee = MarketingFee::query()
                ->where("personel_id", $request->personel_id)
                ->where("year", $request->year)
                ->where("quarter", $request->quartal)
                ->first();

            /* fee sharing percentage base position */
            $fee_position = FeePosition::query()
                ->with("position", "feeCashminimumOrder")
                ->get();

            $fee = collect($fee_position)->where("position_id", $personel->position_id)->first();

            $recap_fee_on_quartal = [
                "quartal" => $request->quartal,
                "total_fee_target" => [
                    "fee_target_settle" => $marketing_fee ? ($marketing_fee->fee_target_settle ? $marketing_fee->fee_target_settle : 0) : 0,
                    "fee_target_settle_pending" => $marketing_fee ? ($marketing_fee->fee_target_settle_pending ? $marketing_fee->fee_target_settle_pending : 0) : 0,
                    "fee_target_unsettle" => 0,
                    "fee_target" => $marketing_fee ? ($marketing_fee->fee_target_total ? $marketing_fee->fee_target_total : 0) : 0,
                ],
                "total_fee_reguler" => [
                    "fee_reguler_settle" => $marketing_fee ? ($marketing_fee->fee_reguler_settle ? $marketing_fee->fee_reguler_settle : 0) : 0,
                    "fee_reguler_settle_pending" => $marketing_fee ? ($marketing_fee->fee_reguler_settle_pending ? $marketing_fee->fee_reguler_settle_pending : 0) : 0,
                    "fee_reguler_unsettle" => 0,
                    "fee_reguler" => $marketing_fee ? ($marketing_fee->fee_reguler_total ? $marketing_fee->fee_reguler_total : 0) : 0,
                ],
                "total_achievement" => collect($recap_fee)->values()->sum("achievement"),
            ];

            $data_recap = [
                "fee_position" => $fee,
                "total_fee" => $recap_fee_on_quartal,
                "detail_fee" => $recap_fee,
            ];

            return $this->response("00", "succes to get marketing fee recap", $data_recap);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing fee recap", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
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
        $validator = Validator::make($request->all(), [
            "month" => "required_with:year",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }

        try {
            $district_on_region = null;
            $personel_on_district = [];

            /* get all marketing on region if request has region_id */
            if ($request->has("region_id")) {
                $district_on_region = $this->districtListIdByAreaId($request->region_id);
                $personel_on_district = $this->district->whereIn("id", $district_on_region)->get()->pluck("personel_id");
                $personel_on_district = collect($personel_on_district)->unique();
            }
            $sales_orders = $this->sales_order->query()
                ->with([
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "areaMarketing" => function ($Q) {
                                return $Q->with([
                                    "subRegionWithRegion" => function ($Q) {
                                        return $Q->with([
                                            "region",
                                        ]);
                                    },
                                ]);
                            },
                        ])
                            ->whereHas("areaMarketing");
                    },
                    "invoice",
                    "sales_order_detail",
                ])

                /* filter by year */
                ->when($request->has("year"), function ($QQQ) use ($request) {
                    return $QQQ
                        ->whereYear("created_at", $request->year)
                        ->orWhereYear("created_at", (int) $request->year - 1);
                })

                /* default year is this year */
                ->when(!$request->has("year"), function ($QQQ) {
                    return $QQQ
                        ->whereYear("created_at", Carbon::now())
                        ->orWhereYear("created_at", Carbon::now()->subYear());
                })

                /* filter region */
                ->when($request->has("region_id"), function ($qqq) use ($personel_on_district) {
                    return $qqq
                        ->whereIn("personel_id", $personel_on_district);
                })

                ->whereHas("personel", function ($QQQ) {
                    return $QQQ->whereHas("areaMarketing");
                })
                ->where("status", "confirmed")
                ->get();

            /* group data bt region */
            $sales_orders_grouped = $sales_orders->groupBy([
                function ($val) {
                    return $val->personel->areaMarketing->subRegionWithRegion->region->id;
                },
                function ($val) {
                    return confirmation_time($val)->format('M');
                },
            ]);

            $detail = [
                "region" => null,
                "data" => [
                    "first_year" => [
                        "all_marketing" => 0,
                        "marketing_pass_target" => 0,
                    ],
                    "second_year" => [
                        "all_marketing" => 0,
                        "marketing_pass_target" => 0,
                    ],
                ],
            ];

            return [
                "data" => $sales_orders_grouped,
                "test" => "",
            ];

            return $this->response("00", "success to get marketing achievement target recap", $sales_order);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing achievement target recap", $th->getMessage());
        }
    }

    public function create()
    {
        try {
            $date = Carbon::now()->month(Carbon::now()->month - 2);
            $startDate = Carbon::now()->startOfQuarter(); // the actual start of quarter method
            $endDate = Carbon::now()->endOfQuarter();
            $quarter_first = Carbon::now()->subMonths(9)->startOfQuarter();
            $quartal_year = $startDate->month;
            return $this->response("00", "active dealer", $quarter_first);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get total sales", $th->getMessage());
        }
    }

    public function exportAreaMarketing()
    {
        ini_set('max_execution_time', 1500); //3 minutes
        $datenow = Carbon::now()->format('d-m-Y');

        $data = DB::table("marketing_area_districts as md")
            ->select("mr.name as region", "ms.name as sub_region", "p.name as province", "c.name as city", "d.name as district", "pd.name as Marketing", "j.name as jabatan")
            ->leftJoin("indonesia_districts as d", "d.id", "=", "md.district_id")
            ->leftJoin("indonesia_cities as c", "c.id", "=", "md.city_id")
            ->leftJoin("indonesia_provinces as p", "p.id", "=", "md.province_id")
            ->leftJoin("marketing_area_sub_regions as ms", "ms.id", "=", "md.sub_region_id")
            ->leftJoin("marketing_area_regions as mr", "mr.id", "=", "ms.region_id")
            ->leftJoin("personels as pd", "pd.id", "=", "md.personel_id")
            ->leftJoin("positions as j", "j.id", "=", "pd.position_id")
            ->orderByRaw("mr.name,ms.name,p.name,c.name,d.name,pd.name")->get();

        return $this->response("00", "success", $data);
    }

    public function exportCalendarCrop(Request $request)
    {

        ini_set('max_execution_time', 1500); //3 minutes
        if ($request->has("year")) {
            $year = $request->year;
        } else {
            $year = Carbon::now()->format('Y');
        }

        $data = DB::table("planting_calendars as pc")
            ->select(
                "mr.name as region",
                "ms.name as sub_region",
                "p.name as province",
                "c.name as city",
                "d.name as district",
                "pk.name as kategori",
                "pl.name",
                "pl.scientific_name",
                "pc.year",
                "pc.jan",
                "pc.feb",
                "pc.mar",
                "pc.apr",
                "pc.mei",
                "pc.jun",
                "pc.jul",
                "pc.aug",
                "pc.sep",
                "pc.okt",
                "pc.nov",
                "pc.dec",
                "pc.area_id",
                "pd.name as marketing",
                "j.name as jabatan",
                "up.name as input_oleh",
                "pc.created_at",
                "pc.updated_at"
            )
            ->leftJoin("plants as pl", "pl.id", "=", "pc.plant_id")
            ->leftJoin("plant_categories as pk", "pk.id", "=", "pl.plant_category_id")
            ->leftJoin("marketing_area_districts as md", "md.id", "=", "pc.area_id")
            ->leftJoin("indonesia_districts as d", "d.id", "=", "md.district_id")
            ->leftJoin("indonesia_cities as c", "c.id", "=", "md.city_id")
            ->leftJoin("indonesia_provinces as p", "p.id", "=", "md.province_id")
            ->leftJoin("marketing_area_sub_regions as ms", "ms.id", "=", "md.sub_region_id")
            ->leftJoin("marketing_area_regions as mr", "mr.id", "=", "ms.region_id")
            ->leftJoin("personels as pd", "pd.id", "=", "md.personel_id")
            ->leftJoin("positions as j", "j.id", "=", "pd.position_id")
            ->leftJoin("users as up", "up.id", "=", "pc.user_id")
            ->whereNull("pc.deleted_at")
            ->where("pc.year", $year)
            ->orderByRaw("mr.name,ms.name,p.name,c.name,pk.name,pl.name,pd.name")->get();

        return $this->response("00", "success", $data);
    }

    public function marketingSalesRecapPerSubRegionPerMarketingFourMonthV2(Request $request)
    {
        try {
            $personelRepository = new PersonelRepository;
            $response = $personelRepository->recapPersonel4Mount($request->all());
            return $this->response("00", "success to get marketing sales recap per region per marketing 4 months ago", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing sales recap per region per marketing 4 months ago", $th->getMessage());
        }
    }
}

<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Events\DealerActivatedEvent;
use Modules\KiosDealer\Http\Controllers\DealerLogController;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class DealerInactiveController extends Controller
{
    public function __construct(Dealer $dealer, DealerV2 $dealerv2, Personel $personel, DealerLogController $log, SalesOrderV2 $sales_order)
    {
        $this->dealer = $dealer;
        $this->dealerv2 = $dealerv2;
        $this->personel = $personel;
        $this->log = $log;
        $this->sales_order = $sales_order;
    }

    use ResponseHandler;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $personel_id = $request->personel_id;
            $dealers = null;
            $now = Carbon::now();
            $status = $this->status();
            $inactive_days = DB::table('fee_follow_ups')
                ->whereNull("deleted_at")
                ->orderBy("follow_up_days")
                ->first();

            $days = (int) $inactive_days->follow_up_days;
            $personels_id = [];
            ini_set('max_execution_time', 500); //3 minutes

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
                'User Jember',
                'Direktur Utama'
            )) {

            } else {
                $days -= 15;
                $personels_id = $this->getChildren(auth()->user->personel_id);
            }

            /* indirect sale in last 60/45 days */
            $sales_orders_indirect = DB::table('sales_orders')
                ->whereNull("deleted_at")
                ->where("type", "2")
                ->whereNotNull("date")
                ->where("date", ">", now()->subDays((int) $days))
                ->orderBy("date", "desc")
                ->select("store_id")
                ->get();

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
                ->get();

            $dealer_new = DB::table('dealers')
                ->whereNull("deleted_at")
                ->whereDate("created_at", ">", Carbon::now()->subDays($days))
                ->get();

            /* dealer who have purchases in last 60/45 days */
            $active_dealer = collect($sales_orders_direct)->merge(collect($sales_orders_indirect))->pluck("store_id")->toArray();

            $dealers = $this->dealer->query()
                ->with([
                    'personel',
                    'agencyLevel',
                    'dealer_file',
                    'handover',
                    'statusFee',
                    'salesOrderOnly' => function ($QQQ) {
                        return $QQQ
                            ->where('status', 'confirmed')
                            ->orderByRaw("type")
                            ->with([
                                "invoiceOnly",
                            ]);
                    },

                ])

            /* excluding active dealer */
                ->where(function ($QQQ) use ($active_dealer, $dealer_new) {
                    $active_dealer = collect($active_dealer)->merge($dealer_new->pluck("id"))->unique();
                    return $QQQ
                        ->whereNotIn("id", $active_dealer->toArray());
                })

                ->when($request->is_blocked == true, function($query){
                    return $query->whereNull("blocked_at");
                })

            /* filter by name */
                ->when($request->has('name'), function ($Q) use ($request) {
                    return $Q->name($request->name);
                })

            /* filter by customer id */
                ->when($request->has("dealer_id"), function ($q) use ($request) {
                    return $q->where("dealer_id", "like", "%" . $request->dealer_id . "%");
                })

                ->whereIn('status', $status)

            /* filter by personel_id */
                ->when($request->has("personel_id"), function ($q) use ($personel_id) {
                    return $q->whereIn('personel_id', [$personel_id]);
                })

            /* filter by region */
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    return $QQQ->region($request->region_id);
                })

            /* personel brach filter, e.g rico */
                ->when($request->personel_branch, function ($QQQ) {
                    return $QQQ->PersonelBranch();
                })

                ->when($request->with_trashed, function ($QQQ) {
                    return $QQQ->withTrashed();
                })
                ->when(!$request->with_trashed, function ($QQQ) {
                    return $QQQ;
                })
                ->when($request->has('filter'), function ($Q) use ($request) {
                    return $Q->filterAll($request->filter);
                })
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("order_type")) {
                        $sort_type = $request->order_type;
                    }

                    if ($request->sorting_column == 'marketing_name') {

                        return $QQQ->orderBy(Personel::select('name')->whereColumn('personels.id', 'dealers.personel_id'), $request->order_type);
                    } elseif ($request->sorting_column != "marketing_name") {
                        return $QQQ->orderBy($request->sorting_column, $sort_type);
                    } else {
                        return $QQQ->orderBy("updated_at", "desc");
                    }
                })
                ->paginate($request->limit ? $request->limit : 10);

            foreach ($dealers as $dealer) {
                $indirect_amount_order = collect($dealer->salesOrderOnly)
                    ->where("type", "2")
                    ->where("status", "confirmed")
                    ->sum("total");

                $direct_amount_order = collect($dealer->salesOrderOnly)
                    ->where("type", "1")
                    ->where("status", "confirmed")
                    ->where("invoiceOnly", "!=", null)
                    ->sum(function ($col) {
                        return $col->invoiceOnly->total + $col->invoiceOnly->ppn;
                    });

                $total_payment = 0;
                $direct_total_payment = collect($dealer->salesOrderOnly)->where("type", "1")->where("invoiceOnly", "!=", null)->map(function ($order, $key) use ($total_payment) {
                    $total_payment = collect($order->invoiceOnly->payment)->sum("nominal");
                    return $total_payment;
                })->sum();

                /* last order attribute */
                $last_order = null;
                $last_direct = null;
                $last_indirect = null;
                $last_direct_submit = null;
                $payment = null;
                $compare_date_order = false;

                if ($dealer->salesOrderOnly) {
                    $direct_sale = collect($dealer->salesOrderOnly)
                        ->where("type", "1")
                        ->where("status", "confirmed")
                        ->where("invoiceOnly", "!=", null)
                        ->sortByDesc("invoiceOnly.created_at")
                        ->first();

                    $direct_submit = collect($dealer->salesOrderOnly)
                        ->where("type", "1")
                        ->where("status", "submited")
                        ->sortByDesc("created_at")
                        ->first();

                    $indirect_sale = collect($dealer->salesOrderOnly)
                        ->where("type", "2")
                        ->sortByDesc("date")
                        ->first();

                    if ($direct_sale && $indirect_sale) {
                        if (Carbon::createFromFormat("Y-m-d H:i:s", $indirect_sale->date)->gt(Carbon::createFromFormat("Y-m-d H:i:s", $direct_sale->invoiceOnly->created_at))) {
                            $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $indirect_sale->date, 'UTC')->setTimezone('Asia/Jakarta');
                        } else {
                            $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $direct_sale->invoiceOnly->created_at, 'UTC')->setTimezone('Asia/Jakarta');
                        }
                    } else if ($direct_sale) {
                        $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $direct_sale->invoiceOnly->created_at, 'UTC')->setTimezone('Asia/Jakarta');

                    } else if ($indirect_sale) {
                        $last_order = Carbon::createFromFormat('Y-m-d H:i:s', $indirect_sale->date, 'UTC')->setTimezone('Asia/Jakarta');

                    } else {
                        $last_order = null;
                    }
                }

                $dealer->last_order = $last_order ? $last_order->setTimezone('Asia/Jakarta') : null;

                /* days last order */
                $dealer->days_last_order = $last_order ? $last_order->setTimezone('Asia/Jakarta')->startOfDay()->diffInDays(Carbon::now(), false) : null;

                /* active status */
                $days = 0;
                $active_status = true;
                if ($last_order) {
                    $days = $last_order ? $last_order->setTimezone('Asia/Jakarta')->startOfDay()->diffInDays(Carbon::now()) : 0;
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
                    $check = Carbon::now()->between($last_order, Carbon::createFromFormat('Y-m-d H:i:s', $last_order)->addYear());
                    $active_status_one_year = $check;
                }
                $dealer->active_status_one_year = $active_status_one_year;

                $dealer = $dealer->unsetRelation("salesOrderOnly");
            }

            return $this->response('00', 'Dealers index', $dealers);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show dealers', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('kiosdealer::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $dealer = $this->dealer->query()
                ->withTrashed()
                ->leftJoin('sales_orders', 'dealers.id', '=', 'sales_orders.store_id')
                ->selectRaw('dealers. *, sales_orders.created_at as last_order')
                ->where('dealers.id', $id)
                ->orderBy("sales_orders.created_at", 'desc')
                ->first();
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
        return view('kiosdealer::edit');
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
            $dealer = $this->dealer->withTrashed()->findOrFail($id);
            if ($request->note != null || $request->note != '') {
                $dealer->note = $request->note;
                $dealer->save();
                $this->dealerLog($dealer->id, "update note");
            } else {
                if ($dealer->deleted_at != null) {

                    // Cannot activated if is_block_grading == 1
                    if($dealer->is_block_grading == true){
                        return $this->response("04", "cant activated dealer, have block by grading", ["name" => $dealer->name, "grading" => $dealer->grading->name]);
                    }

                    $dealer->deleted_at = null;
                    $dealer->save();
                    $this->dealerLog($dealer->id, "delete/deactivate");
                    /* update dealer grade after activated */
                    DealerActivatedEvent::dispatch($dealer);
                } else {
                    $dealer->delete();
                    $this->dealerLog($dealer->id, "delete/deactivate");
                }
            }

            return $this->response('00', 'dealer active status updated', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update active status dealer', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
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

    public function checkBawahan($request, $personel_id, $days)
    {
        $dealers = null;
        $personel = $this->personel->find($personel_id);
        $personels_id = [$personel_id];
        $status = $this->status();
        $now = Carbon::now();
        if ($personel->children == []) {
            $dealers = $this->dealer->query()
                ->with([
                    'personel',
                    'agencyLevel',
                    'dealer_file',
                    'handover',
                    'statusFee',
                    'salesOrderOnly.invoiceOnly',
                ])

            /* filter by name */
                ->when($request->has("name"), function ($q) use ($request) {
                    return $q->where("name", "like", "%" . $request->name . "%");
                })

            /* new dealer created < 60 days and has no order or null */
                ->where(function ($QQQ) use ($now, $days) {
                    return $QQQ
                        ->where(function ($QQQ) use ($now, $days) {
                            return $QQQ
                                ->whereDate("created_at", "<", $now->subDays((int) $days))
                                ->where(function ($QQQ) use ($now, $days) {
                                    return $QQQ
                                        ->whereHas("salesOrder", function ($QQQ) use ($now, $days) {
                                            return $QQQ
                                                ->whereIn("status", ["submited", "confirmed"])
                                                ->whereDate('created_at', '<', $now->subDays((int) $days));
                                        })
                                        ->orWhereDoesntHave("salesOrder");
                                });
                        });
                })

                ->whereIn('personel_id', $personels_id)
                ->whereIn('status', $status)
                ->select("dealers.*")
                ->orderBy("updated_at", "desc")
                ->paginate($request->limit ? $request->limit : 15);
        } else {
            $personels_id = $this->getChildren($personel_id);
            $dealers = $this->dealer->query()
                ->with('personel', 'agencyLevel', 'dealer_file', 'handover')

            /* filter by name */
                ->when($request->has("name"), function ($q) use ($request) {
                    return $q->where("name", "like", "%" . $request->name . "%");
                })

            /* new dealer created < 60 days and has no order or null */
                ->where(function ($QQQ) use ($now, $days) {
                    return $QQQ
                        ->where(function ($QQQ) use ($now, $days) {
                            return $QQQ
                                ->whereDate("created_at", "<", $now->subDays((int) $days))
                                ->where(function ($QQQ) use ($now, $days) {
                                    return $QQQ
                                        ->whereHas("salesOrder", function ($QQQ) use ($now, $days) {
                                            return $QQQ
                                                ->whereIn("status", ["submited", "confirmed"])
                                                ->whereDate('created_at', '<', $now->subDays((int) $days));
                                        })
                                        ->orWhereDoesntHave("salesOrder");
                                });
                        });
                })

            /* filter by personel_id */
                ->when($request->has("personel_id"), function ($q) use ($personel_id) {
                    return $q->whereIn('personel_id', [$personel_id]);
                })

                ->whereIn('personel_id', $personels_id)
                ->whereIn('status', $status)
                ->select("dealers.*")
                ->orderBy("updated_at", "desc")
                ->paginate($request->limit ? $request->limit : 15);
        }
        return $dealers;
    }

    public function status()
    {
        $status = [
            "accepted",
            "submission of changes",
        ];
        return $status;
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
}

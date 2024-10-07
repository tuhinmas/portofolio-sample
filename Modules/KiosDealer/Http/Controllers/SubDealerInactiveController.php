<?php

namespace Modules\KiosDealer\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SubDealerInactiveController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $personel_id = $request->personel_id;
            $dealers = null;
            $now = Carbon::now();
            $status = $this->status();
            $inactive_days = DB::table('inactive_parameters')
                ->where('name', 'inactive dealer')
                ->orderBy("parameter", "desc")
                ->first();
            $days = (int) $inactive_days->parameter;
            if (auth()->user()->hasAnyRole("super-admin", "Marketing Support")) {
                $dealers = $this->dealer->query()
                    ->when($request->has("name"), function ($q) use ($request) {
                        return $q->where("dealers.name", "like", "%" . $request->name . "%");
                    })
                    ->with('personel', 'agencyLevel', 'dealer_file', 'handover', 'statusFee')
                    ->leftJoin('sales_orders', function ($join) {
                        $join->on('dealers.id', '=', 'sales_orders.store_id')
                            ->whereRaw("sales_orders.id in(select max(SO.id) from sales_orders as SO join dealers as D on D.id = SO.store_id group by D.id)");
                    })
                    ->whereDate('sales_orders.created_at', '<', $now->subDays((int) $days - 15))
                    ->orWhereNull('sales_orders.created_at')
                    ->whereIn('dealers.status', $status)
                    ->when($request->has("personel_id"), function ($q) use ($personel_id) {
                        return $q->whereIn('dealers.personel_id', [$personel_id]);
                    })
                    ->where("dealers.name", "like", "%" . $request->name . "%")
                    ->withTrashed()
                    ->select('dealers.*')
                    ->orderBy("updated_at", "desc")
                    ->paginate(30);
            } else {
                if (isset($personel_id)) {
                    $dealers = $this->dealer->query()
                        ->when($request->has("name"), function ($q) use ($request) {
                            return $q->where("dealers.name", "like", "%" . $request->name . "%");
                        })
                        ->with('personel', 'agencyLevel', 'dealer_file', 'handover', "statusFee")
                        ->leftJoin('sales_orders', function ($join) {
                            $join->on('dealers.id', '=', 'sales_orders.store_id')
                                ->whereRaw("sales_orders.id in(select max(SO.id) from sales_orders as SO join dealers as D on D.id = SO.store_id group by D.id)");
                        })
                        ->whereDate('sales_orders.created_at', '<', $now->subDays((int) $days - 14))
                        ->orWhereNull('sales_orders.created_at')
                        ->whereIn('dealers.personel_id', [$personel_id])
                        ->where("dealers.name", "like", "%" . $request->name . "%")
                        ->withTrashed()
                        ->select('dealers.*')
                        ->orderBy("dealers.updated_at", "desc")
                        ->paginate(30);
                } else {
                    $personel_id = auth()->user()->personel_id;
                    $dealers = $this->checkBawahan($request, $personel_id, $days);
                }
            }
            return $this->response('00', 'Dealers index', $dealers);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to show dealers', $th->getMessage());
        }    }

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
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display dealer detail', $th->getMessage());
        }    }

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
                    $dealer->deleted_at = null;
                    $dealer->save();
                    $this->dealerLog($dealer->id, "delete/deactivate");
                } else {
                    $dealer->delete();
                    $this->dealerLog($dealer->id, "delete/deactivate");
                }
            }
            return $this->response('00', 'dealer active status updated', $dealer);
        } catch (\Throwable$th) {
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

<?php

namespace Modules\KiosDealer\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\SupervisorCheck;
use Illuminate\Routing\Controller;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Contracts\Support\Renderable;

class DealerTestController extends Controller
{
    use SupervisorCheck;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $personel_id = auth()->user()->personel_id;
            $district = $this->districtSubordinateList($personel_id);
            // $dealers = Dealer::query()
            //     ->leftJoin("address_with_details", "address_with_details.parent_id", "=", "dealers.id")
            //     ->whereIn("address_with_details.district_id", $district)
            //     ->paginate(15);
            return response()->json([
                "code" => "00",
                // "data" => $dealers,
                "ditricts" => $district
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "code" => "01",
                "data" => $th->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        try {
            $invoice = SalesOrder::query()
                ->join("invoices as i", "sales_orders.id", "=", "i.sales_order_id")
                ->select()
                ->get()
                ->groupBy(function($d){
                    return [
                    Carbon::parse($d->created_at)->format('Y'),
                    Carbon::parse($d->created_at)->format('m'),
                    ];
                });
                return response()->json([
                    "code" => "01",
                    "data" => $invoice
                ]);    
        } catch (\Throwable $th) {
            return response()->json([
                "code" => "01",
                "data" => $th->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('kiosdealer::show');
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
        //
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
}

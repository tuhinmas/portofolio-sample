<?php

namespace Modules\KiosDealer\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\KiosDealer\Entities\Handover;
use Illuminate\Contracts\Support\Renderable;
use Modules\KiosDealer\Http\Controllers\DealerLogController;

class DealerConfirmationController extends Controller
{
    use ResponseHandler;
    public function __construct(Personel $personel, Dealer $dealer, Handover $handover, DealerLogController $log)
    {
        $this->personel = $personel;
        $this->dealer = $dealer;
        $this->handover = $handover;
        $this->log = $log;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $dealers = null;
            if (auth()->user()->personel_id == null || auth()->user()->hasAnyRole('super-admin','Marketing Support','admin')) {
                $dealers = $this->dealer->query()
                    ->with('dealer_file', 'adress_detail')
                    ->withCount("dealer_file")
                    ->having('dealer_file_count', '>', '3')
                    ->where('status', 'filed')
                    ->orWhere('status', 'submission of changes')
                    ->orderBy('updated_at', 'desc')
                    ->paginate(30);
            } else {
                $personel_id = auth()->user()->personel_id;
                $dealers = $this->checkBawahan($request, $personel_id);
            }
            return $this->response('00', 'Dealers confirmation index', $dealers);
        } catch (\Throwable$th) {
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

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $dealer_confirmation = $this->dealer->query()
                ->with('dealer_file')
                ->withCOunt("dealer_file")
                ->where('id', $id)
                ->first();

            return $this->response('00', 'dealer detail with dealer file', $dealer_confirmation);
        } catch (\Throwable$th) {
            return $this->response('00', 'failed to display dealer detail', $th->getMessage());
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
            $dealer = $this->dealer->findOrFail($id);
            if ($request->has("status")) {
                $dealer->status = $request->status;
                $dealer->status_color = $request->status_color;
            }

            if ($request->has("note")) {
                $dealer->note = $request->note;
            }
            $dealer->save();
            return $this->response('00', 'dealer status/note updated', $dealer);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to confirm dealer', $th->getMessage());
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

    public function checkBawahan($request, $personel_id)
    {
        $dealers = null;
        $personel = $this->personel->find($personel_id);
        $personels_id = [$personel_id];
        if ($personel->children == []) {
            $dealers = $this->dealer->query()
                ->with('dealer_file')
                ->withCOunt("dealer_file")
                ->having('dealer_file_count', '>', '3')
                ->where('personel_id', $personel_id)
                ->where('status', 'filed')
                ->orWhere('status', 'submission of changes')
                ->orderBy('updated_at', 'desc')
                ->paginate(30);
        } else {
            $personels_id = $this->getChildren($personel_id);
            $dealers = $this->dealer->query()
                ->with('dealer_file')
                ->withCOunt("dealer_file")
                ->having('dealer_file_count', '>', '3')
                ->whereIn('personel_id', $personels_id)
                ->where('status', 'filed')
                ->orWhere('status', 'submission of changes')
                ->orderBy('updated_at', 'desc')
                ->paginate(30);
        }
        return $dealers;

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
                ->with('dealer_file')
                ->orderBy('dealer_id', 'desc')
                ->first();
            $dealer_id = $dealer->dealer_id;
            $new_dealer_id = (int)$dealer_id + 1;
            return $new_dealer_id;
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to generate dealer_id', $th->getMessage());
        }
    }

    /**
     * dealer log
     *
     * @param [type] $dealer_id
     * @param [type] $activity (crud)
     * @return void
     */
    public function dealerLog($dealer_id, $activity){
        $request = new Request;
        $request["dealer_id"] = $dealer_id;
        $request["activity"] = $activity;
        $this->log->store($request);
    }
}

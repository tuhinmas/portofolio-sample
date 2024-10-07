<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Store;
use Modules\Personel\Entities\Personel;
use Modules\KiosDealer\Entities\StoreTemp;
use Illuminate\Contracts\Support\Renderable;
use Ladumor\OneSignal\OneSignal;
use Modules\KiosDealer\Events\StoreTempConfirmationEvent;
use Modules\KiosDealer\Notifications\StoreTempNotification;

class StoreConfirmationController extends Controller
{
    use ResponseHandler;

    public function __construct(Store $store, Personel $personel)
    {
        $this->store = $store;
        $this->personel = $personel;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        try {
            $stores = null;
            if (auth()->user()->personel_id == null || auth()->user()->hasRole('Marketing Support')){
                $stores = $this->store->query()
                    ->with('core_farmer')
                    ->withCount("core_farmer")
                    ->having('core_farmer_count', '>', '2')
                    ->where('status', 'filed')
                    ->orWhere('status', 'submission of changes')
                    ->orderBy('updated_at','desc')
                    ->paginate(30);
            } else {
                $personel_id = auth()->user()->personel_id;
                $stores = $this->checkBawahan($personel_id);
            }
            return $this->response('00', 'Stores list', $stores);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to display stores', $th);
        };
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
        try {
            $store_confirmation = $this->store->findOrFail($id);
            $store_confirmation->note = $request->note;
            $store_confirmation->status = $request->status;
            $store_confirmation->status_color = $request->status_color;
            $store_confirmation->save();

            return $this->response('00', 'store confirmed', $store_confirmation);
        } catch (\Throwable$th) {
            return $this->response('00', 'failed to confirm store', $th);
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
     * check marketing under personel
     *
     * @param [type] $request
     * @param [type] $personel_id
     * @return void
     */
    public function checkBawahan($personel_id)
    {
        $stores = null;
        $personel = $this->personel->find($personel_id);
        $personels_id = [$personel_id];
        if ($personel->children == []) {
            $stores = $this->store->query()
                ->with('core_farmer')
                ->withCOunt("core_farmer")
                ->where('personel_id', $personel_id)
                ->where('status', 'filed')
                ->orWhere('status', 'submission of changes')
                ->orderBy('name')
                ->paginate(30);
        } else {
            $personels_id = $this->getChildren($personel_id);
            $stores = $this->store->query()
                ->with('core_farmer')
                ->withCOunt("core_farmer")
                ->whereIn('personel_id', $personels_id)
                ->where('status', 'filed')
                ->orWhere('status', 'submission of changes')
                ->orderBy('name')
                ->paginate(30);
        }
        return $stores;

    }

    /**
     * get all choldren
     *
     * @param [type] $personel_id
     * @return void
     */
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

    public function confirmStore(Request $request, $id){
        DB::beginTransaction();
        try {
            $store_temp = StoreTemp::findOrFail($id);
            $confirm_store = StoreTempConfirmationEvent::dispatch($store_temp);
            
            // dd($confirm_store[0]["personel_id"]);
            


            DB::commit();
            return $this->response("00", "success", $confirm_store[0]);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    // private function notif($storeTemp)
    // {
    
    // }
}

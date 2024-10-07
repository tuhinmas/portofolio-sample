<?php

namespace Modules\KiosDealer\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\KiosDealer\Entities\CoreFarmer;
use Modules\KiosDealer\Entities\CoreFarmerTemp;

class CoreFarmerController extends Controller
{
    public function __construct(CoreFarmer $core_farmer, CoreFarmerTemp $core_farmer_temp)
    {
        $this->core_farmer = $core_farmer;
        $this->core_farmer_temp = $core_farmer_temp;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $store_id = $request->store_id;
            $core_farmers = $this->core_farmer
                ->where('store_id', $store_id)
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction;
                    }
                    if ($request->sorting_column) {
                        return $QQQ->orderBy($request->sorting_column, $sort_type);
                    } else {
                        return $QQQ->orderBy("name", "asc");
                    }
                })
                ->get();

            return $this->response('00', 'core farmer index', $core_farmers);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display core farmers', $th->getMessage());
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
        $request->validate([
            'telephone' => 'required|max:120',
            'name' => 'required|max:255',
            'address' => 'required',
        ]);

        try {
            $core_farmer = $this->core_farmer->create([
                'telephone' => $request->telephone,
                'store_id' => $request->store_id,
                'name' => $request->name,
                'address' => $request->address,
            ]);
            return $this->response('00', 'core farmer saved', $core_farmer);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save core farmer', $th->getMessage());
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
            $core_farmer = $this->core_farmer->findOrFail($id);
            return $this->response('00', 'core farmer detail', $core_farmer);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display core farmer detail', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $core_farmer = $this->core_farmer->find($id);

        return response()->json([
            'response_code' => '00',
            'response_message' => 'farmers edit',
            'data' => $core_farmer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'telephone' => 'required|max:120',
            'name' => 'required|max:255',
            'address' => 'required',
        ]);

        try {
            $core_farmer = $this->core_farmer->findOrFail($id);
            $core_farmer->store_id = $request->store_id;
            $core_farmer->name = $request->name;
            $core_farmer->address = $request->address;
            $core_farmer->save();

            $core_farmer_check = $this->checkExistingData($request->telephone);

            if (!is_null($core_farmer_check)) {
                if ($core_farmer_check->id != $id) {
                    return $this->response('02', 'core farmer with these telephone already exist', $core_farmer_check);
                }
            }
            $core_farmer->telephone = $request->telephone;
            $core_farmer->save();
            return $this->response('00', 'core farmer updated', $core_farmer);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update core farmer', $th->getMessage());
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
            $core_farmer = $this->core_farmer->findOrFail($id);
            $core_farmer->delete();
            return $this->response('00', 'core farmer deleted', $core_farmer);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete core farmer', $th->getMessage());
        }
    }

    /**
     * check existing data
     *
     * @param [type] $telephone
     * @return void
     */
    public function checkExistingData($telephone)
    {
        $core_farmer = $this->core_farmer->where("telephone", $telephone)->first();
        return $core_farmer;
    }

    /**
     * response
     *
     * @param [type] $code
     * @param [type] $message
     * @param [type] $data
     * @return void
     */
    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }

    public function searchCoreFarmerByTelephone(Request $request)
    {
        try {
            $core_farmer = $this->core_farmer->query()
                ->with("store")
                ->where("telephone", $request->telephone)
                ->first();

            return $this->response("00", "core farmer check by telephone", $core_farmer);
        } catch (\Throwable$th) {
            return $this->response("01", "failed to check core farmer by telephone", $th->getMessage());
        }
    }
}

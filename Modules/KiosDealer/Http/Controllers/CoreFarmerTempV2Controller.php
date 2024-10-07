<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\KiosDealer\Entities\CoreFarmer;
use Modules\KiosDealer\Entities\CoreFarmerTemp;
use Modules\KiosDealer\Entities\StoreTemp;

class CoreFarmerTempV2Controller extends Controller
{
    use ResponseHandler;

    public function __construct(CoreFarmerTemp $core_farmer_temp)
    {
        $this->core_farmer_temp = $core_farmer_temp;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $core_farmers = $this->core_farmer_temp
                ->with([
                    "store" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                ])
                ->when($request->has("filters"), function ($QQQ) use ($request) {
                    return $this->filter($QQQ, $request);
                })
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction;
                    }
    
                    return $QQQ->orderBy($request->sorting_column, $sort_type);
                    
                })
                ->paginate($request->limit ? $request->limit : 10);
            return $this->response("00", "success", $core_farmers);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    public function filter($query, $request)
    {
        foreach ($request->filters as $i => $search) {
            if (is_array($search["value"])) {
                foreach ($search["value"] as $key => $arraySearch) {
                    if ($key == 0) {
                        $query = $query->where($search['field'], $search["operator"], $arraySearch);
                    }
                    $query = $query->orWhere($search['field'], $search["operator"], $arraySearch);
                }
            } else {
                if ($i == 0) {
                    $query = $query->where($search['field'], $search["operator"], $search["value"]);
                } else {
                    if (array_key_exists("type", $search)) {
                        $query = $query->orWhere($search['field'], $search["operator"], $search["value"]);
                    } else {
                        $query = $query->where($search['field'], $search["operator"], $search["value"]);
                    }
                }
            }
        }
        return $query;
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
        if (!$request->has("resources")) {
            $validator = Validator::make($request->all(), [
                "name" => "required|string|min:1|max:200",
                "address" => "required|min:1|max:100",
                'telephone' => "required|digits_between:6,15",
                "store_temp_id" => "required|max:50",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "invalid data send", $validator->errors());
            }
        } else {
            $validator = Validator::make($request->all(), [
                "resources" => "required",
            ]);

            if ($validator->fails()) {
                return $this->response("04", "invalid data send", $validator->errors());
            }

            if (count($request->resources[0]) < 3) {
                $data = [];
                $array_keys = array_keys($request->resources[0]);
                $request_list = [
                    "dealer_id" => "required",
                    "file_type" => "required",
                    "data" => "required",
                ];

                foreach ($request_list as $request) {
                    if (!in_array($request, $array_keys)) {
                        $data[$request] = ["validation.required"];
                    }
                }

                return $this->response("04", "invalid data send", $data);
            }
        }

        try {
            $core_farmer_temp = $this->core_farmer_temp;
            $data = $request->all();
            $response = [];

            /* batch store */
            if ($request->has("resources")) {
                foreach ($request->resources as $key => $value) {
                    $res = [];
                    foreach ($value as $attribute => $data) {
                        $res[$attribute] = $data;
                    }
                    $res = $core_farmer_temp->create($res);
                    array_push($response, $res);
                }
                return $this->response("00", "success", $response);
            }

            /* single store */
            foreach ($data as $key => $value) {
                $core_farmer_temp[$key] = $value;
            }

            $core_farmer_temp->save();
            return $this->response("00", "success", $core_farmer_temp);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
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
            $core_farmer_temp = $this->core_farmer_temp->with("store")->findOrFail($id);
            return $this->response("00", "success", $core_farmer_temp);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
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
            $core_farmer_temp = $this->core_farmer_temp->with("store")->findOrFail($id);

            foreach ($request->all() as $key => $value) {
                $core_farmer_temp[$key] = $value;
            }
            $core_farmer_temp->save();
            return $this->response("00", "success", $core_farmer_temp);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
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
            $core_farmer_temp = $this->core_farmer_temp->with("store")->findOrFail($id);
            $core_farmer_temp->delete();
            return $this->response("00", "success", $core_farmer_temp);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    public function checkCoreFarmerTempByTelephone(Request $request, $store_temp_id)
    {
        try {
            $validate = Validator::make($request->all(), [
                "telephone" => "required"
            ]);

            if ($validate->fails()) {
                return $this->response("04", "invalid data send", $validate->errors());
            }

            $core_farmer = $this->core_farmer_temp->query()
                ->with("store")
                // ->whereNull("core_farmer_id")
                ->where("store_temp_id", $store_temp_id)
                ->where("telephone", $request->telephone)
                ->first();


            if ($core_farmer) {
                return $this->response("00", "used by same kios", $core_farmer);
            } else {
                $store = StoreTemp::with("store")->find($store_temp_id);
                $store_id = $store->store_id;
                // $store_telephone = $store->store ? $store->store->telephone : null;
                $personel_id = $store->personel_id;
                
                $corefarmer = CoreFarmer::selectRaw("core_farmers.*, st.personel_id")->where("store_id", "!=", $store_id)
                    ->where("core_farmers.telephone", $request->telephone)
                    ->leftJoin("stores as st", "core_farmers.store_id", "st.id")
                    ->where("st.personel_id", $personel_id)
                    ->with("store.personel")
                    ->first();

                if ($corefarmer) {
                    return $this->response("00", "used by other kios", $corefarmer);
                } else {
                    return $this->response("00", "core farmer temp with these phone number not found", null);
                }
            }
        } catch (\Throwable $th) {
            return $this->response("01", "failed to check core farmer by telephone", $th->getMessage());
        }
    }
}

<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class AddressFinderController extends Controller
{

    use ResponseHandler, SuperVisorCheckV2, MarketingArea;
    use ChildrenList;

    /**
     * Indonesia province
     *
     * @return void
     */
    public function province(Request $request)
    {
        if ($request->missing('global')) {
            $request->merge(['global' => false]);
        }

        $personel_id = auth()->user()->personel_id;

        /**
         * marketing role return match data
         */
        $district_id = [];
        if (auth()->user()->hasAnyRole(marketing_positions()) && $request->scope_supervisor) {
            $district_id = $this->districtListMarketing($personel_id);
        } elseif (auth()->user()->hasAnyRole(marketing_positions())) {
            $district_id = $this->districtListAsMarketing($personel_id);
            if (auth()->user()->hasAnyRole(applicator_positions())) {
                $district_id = MarketingAreaDistrict::where("applicator_id", $personel_id)->get()->map(function ($data) {
                    return $data->district_id;
                });
            }
        }

        try {
            $province = Province::query()
                ->where("name", 'like', '%' . $request->name . '%')
                ->with("city")
                ->when(!$request->global, function ($query) use ($district_id) {
                    return $query->whereHas('districts', function ($q) use ($district_id) {
                        return $q->whereIn('marketing_area_districts.district_id', $district_id);
                    });
                })
                ->orderBy('name')
                ->get();

            return $this->response('00', 'Indonesia province', $province);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Indonesia province', $th->getMessage(), 500);
        }
    }

    public function provinceOnly(Request $request)
    {

        try {
            $province = Province::query()
                ->where("name", 'like', '%' . $request->name . '%')
                ->orderBy('name')
                ->get();

            return $this->response('00', 'Indonesia province', $province);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Indonesia province', $th->getMessage());
        }
    }

    /**
     * show cities by province id
     *
     * @param Request $request, province_id = province id
     * @return void
     */
    public function findCity(Request $request)
    {
        if ($request->missing('global')) {
            $request->merge(['global' => false]);
        }

        $personel_id = auth()->user()->personel_id;

        /**
         * marketing role return match data
         */
        $district_id = [];
        if (auth()->user()->hasAnyRole(marketing_positions()) && $request->scope_supervisor) {
            $district_id = $this->districtListMarketing($personel_id);
        } elseif (auth()->user()->hasAnyRole(marketing_positions())) {
            $district_id = $this->districtListAsMarketing($personel_id);
            if (auth()->user()->hasAnyRole(applicator_positions())) {
                $district_id = MarketingAreaDistrict::where("applicator_id", $personel_id)->get()->map(function ($data) {
                    return $data->district_id;
                });
            }
        }

        try {
            $cities = City::query()
                ->when(!$request->global, function ($query) use ($district_id) {
                    return $query->whereHas('districts', function ($q) use ($district_id) {
                        return $q->whereIn('marketing_area_districts.district_id', $district_id);
                    });
                })

                ->when($request->has("distributor_contract_id"), function ($QQQ) use ($request) {
                    return $QQQ->outSideDistributorArea($request->distributor_contract_id);
                })

                ->where('indonesia_cities.province_id', $request->province_id)
                ->where("name", "like", "%" . $request->name . "%")
                ->orderBy('name')
                ->get();

            return $this->response('00', 'Cities list', $cities);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show cities', [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    /**
     * show District by city id
     *
     * @param Request $request, district_id = District id
     * @return void
     */
    public function findDistrict(Request $request)
    {
        if ($request->missing('global')) {
            $request->merge(['global' => false]);
        }

        $personel_id = $this->getChildren(auth()->user()->personel_id);

        $personel = auth()->user()->personel_id;

        // dd(auth()->user()->personel_id);
        try {
            $cities = District::query()
                ->when(!$request->global, function ($query) use ($personel_id, $request) {
                    return $query
                        ->when($request->scope_supervisor, function ($QQQ) use ($personel_id) {
                            return $QQQ->whereHas('districts', function ($q) use ($personel_id) {
                                return $q->whereIn('personel_id', $personel_id);
                            });
                        })
                        ->when(!$request->scope_supervisor, function ($QQQ) use ($personel_id) {
                            return $QQQ
                                ->when(auth()->user()->hasAnyRole(applicator_positions()), function ($qqq) {
                                    return $qqq->whereHas('districts', function ($q) {
                                        return $q->where('applicator_id', auth()->user()->personel_id);
                                    });
                                })->when(!auth()->user()->hasAnyRole(applicator_positions()), function ($qqq) {

                                return $qqq->whereHas('districts', function ($q) {
                                    return $q->where('personel_id', auth()->user()->personel_id);
                                });
                            });
                        });
                })
                ->where('city_id', $request->city_id)
                ->where("name", "like", "%" . $request->name . "%")
                ->orderBy('name')
                ->get();

            return $this->response('00', 'District list', $cities);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show District', $th->getMessage(), 500);
        }
    }

    /**
     * show District by city id
     *
     * @param Request $request, district_id = District id
     * @return void
     */
    public function findDistrictWithFilter(Request $request)
    {
        try {
            $cities = District::query()
                ->leftJoin('marketing_area_districts', 'indonesia_districts.id', '=', 'marketing_area_districts.district_id')
                ->select('indonesia_districts.*')
                ->where('indonesia_districts.city_id', $request->city_id)
                ->WhereNull('marketing_area_districts.district_id')
                ->orderBy("indonesia_districts.name")
                ->get();

            return $this->response('00', 'District list', $cities);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show District', $th, 500);
        }
    }

    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }

    public function findMultiCity(Request $request)
    {
        try {
            $cities = City::query()
                ->with('province')
                ->whereIn('province_id', $request->province_id)
                ->orderBy('name')
                ->get();

            return $this->response('00', 'Cities list', $cities);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show cities', $th->getMessage(), 500);
        }
    }

    /**
     * show District by city id
     *
     * @param Request $request, district_id = District id
     * @return void
     */
    public function findMultiDistrict(Request $request)
    {
        try {
            $cities = District::query()
                ->with('city', 'city.province')
                ->whereIn('city_id', $request->city_id)
                ->orderBy('name')
                ->get();

            return $this->response('00', 'District list', $cities);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show District', $th, 500);
        }
    }

    /**
     * Indonesia province
     *
     * @return void
     */
    public function provinceFilterById(Request $request)
    {
        try {
            $MarketingAreaDistrict = MarketingAreaDistrict::where("applicator_id", $request->applicator_id)->get()->map(function ($data) {
                return $data->province_id;
            });

            $province = Province::query()
                ->with("city")
                ->when($request->has("province_id"), function ($query) use ($request) {
                    return $query->whereIn("id", $request->province_id);
                })
                ->when($request->has("applicator_id"), function ($query) use ($MarketingAreaDistrict) {
                    return $query->whereIn("id", $MarketingAreaDistrict);
                })
            // ->whereIn("id", $request->province_id)
                ->orderBy('name')
                ->get();

            foreach ($province as $key => $value) {
                $province[$key]->name = Str::title($province[$key]->name);
                foreach ($province[$key]->city as $key_1 => $value_1) {
                    $province[$key]->city[$key_1]->name = Str::title($province[$key]->city[$key_1]->name);
                }
            }
            return $this->response('00', 'Indonesia province', $province);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Indonesia province', $th, 500);
        }
    }

    /**
     * show District by city id
     *
     * @param Request $request, district_id = District id
     * @return void
     */
    public function districtExcludeAreaDistrict(Request $request)
    {
        try {
            $districts = District::query()
                ->excludeAreaDistrict()
                ->orderBy('name')
                ->when($request->has("city_id"), function ($q) use ($request) {
                    return $q->where("indonesia_districts.city_id", $request->city_id);
                })
                ->get();

            foreach ($districts as $key => $value) {
                $districts[$key]->name = Str::title($districts[$key]->name);
            }
            return $this->response('00', 'District list', $districts);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to show District', $th->getMessage(), 500);
        }
    }

    public function allDistrictExcludeDistrictInOtherSUbRegion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "city_id" => "required",
            "sub_region_id" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }
        try {
            $districts = District::query()
                ->with("marketingAreaDistrict")
                ->where("city_id", $request->city_id)
                ->whereDoesntHave('marketingAreaDistrict', function ($query) use ($request) {
                    $query->where('sub_region_id', '!=' ,$request->sub_region_id);
                })
                ->orderBy('name')
                ->get();

            return $this->response('00', 'District list', $districts);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get District', $th->getMessage(), 500);
        }
    }

    public function districtExcludeDistributor(Request $request)
    {
        try {
            $districts = District::where("indonesia_districts.city_id", $request->city_id)
                ->excludeDistributor()
                ->get();

            return $this->response('00', 'District list', $districts);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display District', $th->getMessage(), 500);
        }
    }
}

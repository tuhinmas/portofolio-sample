<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class MarketingAreaFinderController extends Controller
{

    public function region(){
        try {
            $region = DB::table('marketing_area_regions')->orderBy("name")->get();
            return $this->response('00', 'region list', $region);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display region list', $th->getMessage(), 500);
        }
    }
    /**
     * display sub region list
     *
     * @param Request $request, region_id=region id
     * @return void
     */
    public function findSubRegion(Request $request)
    {
        try {
            $sub_region = DB::table('marketing_area_sub_regions')
                ->where('region_id', $request->region_id)
                ->get();

            return $this->response('00', 'Sub region list', $sub_region);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display sub region list', $th->getMessage());
        }
    }

    public function findCities(Request $request)
    {
        try {
            $sub_region = DB::table('marketing_area_cities')
                ->where('sub_region_id', $request->sub_region_id)
                ->get();

            return $this->response('00', 'Marketing area cities list', $sub_region);
        } catch (\Throwable$th) {
            return $this->response('00', 'failed to display marketing area list', $th->getMessage());
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
}

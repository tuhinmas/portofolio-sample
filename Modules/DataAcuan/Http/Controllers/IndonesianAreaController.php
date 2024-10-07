<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Support\Str;
use App\Traits\ChildrenList;
use Illuminate\Http\Request;
use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Routing\Controller;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class IndonesianAreaController extends Controller
{
    use ResponseHandler, SuperVisorCheckV2, MarketingArea;
    use ChildrenList;

    public function index(Request $request)
    {
        try {
            $query = Province::query()
                ->select('id','name')
                ->with([
                    "city" => function($q){
                        return $q->select('id','name', 'province_id');
                    },
                    "city.districtAreas" => function($q){
                        return $q->select('id','name', 'city_id');
                    },
                ])
                ->where("name", 'like', '%' . $request->name . '%')
                ->when($request->has('city'), function($q) use($request){
                    $q->whereHas('city', function($q) use($request){
                        $q->where("name", 'like', '%' . $request->city . '%');
                    });
                })
                ->when($request->has('district'), function($q) use($request){
                    $q->whereHas('city.districtAreas', function($q) use($request){
                        $q->where("name", 'like', '%' . $request->district . '%');
                    });
                })
                ->orderBy('name')
                ->get();

        
            $response = $query;
            return $this->response('00', 'Indonesia Area', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Indonesia', $th->getMessage());
        }
    }

}

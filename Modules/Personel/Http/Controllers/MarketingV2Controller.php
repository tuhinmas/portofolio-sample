<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandler;
use App\Http\Controllers\Controller;
use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use App\Traits\SupervisorCheck;
use Modules\Personel\Entities\Marketing;
use Modules\Personel\Entities\Personel;
use Illuminate\Http\Request;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;

class MarketingV2Controller extends Controller
{
    use ResponseHandler, ChildrenList, SupervisorCheck, MarketingArea;

    public function __construct(
        Personel $marketing,
    ) {
        $this->marketing = $marketing;
    }

    /**
     * list marketing
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
    }

    public function show(Request $request, $id)
    {
        try {
            ini_set('max_execution_time', 1500);
            $personel = $this->marketing
                ->when($request->position, function ($query) {
                    return $query->with("position");
                })
                ->select("id", "name", "position_id")
                ->findOrFail($id);

            /* get region detail by personel include personel */
            $region_id = Region::query()
                ->with("subRegionOnly")
                ->where("personel_id", $personel->id)
                ->get();

            /* get sub region detail to region include marketing each level */
            $sub_region_id = SubRegion::query()
                ->with([
                    "region" => function ($query) {
                        return $query->with("subRegionOnly");
                    },
                ])
                ->where("personel_id", $personel->id)
                ->get();

            /* get district detail to region include marketing each level */
            $district_id = MarketingAreaDistrict::query()
                ->with([
                    "subRegionWithRegion" => function ($QQQ) use ($request, $id) {
                        return $QQQ->with([
                            "region" => function ($query) use ($request, $id) {
                                $query->with("subRegionOnly");
                            }
                        ]);
                    },
                ])
                ->when(!$request->applicator,function($query) use ($personel){
                    return $query->where("personel_id", $personel->id);
                })
                ->when($request->applicator, function($query) use ($personel){
                    return $query->where("applicator_id", $personel->id);
                })
                ->get();


            $region = [];

            if (count($region_id)) {
                foreach ($region_id as $region_data) {
                    array_push($region, $region_data->toArray());
                }
            }
            if (count($sub_region_id)) {
                foreach ($sub_region_id as $sub_region) {
                    array_push($region, $sub_region->region);
                }
            }
            if (count($district_id) > 0) {
                foreach ($district_id as $district) {
                    array_push($region, $district->subRegionWithRegion->region);
                }
            }
            
            // dd($region);

            $personel->region = collect($region)->reject(fn ($region) => !$region)->unique("name")->values();

            return $this->response("00", "success to get marketing detail", $personel);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get marketing detail", $th->getMessage());
        }
    }
}

<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Exports\PaymentMethodExport;
use App\Traits\ResponseHandlerV2;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\BloodRhesus;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrder\Entities\SalesOrder;

class MasterPersonalController extends Controller
{
    use ResponseHandlerV2;

    public function index(Request $request)
    {
        try {
            $response = [
                "rhesus_bloods" => $this->bloodRhesuses([
                    "without_json" => true
                ]),
                "personels" => $this->personels([
                    "without_json" => true
                ]),
                "countries" => $this->countries([
                    "without_json" => true
                ]),
                "positions" => $this->positions([
                    "without_json" => true
                ]),
                "religions" => $this->religions([
                    "without_json" => true
                ]),
                "organisations" => $this->organisations([
                    "without_json" => true
                ]),
                "identity_cards" => $this->identityCards([
                    "without_json" => true
                ]),
                "banks" => $this->banks([
                    "without_json" => true
                ]),
                "bloods" => $this->bloods([
                    "without_json" => true
                ]),
            ];
            return $this->response('00', 'Master Personal', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function resetMaster()
    {
        try{
            Cache::forget('blood_rhesuses');
            Cache::forget('positions');
            Cache::forget('countries');
            Cache::forget('personels');
            Cache::forget('religions');
            Cache::forget('organisations');
            Cache::forget('identity_cards');
            Cache::forget('banks');
            Cache::forget('bloods');
            Cache::forget('regions');
            Cache::forget('sub_regions');
            Cache::forget('districts');

            $response = [
                $this->bloodRhesuses([
                    "without_json" => true
                ]),
                
                $this->positions([
                    "without_json" => true
                ]),
                
                $this->countries([
                    "without_json" => true
                ]),
                
                $this->personels([
                    "without_json" => true
                ]),
                
                $this->religions([
                    "without_json" => true
                ]),
                
                $this->organisations([
                    "without_json" => true
                ]),
                
                $this->identityCards([
                    "without_json" => true
                ]),
                
                $this->banks([
                    "without_json" => true
                ]),
                
                $this->bloods([
                    "without_json" => true
                ]),
                
                $this->regions([
                    "without_json" => true
                ]),
    
                $this->subRegions([
                    "without_json" => true
                ]),
    
                $this->districts([
                    "without_json" => true
                ]),
            ];
        
            return $this->response('00', 'Master Personal', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function bloodRhesuses($params = [])
    {
        try {
            $response = Cache::remember('blood_rhesuses', 60*60, function () {
                return DB::table('blood_rhesuses')
                    ->whereNull("deleted_at")
                    ->orderBy("name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }

            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function positions($params = [])
    {
        try {
            $response = Cache::remember('positions', 60*60, function () {
                return DB::table('positions')->whereNull("deleted_at")
                    ->select("id", "name")
                    ->orderBy("name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }

            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function countries($params = [])
    {
        try {
            $response = Cache::remember('countries', 60*60, function () {
                return DB::table('countries')->whereNull("deleted_at")
                    ->select("id","active","code","label_en")
                    ->orderBy("label_en", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }

            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function personels($params = [])
    {
        try {
            $response = Cache::remember('personels', 30*60, function () {
                return DB::table('personels')->whereNull("personels.deleted_at")
                    ->leftJoin('positions', 'positions.id', '=', 'personels.position_id')->whereNull("positions.deleted_at")
                    ->whereIn("personels.status", [1,2])
                    ->select("personels.id","personels.name", "positions.id as position_id", "positions.name as position_name")
                    ->orderBy("personels.name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function religions($params = [])
    {
        try {
            $response = Cache::remember('religions', 60*60, function () {
                return DB::table('religions')->whereNull("deleted_at")
                    ->select("id","name")
                    ->orderBy("name", "asc")
                    ->get();
            });
            
            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function organisations($params = [])
    {
        try {
            $response = Cache::remember('organisations', 60*60, function () {
                return DB::table('organisations')->whereNull("deleted_at")
                    ->select("id","name")
                    ->orderBy("name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function identityCards($params = [])
    {
        try {
            $response = Cache::remember('identity_cards', 60*60, function () {
                return DB::table('identity_cards')->whereNull("deleted_at")
                    ->select("id","name")
                    ->orderBy("name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function banks($params = [])
    {
        try {
            $response = Cache::remember('banks', 60*60, function () {
                return DB::table('banks')->whereNull("deleted_at")
                    ->select("id","name")
                    ->orderBy("name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function bloods($params = [])
    {
        try {
            $response = Cache::remember('bloods', 60*60, function () {
                return DB::table('bloods')->whereNull("deleted_at")
                    ->select("id","name")
                    ->orderBy('name', "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function regions($params = [])
    {
        try {
            $response = Cache::remember('regions', 60*60, function () {
                return DB::table('marketing_area_regions')->whereNull("marketing_area_regions.deleted_at")
                    ->join("personels", "personels.id", "=", "marketing_area_regions.personel_id")->whereNull("personels.deleted_at")
                    ->select(
                        "marketing_area_regions.id",
                        "marketing_area_regions.name", 
                        "personels.id as personel_id", 
                        "personels.name as personel_name")
                    ->orderBy("marketing_area_regions.name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }

            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function subRegions($params = [])
    {
        try {
            $response = Cache::remember('sub_regions', 60*60, function () {
                return DB::table('marketing_area_sub_regions')->whereNull("marketing_area_sub_regions.deleted_at")
                    ->join("personels", "personels.id", "=", "marketing_area_sub_regions.personel_id")->whereNull("personels.deleted_at")
                    ->join("marketing_area_regions", "marketing_area_regions.id", "=", "marketing_area_sub_regions.region_id")->whereNull("marketing_area_regions.deleted_at")
                    ->select(
                        "marketing_area_sub_regions.id",
                        "marketing_area_sub_regions.name", 
                        "personels.id as personel_id", 
                        "personels.name as personel_name",
                        "marketing_area_regions.id as region_id",
                        "marketing_area_regions.name as region_name"
                    )
                    ->orderBy("marketing_area_sub_regions.name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

    public function districts($params = [])
    {
        try {
            $response = Cache::remember('districts', 60*60, function () {
                return DB::table('marketing_area_districts')->whereNull("marketing_area_districts.deleted_at")
                    ->join("personels", "personels.id", "=", "marketing_area_districts.personel_id")->whereNull("personels.deleted_at")
                    ->join("marketing_area_sub_regions", "marketing_area_sub_regions.id", "=", "marketing_area_districts.sub_region_id")->whereNull("marketing_area_districts.deleted_at")
                    ->join("marketing_area_regions", "marketing_area_regions.id", "=", "marketing_area_sub_regions.region_id")->whereNull("marketing_area_regions.deleted_at")
                    ->join("indonesia_provinces", "indonesia_provinces.id", "=", "marketing_area_districts.province_id")
                    ->join("indonesia_cities", "indonesia_cities.id", "=", "marketing_area_districts.city_id")
                    ->join("indonesia_districts", "indonesia_districts.id", "=", "marketing_area_districts.district_id")
                    ->select(
                        "marketing_area_districts.id",
                        "indonesia_provinces.id as province_id",
                        "indonesia_provinces.name as province_name",
                        "indonesia_cities.id as city_id",
                        "indonesia_cities.name as city_name",
                        "indonesia_districts.id as district_id",
                        "indonesia_districts.name as district_name",
                        "marketing_area_sub_regions.id as sub_region_id",
                        "marketing_area_sub_regions.name as sub_region_name", 
                        "personels.id as personel_id", 
                        "personels.name as personel_name",
                        "marketing_area_regions.id as region_id",
                        "marketing_area_regions.name as region_name"
                    )
                    ->orderBy("indonesia_districts.name", "asc")
                    ->get();
            });

            $params = array_merge($params, request()->all());
            if (!empty($params['sort']) && !empty($params['sort']['direction'])) {
                if ($params['sort']['direction'] == "asc") {
                    $response = $response->sortBy($params['sort']['field'])->values();
                }
    
                if ($params['sort']['direction'] == "desc") {
                    $response = $response->sortByDesc($params['sort']['field'])->values();
                }
            }

            if(!empty($params['without_json'])){
                return $response;
            }
            return $this->response('00', 'Master Personal', $response);
            
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display Master Personal', $th);
        }
    }

}

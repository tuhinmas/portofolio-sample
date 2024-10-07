<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\DB;
use Orion\Concerns\DisablePagination;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealerV2\Entities\DealerV2;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\DataAcuan\Traits\SelfReferenceTrait;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Events\MarketingAreaOnChangeEvent;
use Modules\DataAcuan\Events\ForecastChangeAreaMarketingEvent;
use Modules\DataAcuan\Http\Requests\MarketingAreaDistrictRequest;
use Modules\DataAcuan\Transformers\MarketingAreaDistrictResource;
use Modules\DataAcuan\Jobs\MarketingAreaDistrict\UpdatedDistrictJob;
use Modules\DataAcuan\Actions\MarketingArea\DistrictMarketingChangeAction;
use Modules\DataAcuan\Transformers\MarketingAreaDistrictCollectionResource;
use Modules\DataAcuan\Jobs\MarketingAreaDistrict\SyncRetailerToMarketingJob;
use Modules\DataAcuan\Actions\MarketingArea\UpdateMarketingSubtitutionAction;
use Modules\PlantingCalendar\Entities\PlantingCalendar;

class MarketingAreaDistrictController extends Controller
{
    use DisablePagination, SelfReferenceTrait, ResponseHandler;
    use DisableAuthorization;

    protected $model = MarketingAreaDistrict::class;
    protected $request = MarketingAreaDistrictRequest::class;
    protected $resource = MarketingAreaDistrictResource::class;
    protected $collectionResource = MarketingAreaDistrictCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "district",
            "city",
            "subRegion",
            "subRegion.personel",
            "subRegion.personel.position",
            "subRegion.region",
            "subRegion.region.personel",
            "subRegion.region.personel.position",
            "subRegion.region.provinceRegion",
            "subRegion.city",
            "personel",
            "personel.position",
            "province",
        ];
    }

    public function includes(): array
    {
        return [
            "subRegion.region.personel.position",
            "subRegion.region.provinceRegion",
            "subRegion.personel.position",
            "subRegion.region.personel",
            "subRegion.personel",
            "personel.position",
            "plantingCalendar",
            "subRegion.region",
            "subRegion.city",
            "applicator",
            "subRegion",
            "district",
            "personel",
            "province",
            "city",
        ];
    }

    /**
     * scopes list
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [
            "districtSupervisorList",
            "districtSubordinateList",
            "districtListSupervisor",
            "sortByDistrictName",
        ];
    }
    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'province_id',
            'city_id',
            'district_id',
            'personel_id',
            'sub_region_id',
            'district.name',
            'personel.name',
            "created_at",
            "updated_at",
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'province_id',
            'city_id',
            'district_id',
            'personel_id',
            'sub_region_id',
            'district.name',
            'personel.name',
            'province_id',
            'city.name',
            "created_at",
            "updated_at",
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        $query->when($request->has('scope_applicator'), function ($q) {
            return $q->where('applicator_id', auth()->user()->personel_id);
        });

        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->has("limit")) {
            return $query->paginate($request->limit ? $request->limit : 15);
        } else {
            return $query->get();
        }
    }

    /**
     * perform update
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     * @return void
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $new_attributes[] = [];
        $attributes["personel_id"] = $this->subRegion($request->sub_region_id);
        if ($request->has("personel_id")) {
            $attributes["personel_id"] = $request->personel_id;
        }
        foreach ($request->district_id as $key => $dist) {
            $new_attributes[$key]["province_id"] = $attributes["province_id"];
            $new_attributes[$key]["city_id"] = $attributes["city_id"];
            $new_attributes[$key]["sub_region_id"] = $attributes["sub_region_id"];
            $new_attributes[$key]["personel_id"] = $attributes["personel_id"];
            $new_attributes[$key]["district_id"] = $dist;
        }

        foreach ($new_attributes as $key => $value) {
            if ($key == 0) {
                $entity->fill($new_attributes[$key]);
                $entity->save();
            } else {
                $entity = new MarketingAreaDistrict;
                $entity->fill($new_attributes[$key]);
                $entity->save();
            }
        }
    }

    public function afterStore(Request $request, $model)
    {
        if ($model->applicator_id && $model->personel_id) {
            $applicator = Personel::find($model->applicator_id);

            /**
             * applicator supervisor is according marketing
             * on this area, if marketing area change,
             * and not applicator supervisor, then
             * applicator will revoked from this
             * area
             */
            if ($applicator) {
                if ($applicator->supervisor_id != $model->personel_id) {
                    $model->applicator_id = null;
                }
            }
        }

        $this->syncMarketingOnAllStores();
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    protected function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $replaced_personel = $entity->personel_id;
        $entity->fill($attributes);
        $entity->save();

        $personel_id = $entity->personel_id;

        // if ($request->has("personel_id")) {
        //     $personel_id = $request->personel_id;
        //     $this->personelUpdateSupervisor($personel_id, $entity->sub_region_id);
        // }

        /* update marketing freeze history and subtitution */
        if ($replaced_personel != $personel_id) {
            UpdatedDistrictJob::dispatch($entity, $replaced_personel, auth()->user());
            // $log_freeze_action = new UpdateMarketingSubtitutionAction();
            // $log_freeze_action($replaced_personel, $personel_id);

        }
    }

    public function afterUpdate(Request $request, $model)
    {
        // if (!$model->personel_id) {
        //     $model->applicator_id = null;
        //     $model->save();
        // }

        // if ($model->applicator_id) {
        //     $applicator = Personel::find($model->applicator_id);

        //     /**
        //      * applicator supervisor is according marketing
        //      * on this area, if marketing area change,
        //      * and not applicator supervisor, then
        //      * applicator will revoked from this
        //      * area
        //      */
        //     if ($applicator) {
        //         if ($applicator->supervisor_id != $model->personel_id) {
        //             $model->applicator_id = null;
        //         }
        //     }
        // }

        /**
         * marketing change all dealers will take over by new marketing
         * applicator rule affected
         */
        // $sales_order_take_over = MarketingAreaOnChangeEvent::dispatch($model);
        // $model->save();
    }

    public function beforeUpdate(Request $request, $model)
    {
        // $findMarketingAreaDistrict = MarketingAreaDistrict::find($model->id);
        // if ($findMarketingAreaDistrict->personel_id != $request->personel_id) {
        //     $personelIdChange[] = [
        //         'marketing_area_district' => $findMarketingAreaDistrict->id,
        //         'personel_id_before' => $findMarketingAreaDistrict->personel_id,
        //         'personel_id_after' => $request->personel_id,
        //     ];

        //     ForecastChangeAreaMarketingEvent::dispatch($personelIdChange);
        // }

    }

    public function beforeBatchUpdate(Request $request)
    {
        // if (array_key_exists("resources", $request->all())) {
        //     $personelIdChange = [];
        //     foreach ($request["resources"] as $key => $resource) {
        //         $findMarketingAreaDistrict = MarketingAreaDistrict::find($key);
        //         if ($findMarketingAreaDistrict->personel_id != $resource['personel_id']) {
        //             $personelIdChange[] = [
        //                 'marketing_area_district' => $findMarketingAreaDistrict->id,
        //                 'personel_id_before' => $findMarketingAreaDistrict->personel_id,
        //                 'personel_id_after' => $resource['personel_id'],
        //             ];
        //         }
        //     }
        //     ForecastChangeAreaMarketingEvent::dispatch($personelIdChange);
        // }
    }

    public function afterBatchUpdate(Request $request, $entities)
    {
        // foreach ($entities as $entity) {
        //     if ($entity->applicator_id && $entity->personel_id) {
        //         $applicator = Personel::find($entity->applicator_id);
        //         $applicator = DB::table('personels')
        //             ->whereNull("deleted_at")
        //             ->where("id", $entity->applicator_id)
        //             ->first();

        //         /**
        //          * applicator supervisor is according marketing
        //          * on this area, if marketing area change,
        //          * and not applicator supervisor, then
        //          * applicator will revoked from this
        //          * area
        //          */
        //         if ($applicator) {
        //             if ($applicator->supervisor_id != $entity->personel_id) {
        //                 $entity->applicator_id = null;
        //                 $entity->save();
        //             }
        //         }
        //     }
        // }
    }

    /**
     * sync district
     *
     * @param Request $request
     * @return void
     */
    public function syncDistrict(Request $request)
    {
        try {
            $districts = [];
            foreach ($request->district_id as $district) {
                $district = MarketingAreaDistrict::firstOrCreate([
                    "district_id" => $district,
                ], [
                    "province_id" => $request->province_id,
                    "city_id" => $request->city_id,
                    "sub_region_id" => $request->sub_region_id,
                    "personel_id" => $this->subRegion($request->sub_region_id),
                ]);
                array_push($districts, $district);
            }
            $detach = $this->detachDistrict($request);
            $this->syncMarketingOnAllStores();
            return $this->response("00", "success sync district", $districts);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to add district", $th->getMessage(), 500);
        }
    }

    /**
     * syncronize district
     *
     * @param [type] $request
     * @return void
     */
    public function detachDistrict($request)
    {
        try {
            /* get district to delete */
            $district_to_update =  MarketingAreaDistrict::where("city_id", $request->city_id)
                ->where("sub_region_id", $request->sub_region_id)
                ->whereNotIn("district_id", $request->district_id)
                ->get()
                ->pluck("district_id");
    
            $districts = MarketingAreaDistrict::where("city_id", $request->city_id)
                ->where("sub_region_id", $request->sub_region_id)
                ->whereNotIn("district_id", $request->district_id)
                ->get()
                ->each(function($q){
                    $q->delete();
                });
    
            MarketingAreaDistrict::whereIn("district_id", $request->district_id)
                ->get()
                ->each(function($q){
                    $lastDistrict = MarketingAreaDistrict::withTrashed()
                        ->where('district_id', $q->district_id)
                        ->orderBy('deleted_at', 'desc')
                        ->first();

                    $plantCal = PlantingCalendar::where('area_id', $lastDistrict->id)->get()->pluck('id')->toArray();

                    if($plantCal) {
                        PlantingCalendar::whereIn('id', $plantCal)->update([
                            'area_id' => $q->id,
                        ]);
                    }
                });

            /* dealer on marketing area that has been deleted */
            $address = DB::table('address_with_details')
                ->whereNull("deleted_at")
                ->whereIn("district_id", $district_to_update)
                ->where("type", "dealer")
                ->get()
                ->pluck("parent_id")
                ->toArray();

            /* update dealer */
            $dealers = DealerV2::query()
                ->whereIn("id", $address)
                ->where("is_distributor", "0")
                ->update([
                    "personel_id" => null,
                ]);

            return $districts;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function subRegion($sub_region_id)
    {
        $personel_id_rmc = DB::table('marketing_area_sub_regions')->where("id", $sub_region_id)->first()->personel_id;
        return $personel_id_rmc;
    }

    /**
     * take over all dealer and sub oleh by replacement personel
     *
     * @param [type] $replaced_personel
     * @param [type] $replacement_personel
     * @param [type] $address
     * @return void
     */
    public function personelUpdateOnMarketingChange($replaced_personel, $replacement_personel, $address)
    {
        $list_agency_level = DB::table('agency_levels')->whereIn('name', ['D1', 'D2'])->pluck('id');
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "L1")->first();

        $personel_dealer = DealerV2::query()
            ->where("personel_id", $replaced_personel)
            ->whereIn("id", $address)
            ->whereNotIn('agency_level_id', $list_agency_level)
            ->update([
                "personel_id" => $replacement_personel,
                "status_fee" => $status_fee ? $status_fee->id : null,
            ]);

        $personel_sub_dealer = SubDealer::query()
            ->where("personel_id", $replaced_personel)
            ->whereIn("id", $address)
            ->update([
                "personel_id" => $replacement_personel,
                "status_fee" => $status_fee ? $status_fee->id : null,
            ]);

        return $personel_dealer;
    }

    /**
     * update supervisor of marketing if there has no supervisor
     *
     * @param [type] $personel_id
     * @param [type] $sub_region_id
     * @return void
     */
    public function personelUpdateSupervisor($personel_id, $sub_region_id)
    {
        $personel = Personel::query()
            ->where("id", $personel_id)
            ->first();

        $sub_region_Personel = SubRegion::findOrFail($sub_region_id);
        if ($personel) {

            /* update supervisor if personel on sub region is not same with personel on district */
            if ($personel->supervisor_id !== $sub_region_Personel->personel_id) {
                $personel->supervisor_id = $sub_region_Personel->personel_id;
                $personel->save();
            }
        }
    }

    /**
     * sync marketing on dealer / sub dealer
     */
    public function syncMarketingOnAllStores()
    {
        try {
            SyncRetailerToMarketingJob::dispatch();

            return $this->response("00", "success", "all stores has been synchronized");
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage(), 500);
        }
    }

    /**
     * sync marketing on dealer D1
     */
    public function syncMarketingDistributorD1()
    {
        try {
            $agency_level_list = DB::table('agency_levels')
                ->where("name", "D1")
                ->get()
                ->pluck("id")
                ->toArray();

            /* sync dealer D1 marketing */
            $dealer = DealerV2::query()
                ->with([
                    "areaDistrictDealer",
                ])
                ->whereHas("areaDistrictDealer", function ($QQQ) {
                    return $QQQ->whereHas("subRegion", function ($QQQ) {
                        return $QQQ->whereHas("region");
                    });
                })
                ->whereIn("agency_level_id", $agency_level_list)
                ->whereHas("addressDetail", function ($QQQ) {
                    return $QQQ->where("type", "dealer");
                })
                ->get()
                ->each(function ($dealer) {
                    $dealer->personel_id = $dealer->areaDistrictDealer->subRegion->region->personel_id;
                    $dealer->save();
                });

            return $this->response("00", "success", "dealer D1 has been synchronized");
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage(), 500);
        }
    }

    /**
     * sync marketing on dealer D2
     */
    public function syncMarketingDistributorD2()
    {
        try {
            $agency_level_list = DB::table('agency_levels')
                ->where("name", "D2")
                ->get()
                ->pluck("id")
                ->toArray();

            /* sync dealer D1 marketing */
            $dealer = DealerV2::query()
                ->with([
                    "areaDistrictDealer",
                ])
                ->whereHas("areaDistrictDealer", function ($QQQ) {
                    return $QQQ->whereHas("subRegion");
                })
                ->whereIn("agency_level_id", $agency_level_list)
                ->whereHas("addressDetail", function ($QQQ) {
                    return $QQQ->where("type", "dealer");
                })
                ->get()
                ->each(function ($dealer) {
                    $dealer->personel_id = $dealer->areaDistrictDealer->subRegion->personel_id;
                    $dealer->save();
                });

            return $this->response("00", "success", "dealer D2 has been synchronized");
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage(), 500);
        }
    }
}

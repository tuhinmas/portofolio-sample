<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\District;
use Modules\DataAcuan\Entities\Region;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Events\ForecastChangeAreaMarketingEvent;
use Modules\DataAcuan\Http\Requests\MarketingAreaRegionRequest;
use Modules\DataAcuan\Transformers\MarketingAreaRegionResource;
use Modules\DataAcuan\Actions\MarketingArea\RegionMarketingChangeAction;
use Modules\DataAcuan\Transformers\MarketingAreaRegionCollectionResource;

class MarketingAreaRegionController extends Controller
{
    use ResponseHandler, MarketingArea;
    use DisableAuthorization;

    protected $model = Region::class;
    protected $request = MarketingAreaRegionRequest::class;
    protected $resource = MarketingAreaRegionResource::class;
    protected $collectionResource = MarketingAreaRegionCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "provinceRegion",
            "subRegion",
            "subRegion.city",
            "subRegion.city.city",
            "subRegion.personel",
            "subRegion.personel.position",
            "personel", "personel.position",
        ];
    }

    public function includes(): array
    {
        return [
            "subRegion.district.district",
            "subRegion.district",
        ];
    }

    public function exposedScopes(): array
    {
        return [
            "asSuperVisor",
            "name",
            "personelBranch",
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return ['id', 'name', 'mdm', 'subRegion.name', 'personel_id', 'provinceRegion.name'];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return ['id', 'name', 'subRegion.name', 'personel_id', 'provinceRegion.name', 'subRegion.city.city.name'];
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
            'name',
            'personel_id',
            'subRegion.name',
            'created_at',
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
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
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->has("disabled_pagination")) {

            // temukan dulu list district by personel
            if (is_array($request->personel_id)) {
                $personel_id = $request->personel_id;
            } else {
                $personel_id = [$request->personel_id];
            }
            return $query
                ->when($request->has("personel_id"), function ($query) use ($request, $personel_id) {
                    $all_district = $this->districtListMarketingList($personel_id);
                    $list_district_id = $all_district;

                    $list_region = MarketingAreaDistrict::whereIn("id", $list_district_id)->with("subRegionWithRegion")->get()->pluck('subRegionWithRegion.region_id')->unique();
                    return $query->whereIn("id", $list_region);
                })
                ->when($request->has("region_id"), function ($query) use ($request) {
                    return $query->whereIn("id", $request->region_id);
                })
                ->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        foreach ($request->parent_id as $parent_id) {
            $entity->name = $request->name;
            $entity->target = $request->target;
            $entity->personel_id = $request->personel_id;
            $entity->save();
        }
        $entity->provinceRegion()->attach($request->parent_id);
    }

    /**
     * Builds Eloquent query for fetching entity(-ies).
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildDestroyFetchQuery(Request $request, array $requestedRelations, bool $softDeletes): Builder
    {
        $query = parent::buildDestroyFetchQuery($request, $requestedRelations, $softDeletes);
        return $query;
    }

    /**
     * Runs the given query for fetching entity.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runDestroyFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    protected function performDestroy(Model $entity): void
    {
        $entity->provinceRegion()->detach();
        $entity->delete();
    }

    protected function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entity.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function beforeUpdate(Request $request, $model)
    {
        $findMarketingAreaDistrict = Region::find($model->id);
        if ($findMarketingAreaDistrict->personel_id != $request->personel_id) {
            $findPersonelInDistrict = MarketingAreaDistrict::where('personel_id', $findMarketingAreaDistrict->personel_id)->get();
            $personelIdChange = [];
            foreach ($findPersonelInDistrict as $key => $value) {
                $personelIdChange[] = [
                    'marketing_area_district' => $value->id,
                    'personel_id_before' => $findMarketingAreaDistrict->personel_id,
                    'personel_id_after' => $request->personel_id
                ];
            }

            if (count($personelIdChange) > 0) {
                ForecastChangeAreaMarketingEvent::dispatch($personelIdChange);
            }
        }

    }

    protected function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        unset($attributes["parent_id"]);
        $entity->fill($attributes);
        $entity->save();
    }

    public function checkRegionProvince($request, $region_id)
    {
        $districts = 0;
        $sub_region_id = SubRegion::where("region_id", $region_id)->get()->pluck("id");

        $districts = MarketingAreaDistrict::query()
            ->whereIn("sub_region_id", $sub_region_id)
            ->whereNotIn("province_id", $request->parent_id)
            ->count();

        return $districts;
    }

    public function updateRegion(Request $request, $id)
    {
        try {
            $region = Region::findOrFail($id);
            $replaced_personel = $region->personel_id;

            $first_target = $region->target;
            $new_request = $request->all();
            unset($new_request["parent_id"]);

            foreach ($new_request as $key => $value) {
                $region[$key] = $value;
            }
            $region->save();

            /* update child target if region target updated */
            // if ($first_target !== $region->target) {
            //     $this->updateTargetEvent($id, $request->target);
            // }

            /* check if province has child */
            $province_check = 0;
            if ($request->has("parent_id")) {
                $province_check = $this->checkRegionProvince($request, $id);
            }

            /* marketing change all dealers will take over by new marketing */
            $replacement_personel = $region->personel_id;

            /* get all district on this sub region */
            // $district_id = $this->districtListByAreaId($region->id);

            // /* get dealer by dstrict address */
            // $dealer_id = DB::table('address_with_details')
            //     ->whereNull("deleted_at")
            //     ->where("district_id", $district_id)
            //     ->where("type", "dealer")
            //     ->get()
            //     ->pluck("parent_id")
            //     ->toArray();

            /* update marketing on district list above if there any personel change on this sub region */
            if ($replaced_personel !== $replacement_personel) {
                (new RegionMarketingChangeAction)($region, $replaced_personel);
                
                // $this->personelUpdateOnMarketingChange($replaced_personel, $replacement_personel, $dealer_id);
                // $this->districtTakeOverByReplacementPersonel($replaced_personel, $replacement_personel, $region->id);
                // $this->personelUpdateSupervisor($id);
            }

            /* province cannot be deleted if there child exist */
            if ($province_check > 0) {
                return $this->response("01", "failed on update region", "Provinsi masih terikat pada subregion", 422);
            } else {
                if ($request->has("parent_id")) {
                    $region->provinceRegion()->sync($request->parent_id);
                }
            }

            $region = $region->where("id", $id)->with("provinceRegion")->first();
            return $this->response("00", "success on update region", $region);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to update region",[
                "message" =>  $th->getMessage(),
                "line" =>  $th->getLine(),
                "file" =>  $th->getFile(),
                "trace" =>  $th->getTrace()
            ]);
        }
    }

    /**
     * update child target if parent target changes
     *
     * @param [type] $region_id
     * @param [type] $target
     * @return void
     */
    public function updateTargetEvent($region_id, $target)
    {
        $sub_region = SubRegion::query()
            ->where("region_id", $region_id)
            ->where("target", "<", $target)
            ->update([
                "target" => $target,
            ]);

        return $sub_region;
    }

    /**
     * take over all dealer and sub oleh by replacement personel
     *
     * @param [type] $replaced_personel
     * @param [type] $replacement_personel
     * @param [type] $address
     * @return void
     */
    public function personelUpdateOnMarketingChange($replaced_personel, $replacement_personel, $dealer_id)
    {
        $personel_dealer = DealerV2::query()
            ->where("personel_id", $replaced_personel)
            ->whereIn("id", $dealer_id)
            ->update([
                "personel_id" => $replacement_personel,
            ]);

        $personel_sub_dealer = SubDealer::query()
            ->where("personel_id", $replaced_personel)
            ->whereIn("id", $dealer_id)
            ->update([
                "personel_id" => $replacement_personel,
            ]);

        return $personel_dealer;
    }

    /**
     * district take over by personel
     *
     * @param [type] $replaced_personel
     * @param [type] $replacement_personel
     * @param [type] $id
     * @return void
     */
    public function districtTakeOverByReplacementPersonel($replaced_personel, $replacement_personel, $id)
    {
        $sub_region_id = SubRegion::query()
            ->where("personel_id", $replaced_personel)
            ->where("region_id", $id)
            ->get()
            ->pluck("id")
            ->toArray();

        $district_list = [];
        foreach ($sub_region_id as $sub_region) {
            $personel_district = $this->districtListByAreaId($sub_region);
            foreach ($personel_district as $district) {
                array_push($district_list, $district);
            }
        }
        $district_list = array_unique($district_list);

        /**
         * take over sub region if mdm also in sub region
         */
        $sub_region = SubRegion::query()
            ->where("personel_id", $replaced_personel)
            ->where("region_id", $id)
            ->update([
                "personel_id" => $replacement_personel,
            ]);

        /**
         * take over district if mdm also in district
         */
        $marketing_area_district = MarketingAreaDistrict::query()
            ->whereIn("sub_region_id", $sub_region_id)
            ->whereIn("district_id", $district_list)
            ->where("personel_id", $replaced_personel)
            ->update([
                "personel_id" => $replacement_personel,
            ]);
        return $marketing_area_district;
    }

    /**
     * update supervisor of marketing if there has no supervisor
     *
     * @param [type] $personel_id
     * @param [type] $sub_region_id
     * @return void
     */
    public function personelUpdateSupervisor($region_id)
    {
        /* get region detail */
        $region_detail = Region::find($region_id);

        /* get personel with MM position */
        $personel_mm = Personel::query()
            ->where("name", "Budi Kartika")
            ->first();

        /* compare personel in region and personel mm */
        if ($region_detail->personel_id !== $personel_mm->id) {

            /**
             * if personel in region is not same with personel mm
             * update supervisor personel region with
             * personel mm
             */
            $personel = Personel::query()
                ->where("id", $region_detail->personel_id)
                ->update([
                    "supervisor_id" => $personel_mm->id,
                ]);
        }

        /* get a ll personel in sub region */
        $personel_list_in_sub_region = SubRegion::query()
            ->where("region_id", $region_id)
            ->whereNotNull("personel_id")
            ->get()
            ->pluck("personel_id")
            ->toArray();

        /* update all personel under region with new supervisor */
        $personel_update_supervisor = Personel::query()
            ->whereIn("id", $personel_list_in_sub_region)
            ->where("id", "!=", $region_detail->personel_id)
            ->update([
                "supervisor_id" => $region_detail->personel_id,
            ]);
    }
}

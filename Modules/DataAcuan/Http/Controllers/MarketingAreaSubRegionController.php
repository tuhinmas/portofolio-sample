<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Actions\MarketingArea\SubRegionMarketingChangeAction;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\DataAcuan\Events\ForecastChangeAreaMarketingEvent;
use Modules\DataAcuan\Http\Requests\MarketingAreaSubRegionRequest;
use Modules\DataAcuan\Transformers\MarketingAreaSubRegionCollectionResource;
use Modules\DataAcuan\Transformers\MarketingAreaSubRegionResource;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class MarketingAreaSubRegionController extends Controller
{
    use ResponseHandler, MarketingArea;
    use DisableAuthorization;

    protected $model = SubRegion::class;
    protected $request = MarketingAreaSubRegionRequest::class;
    protected $resource = MarketingAreaSubRegionResource::class;
    protected $collectionResource = MarketingAreaSubRegionCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            'region',
            'city',
            'region.provinceRegion',
            'region.personel.position',
            'city.district',
            'city.district.district',
            "city.personel",
            'personel.position',
        ];
    }

    public function includes(): array
    {
        return [
            "eventArea",
            "district.district",
        ];
    }

    public function exposedScopes(): array
    {
        return [
            "personelBranch",
            "asSuperVisor",
            "byEvent",
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
            'name',
            'personel_id',
            'region_id',
            'personel.name',
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
            'name',
            'region_id',
            'personel_id',
            'created_at',
            'personel.name',
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

        // temukan dulu list district by personel
        $validation = Validator::make($request->all(), [
            "personel_id" => "array",
        ]);

        if ($validation->fails()) {
            return $this->response("04", "invalid data send", $validation->errors());
        }

        if ($request->has("disabled_pagination")) {
            return $query
                ->when($request->has("sub_region_id"), function ($query) use ($request) {
                    return $query->whereIn("id", $request->sub_region_id);
                })
                ->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
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

    public function beforeUpdate(Request $request, $model)
    {
        $findMarketingAreaDistrict = SubRegion::find($model->id);
        if ($findMarketingAreaDistrict->personel_id != $request->personel_id) {
            $findPersonelInDistrict = MarketingAreaDistrict::where('personel_id', $findMarketingAreaDistrict->personel_id)->get();
            $personelIdChange = [];
            foreach ($findPersonelInDistrict as $key => $value) {
                $personelIdChange[] = [
                    'marketing_area_district' => $value->id,
                    'personel_id_before' => $findMarketingAreaDistrict->personel_id,
                    'personel_id_after' => $request->personel_id,
                ];
            }

            if (count($personelIdChange) > 0) {
                ForecastChangeAreaMarketingEvent::dispatch($personelIdChange);
            }

        }

    }
    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $replaced_personel = $entity->personel_id;
        $entity->fill($attributes);
        $entity->save();
        
        /* update marketing on district list above if there any personel change on this sub region */
        if ($replaced_personel !== $entity->personel_id) {
            (new SubRegionMarketingChangeAction)($entity, $replaced_personel);
        }

    }

    public function marketingAreaSubRegionDelete($id)
    {
        $subregion = SubRegion::query()
            ->where('id', $id)
            ->whereHas('district', function ($QQQ) {
                return $QQQ->whereHas('personel');
            })
            ->first();

        if ($subregion) {
            return $this->response("01", "Can't Delete", "This Sub Region have marketing");
        } else {
            $subregion = SubRegion::findOrFail($id);
            $subregion->delete();
            return $this->response("00", "Delete Success", $subregion);
        }

    }

    public function syncCity(Request $request)
    {
        try {
            $sub_region = SubRegion::findOrFail($request->sub_region_id);
            $sub_region->attachCity()->sync($request->city_id);
            $sub_region = SubRegion::where("id", $request->sub_region_id)->with("city")->first();

            $districts = $this->syncDistrict($request->sub_region_id, $request->city_id);

            return $this->response("00", "success, add city to sub region", $sub_region);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to add city to sub region", $th->getMessage());
        }
    }

    public function syncDistrict($sub_region, $city)
    {
        try {
            $districts = MarketingAreaDistrict::query()
                ->where("sub_region_id", $sub_region)
                ->whereNotIn("city_id", $city)
                ->delete();
            return $districts;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    /**
     * personel target update on parent area target changes
     *
     * @param [type] $sub_region_id
     * @param [type] $target
     * @return void
     */
    public function personelTargetUpdate($sub_region_id, $target)
    {
        $personel_id = DB::table('marketing_area_districts')
            ->whereNull("deleted_at")
            ->where("sub_region_id", $sub_region_id)
            ->get()
            ->pluck("personel_id");

        $personel_id = collect($personel_id)->unique()->toArray();

        /**
         * update target marketing if
         * target less than new target
         */
        $personel = Personel::query()
            ->whereIn("id", $personel_id)
            ->where("target", "<", $target)
            ->update([
                "target" => $target,
            ]);

        return $personel;
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
        $position_rmc = DB::table('positions')
            ->whereNull("deleted_at")
            ->whereIn("name", position_rmc())
            ->first();

        $position_mdm = DB::table('positions')
            ->whereNull("deleted_at")
            ->whereIn("name", position_mdm())
            ->first();

        $personel_dealer = DealerV2::query()
            ->where("personel_id", $replaced_personel)
            ->whereIn("id", $dealer_id)
            ->get()
            ->each(function ($dealer) use ($replacement_personel) {
                $dealer->personel_id = $replacement_personel;
                $dealer->save();
            });

        $personel_sub_dealer = SubDealer::query()
            ->where("personel_id", $replaced_personel)
            ->whereIn("id", $dealer_id)
            ->each(function ($sub_dealer) use ($replacement_personel) {
                $sub_dealer->personel_id = $replacement_personel;
                $sub_dealer->save();
            });

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
        $personel_district = $this->districtListMarketing($replaced_personel);
        $marketing_area_district = MarketingAreaDistrict::query()
            ->where("sub_region_id", $id)
            ->whereIn("district_id", $personel_district)
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
    public function personelUpdateSupervisor($sub_region_id)
    {
        /* get sub region detail */
        $sub_region_detail = SubRegion::findOrFail($sub_region_id);

        /* get region detail */
        $region_detail = Region::find($sub_region_detail->region_id);

        /* compare personel in sub region and region */
        if ($sub_region_detail->personel_id !== $region_detail->personel_id) {

            /**
             * if personel in sub region is not same with personel in region
             * update supervisor personel sub region with personel in region
             */
            $personel = Personel::query()
                ->where("id", $sub_region_detail->personel_id)
                ->update([
                    "supervisor_id" => $region_detail->personel_id,
                ]);
        }

        /* get a ll personel in district */
        $personel_list_in_district = MarketingAreaDistrict::query()
            ->where("sub_region_id", $sub_region_id)
            ->whereNotNull("personel_id")
            ->get();

        /**
         * if in this sub region there is marketing has more than one area
         * in another sub region, all area which handled by its marketing
         * will be revoked from all area in this sub region, and these
         * area will handled by new RMC
         */

        /* marketing area exclude RMC */
        $marketing_area_exclude_rmc = $personel_list_in_district
            ->reject(function ($district) use ($sub_region_detail) {
                return $district->personel_id == $sub_region_detail->personel_id;
            })
            ->pluck("personel_id")
            ->toArray();

        /**
         * district in another sub region which handled by marketing
         * has more than two area in diffrent sub region and
         * diffrent RMC
         */
        $district_list_in_another_sub_region = MarketingAreaDistrict::query()
            ->whereIn("personel_id", $marketing_area_exclude_rmc)
            ->whereHas("subRegionWithRegion", function ($QQQ) use ($sub_region_detail) {
                return $QQQ->where("personel_id", "!=", $sub_region_detail->personel_id);
            })
            ->where("sub_region_id", "!=", $sub_region_id)
            ->get();

        if ($district_list_in_another_sub_region->count() > 0) {

            /* update marketing with rmc becouse of the reason already mentioned */
            $marketing_district_updated = MarketingAreaDistrict::query()
                ->whereIn("personel_id", $district_list_in_another_sub_region->pluck("personel_id")->toArray())
                ->where("sub_region_id", $sub_region_id)
                ->update([
                    "personel_id" => $sub_region_detail->personel_id,
                ]);
        }

        /* update all personel under subregion with new supervisor */
        $personel_update_supervisor = Personel::query()
            ->whereIn("id", $personel_list_in_district->pluck("personel_id")->toArray())
            ->whereNotIn("id", $district_list_in_another_sub_region->pluck("personel_id")->toArray())
            ->where("id", "!=", $sub_region_detail->personel_id)
            ->update([
                "supervisor_id" => $sub_region_detail->personel_id,
            ]);
    }
}

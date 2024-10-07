<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\MarketingAreaCity;
use Modules\DataAcuan\Http\Requests\MarketingAreaCityRequest;
use Modules\DataAcuan\Transformers\MarketingAreaCityResource;
use Modules\DataAcuan\Transformers\MarketingAreaCityCollectionResource;

class MarketingAreaCityController extends Controller
{
    use DisableAuthorization;

    protected $model = MarketingAreaCity::class;
    protected $request = MarketingAreaCityRequest::class;
    protected $resource = MarketingAreaCityResource::class;
    protected $collectionResource = MarketingAreaCityCollectionResource::class;

    /**
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "subRegion",
            "subRegion.region",
            "district",
            "district.district",
            "district.personel",
            'personel',
            "city"
        ];
    }

    public function exposedScopes(): array
    {
        return [
            "personelBranch",
            "asSuperVisor",
            "supervisor"
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
            'city.name'
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
            'city.name'
        ];
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
        // $marketing_area_city = MarketingAreaCity::where("sub_region_id", $request->sub_region_id)->get();
        // $marketing_area_city_count = count($marketing_area_city);

        // /* synchronize data */
        // if (!$request->city_id) {
        //     foreach ($marketing_area_city as $city) {
        //         $city->delete();
        //     }
        // }
        // else {
        //     $city = DB::table('indonesia_cities')->where("id", $request->city_id[0])->first();
        //     foreach ($marketing_area_city as $city) {
        //         if (!in_array($city->city_id, $request->city_id)) {
        //             $city->delete();
        //         }
        //     }
        // }

        /* mapping data */
        $new_attributes[] = [];
        foreach ($request->city_id as $key => $city_id) {
            $parent = DB::table('indonesia_cities')->where("id", $city_id)->first();
            $new_attributes[$key]["name"] = $parent->name;
            $new_attributes[$key]["sub_region_id"] = $attributes["sub_region_id"];
            $new_attributes[$key]["city_id"] = $city_id;
        }

        foreach ($new_attributes as $key => $value) {
            if ($key == 0) {
                $entity->fill($new_attributes[$key]);
                $entity->save();
            } else {
                $entity = new MarketingAreaCity;
                $entity->fill($new_attributes[$key]);
                $entity->save();
            }
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
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        // $query->where("sub_region_id", $request->sub_region_id);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return MarketingAreaCity::findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $marketing_area_city = MarketingAreaCity::where("sub_region_id", $request->sub_region_id)->get();
        $citycount = count($marketing_area_city);
        if (count($request->city_id) >= $citycount) {
            foreach ($request->city_id as $city) {
                $parent = DB::table('indonesia_cities')->where("id", $city)->first();
                MarketingAreaCity::firstOrCreate([
                    "name" => $parent->name,
                    "city_id" => $city,
                    "sub_region_id" => $request->sub_region_id,
                ]);
            }
        } else {
            foreach ($marketing_area_city as $city) {
                if (!in_array($city->city_id, $request->city_id)) {
                    $city->delete();
                }
            }
        }
    }
}

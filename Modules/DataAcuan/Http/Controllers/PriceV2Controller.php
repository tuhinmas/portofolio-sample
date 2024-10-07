<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Price;
use Modules\DataAcuan\Entities\PriceHistory;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Http\Requests\PriceV2Request;
use Modules\DataAcuan\Transformers\PriceV2CollectionResource;
use Modules\DataAcuan\Transformers\PriceV2Resource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class PriceV2Controller extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = Price::class;
    protected $request = PriceV2Request::class;
    protected $resource = PriceV2Resource::class;
    protected $collectionResource = PriceV2CollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "product_id",
            "agency_level_id",
            "het",
            "price",
            "minimum_order",
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            "product_id",
            "agency_level_id",
            "het",
            "price",
            "minimum_order",
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "product_id",
            "agency_level_id",
            "het",
            "price",
            "minimum_order",
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
        return parent::buildIndexFetchQuery($request, $requestedRelations);
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
        if ($request->has("disabled_pagination") && $request->disable_pagination == true) {
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function beforeBatchStore(Request $request)
    {

        $unchangedData = [];

        foreach ($request->resources as $resourceData) {
            $existingResource = price::where('price', $resourceData['price'])
                ->where('valid_from', $resourceData['valid_from'])
                ->where('het', $resourceData['het'])
                ->where('product_id', $resourceData['product_id'])
                ->where('minimum_order', $resourceData['minimum_order'])
                ->first();

            if ($existingResource) {
                $unchangedData[] = $existingResource;
            }
        }

        if (count($unchangedData) >= 5) {
            // Data yang sama ditemukan, berikan pemberitahuan
            return $this->response("04", "invalid data send", [
                "messages" => "Data tidak disimpan",
                "data" => $unchangedData,
            ], 422);
        }

        // if ($request->has("resources")) {
        //     $priceProduct = price::where('product_id', $resourceData['product_id'])
        //         ->get();
        //     $priceProductHistoryLast = PriceHistory::select("id", "segmen")->latest()->first();

        //     /** store to history price */
        //     foreach ($priceProduct as $data) {
        //         $historyprice = new PriceHistory();
        //         $historyprice->price = $data->price;
        //         $historyprice->segmen = $priceProductHistoryLast ? $priceProductHistoryLast->segmen + 1 : 0;
        //         $historyprice->valid_from = $data->valid_from;
        //         $historyprice->agency_level_id = $data->agency_level_id;
        //         $historyprice->het = $data->het;
        //         $historyprice->minimum_order = $data->minimum_order;
        //         $historyprice->price_id = $data->id;
        //         $historyprice->product_id = $data->product_id;
        //         $historyprice->save();
        //     }
        // }

        Price::where("product_id", $request->resources[0]["product_id"])->delete();
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->save();
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

    public function priceHistoryBySegmen(Request $request)
    {

        $mappingSegmen = [];

        $Price = PriceHistory::with("agencyLevel")->with("product.package")
            ->when($request->has("product_id"), function ($query) use ($request) {
                return $query->where("product_id", $request->product_id);
            })
            ->get()->groupBy("segmen")->map(function ($values, $key) {
            $data = [
                // "segmen" => $key,
                "valid_from" => null,
                "D1" => null,
                "D2" => null,
                "R1" => null,
                "R2" => null,
                "R3" => null,
                "HET" => null,
            ];
            foreach ($values as $key => $value) {
                $data["valid_from"] = $value->valid_from;
                $data["HET"] = $value->het;
                $data[$value->agencyLevel->name]["price"] = $value->price;
                $data[$value->agencyLevel->name]["minimum_order"] = $value->minimum_order;
                $data[$value->agencyLevel->name]["product"] = $value->product;
            }

            return $data;
        })->sortByDesc("valid_from")->values();

        return $this->response("00", "success", $Price);
    }

    public function priceHistoryBySegmenV2(Request $request)
    {

        $mappingSegmen = [];
        $Price = Price::with("agencyLevel")->with("product.package")
            ->when($request->has("product_id"), function ($query) use ($request) {
                return $query->where("product_id", $request->product_id);
            })
            ->get()
            ->groupBy("product_id")
            ->map(function ($values, $key) {
                $data = [
                    "valid_from" => null,
                    "D1" => null,
                    "D2" => null,
                    "R1" => null,
                    "R2" => null,
                    "R3" => null,
                    "HET" => null,
                ];
                foreach ($values as $key => $value) {
                    $data["valid_from"] = $value->valid_from ?: Carbon::parse($value->updated_at)->format("Y-m-d");
                    $data["HET"] = $value->het;
                    $data["het_with_ppn"] = $value->het_with_ppn;
                    $data[$value->agencyLevel->name]["price"] = $value->price;
                    $data[$value->agencyLevel->name]["price_with_ppn"] = $value->price_with_ppn;
                    $data[$value->agencyLevel->name]["minimum_order"] = $value->minimum_order;
                    $data[$value->agencyLevel->name]["product"] = $value->product;
                }

                return $data;
            })->sortByDesc("valid_from")->values();

        return $this->response("00", "success", $Price);
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function afterBatchStore(Request $request, $model)
    {
        if ($request->has("resources")) {
            $priceProductHistoryLast = PriceHistory::select("id", "segmen")->latest()->first();

            /** store to history price */
            foreach ($model as $data) {
                $historyprice = new PriceHistory();
                $historyprice->price = $data->price;
                $historyprice->segmen = $priceProductHistoryLast ? $priceProductHistoryLast->segmen + 1 : 0;
                $historyprice->valid_from = $data->valid_from;
                $historyprice->agency_level_id = $data->agency_level_id;
                $historyprice->het = $data->het;
                $historyprice->minimum_order = $data->minimum_order;
                $historyprice->price_id = $data->id;
                $historyprice->product_id = $data->product_id;
                $historyprice->save();
            }
        }

        $model->first()->product->het = $model->first()->het;
        $model->first()->product->save();
    }
}

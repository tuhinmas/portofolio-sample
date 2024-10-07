<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\PointProduct;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Events\PointInCreatedPointProductEvent;
use Modules\DataAcuan\Events\PointInDeletedPointProductEvent;
use Modules\DataAcuan\Events\PointInUpdatedPointProductEvent;
use Modules\DataAcuan\Http\Requests\PointProductRequest;
use Modules\DataAcuan\Transformers\PointProductCollectionResource;
use Modules\DataAcuan\Transformers\PointProductResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class PointProductController extends Controller
{
    use DisableAuthorization;

    protected $model = PointProduct::class;
    protected $request = PointProductRequest::class;
    protected $resource = PointProductResource::class;
    protected $collectionResource = PointProductCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [

        ];
    }

    public function includes(): array
    {
        return [
            "product",
            "product.package",
            "product.category",
        ];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [
        ];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "id",
            "year",
            "product_id",
            "product.name",
            "minimum_quantity",
            "point",
            "created_at",
            "updated_at",
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
            "id",
            "year",
            "product_id",
            "product.name",
            "minimum_quantity",
            "point",
            "created_at",
            "updated_at",
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
            "id",
            "year",
            "product_id",
            "product.name",
            "minimum_quantity",
            "point",
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
            $data = $query->whereHas("deliveryOrder")->get();
            if ($request->has('delivery_order_only')) {
                return $data->map(function ($data) {
                    return $data->deliveryOrder;
                });
            }
            return $data;
        } else {
            $data = $query->paginate($request->limit > 0 ? $request->limit : 15);
            if ($request->sort_by == 'quantity_to_package') {
                if ($request->direction == "desc") {
                    $sortedResult = $data->getCollection()->sortByDesc(function ($item) {
                        return $item->quantity_to_package;
                    })->values();
                } elseif ($request->direction == "asc") {
                    $sortedResult = $data->getCollection()->sortBy(function ($item) {
                        return $item->quantity_to_package;
                    })->values();
                }

                $data->setCollection($sortedResult);
            }

            return $data;
        }
    }

    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $package_check = $this->package_check($attributes["product_id"]);
        $attributes["minimum_quantity"] = $request->minimum_quantity * $package_check->quantity_per_package;
        $entity->fill($attributes);
        $entity->save();
    }

    public function afterStore(Request $request, $model)
    {
        $point_Product = PointInCreatedPointProductEvent::dispatch($model);
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
    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $package_check = $this->package_check($request->product_id);
        $attributes["minimum_quantity"] = $request->minimum_quantity * $package_check->quantity_per_package;
        $entity->fill($attributes);
        $entity->save();
    }

    /**
     * after update
     *
     * @param Request $request
     * @param [type] $model
     * @return void
     */
    public function afterUpdate(Request $request, $model)
    {
        $point_Product = PointInUpdatedPointProductEvent::dispatch($model);
    }

    /**
     * check product package is active and packaging
     *
     * @param [type] $product_id
     * @return void
     */
    public function package_check($product_id)
    {

        $product = Product::findOrFail($product_id);
        $packages = DB::table('packages')
            ->where('product_id', $product_id)
            ->whereNull("deleted_at")
            ->where("isActive", "1")
            ->first();

        $packaging = $product->unit;
        $quantity_per_package = 1;
        if ($packages) {
            $quantity_per_package = $packages->quantity_per_package;
            $packaging = $packages->packaging;
        }

        $data = (object) [
            'packaging' => $packaging,
            'quantity_per_package' => $quantity_per_package,
        ];

        return $data;
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildDestroyFetchQuery(Request $request, $requestedRelations, bool $softDeletes): Builder
    {
        $query = parent::buildDestroyFetchQuery($request, $requestedRelations, $softDeletes);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runDestroyFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function performDestroy(Model $entity): void
    {
        $entity->delete();
    }

    /**
     * hook method after point product deleted
     *
     * @param Request $request
     * @param [type] $model
     * @return void
     */
    public function afterDestroy(Request $request, $model)
    {
        $point_product = PointInDeletedPointProductEvent::dispatch($model);
    }
}

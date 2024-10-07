<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Entities\ProductMandatory;
use Modules\DataAcuan\Http\Requests\ProductMandatoryRequest;
use Modules\DataAcuan\Transformers\ProductMandatoryCollectionResource;
use Modules\DataAcuan\Transformers\ProductMandatoryResource;
use Modules\ProductGroup\Entities\ProductGroup;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class ProductMandatoryController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = ProductMandatory::class;
    protected $request = ProductMandatoryRequest::class;
    protected $resource = ProductMandatoryResource::class;
    protected $collectionResource = ProductMandatoryCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    public function includes(): array
    {
        return [
            "productGroup",
            "productMember",
            "productMember.product",
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
            'period_date',
            'productGroup.name',
            "productMember.product.name",
            "target",
            'created_at',
            'updated_at',
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
            'id',
            'period_date',
            'productGroup.name',
            "productMember.product.name",
            "target",
            'created_at',
            'updated_at',
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
            'id',
            'period_date',
            'productGroup.name',
            "productMember.product.name",
            "target",
            'created_at',
            'updated_at',
        ];
    }

    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        unset($attributes["product_id"]);
        $entity->fill($attributes);
        $entity->save();
    }

    /**
     * check before save
     *
     * @param Request $request
     * @param [type] $model
     * @return void
     */
    public function afterStore(Request $request, $model)
    {
        ProductGroup::findOrFail($request->product_group_id);
        collect($request->product_id)->each(function ($product_id) use ($request) {
            $product = Product::findOrFail($product_id);
        });

        $model->productMandatoryMember()->syncWithPivotValues(collect($request->product_id)->unique()->toArray(), ["period_date" => $request->period_date]);
    }

    /**
     * Builds Eloquent query for fetching entity(-ies).
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
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

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        unset($attributes["product_id"]);
        $entity->fill($attributes);
        $entity->save();
    }

    public function afterUpdate(Request $request, $model)
    {
        collect($request->product_id)->each(function ($product_id) {
            Product::findOrFail($product_id);
        });

        $model->productMandatoryMember()->syncWithPivotValues(collect($request->product_id)->unique()->toArray(), ["period_date" => $request->period_date]);
    }
}

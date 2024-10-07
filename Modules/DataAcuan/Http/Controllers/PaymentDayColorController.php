<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\PaymentDayColor;
use Modules\DataAcuan\Http\Requests\PaymentDayColorRequest;
use Modules\DataAcuan\Transformers\PaymentDayColorCollectionResource;
use Modules\DataAcuan\Transformers\PaymentDayColorResource;



class PaymentDayColorController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = PaymentDayColor::class;
    protected $request = PaymentDayColorRequest::class;
    protected $resource = PaymentDayColorResource::class;
    protected $collectionResource = PaymentDayColorCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
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
            "min_days",
            "max_days",
            "bg_color",
            "text_color"
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
            "min_days",
            "max_days",
            "bg_color",
            "text_color",
            "created_at",
            "updated_at"
        ];
    }

    public function aggregates(): array
    {
        return [
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
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    
    public function beforeStore(Request $request, $model)
    {

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
        $entity->created_by = auth()->user()->personel_id;
        $entity->save();
    }
}

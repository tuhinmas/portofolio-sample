<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\PickupOrder\Entities\PickupOrderFile;
use Modules\PickupOrder\Http\Requests\PickupOrderFileRequest;
use Modules\PickupOrder\Transformers\Resources\PickupOrderFileResource;
use Modules\PickupOrder\Transformers\Collections\PickupOrderFileCollectionResource;

class PickupOrderFileController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = PickupOrderFile::class;
    protected $request = PickupOrderFileRequest::class;
    protected $resource = PickupOrderFileResource::class;
    protected $collectionResource = PickupOrderFileCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return ["*"];
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
        return column_lists(new $this->model);
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return column_lists(new $this->model);
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return column_lists(new $this->model);
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
            return $query->paginate($request->limit ? $request->limit : 15);
        }
    }
}

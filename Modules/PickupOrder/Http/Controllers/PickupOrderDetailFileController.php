<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Modules\PickupOrder\Entities\PickupOrderDetailFile;
use Modules\PickupOrder\Http\Requests\PickupOrderDetailFileRequest;
use Modules\PickupOrder\Transformers\Collections\PickupOrderDetailFileCollectionResource;
use Modules\PickupOrder\Transformers\Resources\PickupOrderDetailFileResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class PickupOrderDetailFileController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = PickupOrderDetailFile::class;
    protected $request = PickupOrderDetailFileRequest::class;
    protected $resource = PickupOrderDetailFileResource::class;
    protected $collectionResource = PickupOrderDetailFileCollectionResource::class;

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

<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\PickupOrder\Entities\MobileWarehousingVersion;
use Modules\PickupOrder\Http\Requests\MobileWarehousingVersionRequest;
use Modules\PickupOrder\Transformers\Collections\MobileWarehousingVersionResourceCollection;
use Modules\PickupOrder\Transformers\Resources\MobileWarehousingVersionResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class MobileWarehousingVersionController extends Controller
{
    use DisableAuthorization;
    use ResponseHandlerV2;

    protected $model = MobileWarehousingVersion::class;
    protected $request = MobileWarehousingVersionRequest::class;
    protected $resource = MobileWarehousingVersionResource::class;
    protected $collectionResource = MobileWarehousingVersionResourceCollection::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            "*",
        ];
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
        $last_version = DB::table('mobile_warehousing_versions')
            ->whereNull("deleted_at")
            ->where("environment", app()->environment())
            ->orderByDesc("id")
            ->first();

        if ($request->has("disabled_pagination")) {
            $versions = $query
                ->when($request->limit, function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit ? $request->limit : 15);
                })
                ->get();
        } else {
            $versions = $query->paginate($request->limit ? $request->limit : 15);
        }
        
        if (count($versions) > 0) {
            foreach ($versions as $version) {
                $version["is_last_version"] = $last_version ? ($last_version->id == $version->id) : false;
            }
        }

        return $versions;
    }
}

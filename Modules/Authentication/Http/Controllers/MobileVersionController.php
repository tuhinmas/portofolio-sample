<?php

namespace Modules\Authentication\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\MobileVersion;
use Modules\Authentication\Http\Requests\MobileVersionRequest;
use Modules\Authentication\Transformers\MobileVersionCollectionResource;
use Modules\Authentication\Transformers\MobileVersionResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class MobileVersionController extends Controller
{
    use DisableAuthorization;

    protected $model = MobileVersion::class;
    protected $request = MobileVersionRequest::class;
    protected $resource = MobileVersionResource::class;
    protected $collectionResource = MobileVersionCollectionResource::class;

    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * filter list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "version",
            "environment",
            "note",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * search list
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            "version",
            "environment",
            "note",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * sort list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "version",
            "environment",
            "note",
            "created_at",
            "updated_at",
        ];
    }

    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit): LengthAwarePaginator
    {
        $last_version = DB::table('mobile_versions')
            ->whereNull("deleted_at")
            ->where("environment", app()->environment())
            ->orderByDesc("id")
            ->first();

        $versions = $query->paginate($paginationLimit);
        if (count($versions) > 0) {
            foreach ($versions as $version) {
                $version["is_last_version"] = $last_version ? ($last_version->id == $version->id) : false;
            }
        }
        return $versions;
    }
}

<?php

namespace Modules\Authentication\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\Authentication\Entities\UserAccessHistory;
use Modules\Authentication\Transformers\UserAccessHistoryResource;
use Modules\Authentication\Transformers\UserAccessHistoryCollectionResource;

class UserAccessHistoryController extends Controller
{
    use DisableAuthorization;

    protected $model = UserAccessHistory::class;
    protected $resource = UserAccessHistoryResource::class;
    protected $collectionResource = UserAccessHistoryCollectionResource::class;

    public function includes(): array
    {
        return [
            "personel",
            "user",
        ];
    }

    /**
     * filter list
     *
     * @return array
     */
    public function filterableBy(): array
    {        
        $column_lists = column_lists(new $this->model);
        array_push($column_lists, "user.personel_id");
        return $column_lists;
    }

    /**
     * sort list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        $column_lists = column_lists(new $this->model);
        array_push($column_lists, "personel.name");
        return $column_lists;
    }
}

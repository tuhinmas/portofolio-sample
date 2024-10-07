<?php

namespace Modules\Authentication\Http\Controllers;

use Modules\Authentication\Entities\MenuHandler;
use Modules\Authentication\Http\Requests\MenuHandlerRequest;
use Modules\Authentication\Transformers\MenuHandlerCollectionResource;
use Modules\Authentication\Transformers\MenuHandlerResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class MenuHandlerController extends Controller
{
    use DisableAuthorization;

    protected $model = MenuHandler::class;
    protected $request = MenuHandlerRequest::class;
    protected $resource = MenuHandlerResource::class;
    protected $collectionResource = MenuHandlerCollectionResource::class;

    /**
     * filter list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "title",
            "icon",
            "role",
            "visibility",
            "position"
        ];
    }
    
    /**
     * filter list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "title",
            "icon",
            "role",
            "visibility",
            "position"
        ];
    }
    
    /**
     * filter list
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            "title",
            "icon",
            "role",
            "visibiblity",
            "position"
        ];
    }
}

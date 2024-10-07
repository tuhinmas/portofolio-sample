<?php

namespace Modules\Authentication\Http\Controllers;

use Illuminate\Http\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\Authentication\Entities\MenuSubHandler;
use Modules\Authentication\Http\Requests\MenuSubHandlerRequest;
use Modules\Authentication\Transformers\MenuSubHandlerResource;
use Modules\Authentication\Transformers\MenuSubHandlerCollectionResource;

class MenuSubHandlerController extends Controller
{
    use DisableAuthorization;

    protected $model = MenuSubHandler::class;
    protected $request = MenuSubHandlerRequest::class;
    protected $resource = MenuSubHandlerResource::class;
    protected $collectionResource = MenuSubHandlerCollectionResource::class;

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
            "screen",
            "menu_id",
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
            "screen",
            "menu_id",
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
            "visibility",
            "screen",
            "menu_id",
            "position"
        ];
    }
}

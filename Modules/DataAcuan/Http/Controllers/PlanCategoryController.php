<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use Orion\Concerns\DisablePagination;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Entities\PlantCategory;
use Modules\DataAcuan\Http\Requests\PlantCategoryRequest;
use Modules\DataAcuan\Transformers\PlantCategoryResource;
use Modules\DataAcuan\Transformers\PlantCategoryCollection;

class PlanCategoryController extends Controller
{
    use DisablePagination;
    use DisableAuthorization;

    protected $model = PlantCategory::class;
    protected $request = PlantCategoryRequest::class;
    protected $resource = PlantCategoryResource::class;
    Protected $collectionResource = PlantCategoryCollection::class;

   
    /**
     * The attributes that are used for searching.
     *
     * @return array
     */
    public function includes() : array
    {
        return ["plant"];
    }

    public function filterableBy() : array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }
}

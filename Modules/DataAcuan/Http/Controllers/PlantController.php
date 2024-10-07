<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use Modules\DataAcuan\Entities\Plant;
use Orion\Concerns\DisablePagination;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Http\Requests\PlantRequest;
use Modules\DataAcuan\Transformers\PlantResource;
use Modules\DataAcuan\Transformers\PlantCollectionResource;

class PlantController extends Controller
{
    use DisablePagination;
    use DisableAuthorization;

    protected $model = Plant::class; 
    protected $request = PlantRequest::class;
    protected $resource = PlantResource::class;
    protected $collectionResource = PlantCollectionResource::class;

    public function alwaysIncludes() : array
    {
        return ["category"];
    }
    
    public function includes() : array
    {
        return [
            "plantingCalendar"
        ];
    }
    
   /**
    * The attributes that are used for filtering.
    *
    * @return array
    */
    public function filterableBy() : array
    {
        return [
            'id', 
            'name', 
            'plant_category_id', 
            'varieties',
            'category.name', 
            "scientific_name",
            'created_at',
            'updated_at'
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
            'name', 
            'plant_category_id', 
            'varieties',
            'category.name', 
            "scientific_name",
            'created_at',
            'updated_at'
        ];
    }

    
}

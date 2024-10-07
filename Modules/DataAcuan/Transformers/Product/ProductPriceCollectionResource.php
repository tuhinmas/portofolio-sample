<?php

namespace Modules\DataAcuan\Transformers\product;

use App\Traits\CollectionResourceWith;
use Orion\Http\Resources\CollectionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceCollectionResource extends CollectionResource
{
    use CollectionResourceWith;
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

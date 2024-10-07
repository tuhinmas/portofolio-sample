<?php

namespace Modules\PickupOrder\Transformers\Collections;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PickupOrderDetailFileCollectionResource extends ResourceCollection
{
    use CollectionResourceWith;
    
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

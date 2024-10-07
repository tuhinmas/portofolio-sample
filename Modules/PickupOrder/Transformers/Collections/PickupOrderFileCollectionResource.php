<?php

namespace Modules\PickupOrder\Transformers\Collections;

use App\Traits\CollectionResourceWith;
use Orion\Http\Resources\CollectionResource;

class PickupOrderFileCollectionResource extends CollectionResource
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

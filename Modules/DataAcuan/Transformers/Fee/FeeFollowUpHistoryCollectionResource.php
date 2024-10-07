<?php

namespace Modules\DataAcuan\Transformers\Fee;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FeeFollowUpHistoryCollectionResource extends ResourceCollection
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

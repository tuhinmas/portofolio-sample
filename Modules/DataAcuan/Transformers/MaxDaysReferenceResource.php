<?php

namespace Modules\DataAcuan\Transformers;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;

class MaxDaysReferenceResource extends JsonResource
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

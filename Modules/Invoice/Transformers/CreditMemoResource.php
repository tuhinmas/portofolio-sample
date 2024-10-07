<?php

namespace Modules\Invoice\Transformers;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditMemoResource extends JsonResource
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

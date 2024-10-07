<?php

namespace Modules\Personel\Transformers;

use Orion\Http\Resources\Resource;
use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductMandatoryAchievementResourceCollection extends JsonResource
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
        return [
            "marketing_id" => $this->marketing_id
        ];
    }
}

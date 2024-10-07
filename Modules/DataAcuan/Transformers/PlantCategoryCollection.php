<?php

namespace Modules\DataAcuan\Transformers;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PlantCategoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'response_code' => "00",
            "response_message" => 'activity plan index',
            'data' => $this->collection,
        ];    
    }
}

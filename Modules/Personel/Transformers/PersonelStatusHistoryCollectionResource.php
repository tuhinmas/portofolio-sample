<?php

namespace Modules\Personel\Transformers;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PersonelStatusHistoryCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "response_code" => "00",
            "response_message" => "success",
            "data" => $this->collection,
        ];
    }
}

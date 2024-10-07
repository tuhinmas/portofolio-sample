<?php

namespace Modules\Authentication\Transformers;

use Orion\Http\Resources\CollectionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuHandlerCollectionResource extends CollectionResource
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

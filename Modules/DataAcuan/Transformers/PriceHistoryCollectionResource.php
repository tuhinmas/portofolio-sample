<?php

namespace Modules\DataAcuan\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PriceHistoryCollectionResource extends ResourceCollection
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
            "response_message" => "success get list price",
            "data" => $this->collection
        ];
    }
}

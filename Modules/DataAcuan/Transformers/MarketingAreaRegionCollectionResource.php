<?php

namespace Modules\DataAcuan\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MarketingAreaRegionCollectionResource extends ResourceCollection
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
            'response_code' => "00",
            "response_message" => 'region index',
            'data' => $this->collection,
        ];
    }

    public function toResponse($request){
        return parent::toResponse($request)->setStatusCode(200);
    }
}

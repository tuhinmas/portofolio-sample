<?php

namespace Modules\DistributionChannel\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryOrderFileResource extends JsonResource
{
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

    public function with($request){
        return [
            "response_code" => "00",
            "response_message" => "success"
        ];
    }
}

<?php

namespace Modules\SalesOrder\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ExportConfirmedSaleDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function with($request)
    {
        return [
            "response_code" => "00",
            "response_message" => "success",
        ];
    }
}

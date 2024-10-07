<?php

namespace Modules\KiosDealer\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class DealerTempFileResource extends JsonResource
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
            "id" => $this->id,
            "dealer_id" => $this->dealer_id,
            "file_type" => $this->file_type,
            "file" => $this->data,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at
        ];
    }

    public function with($request){
        return [
            "response_code" => "00",
            "response_message" => "success"
        ];
    }

    public function toResponse($request){
        return parent::toResponse($request)->setStatusCode(200);
    }
}

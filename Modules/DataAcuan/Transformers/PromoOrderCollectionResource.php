<?php

namespace Modules\DataAcuan\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PromoOrderCollectionResource extends ResourceCollection
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
            "response_message" => "Promo list",
            "data" => $this->collection->map(function($q){
                $data = [];
                if (isset($q->attributes)) {
                    if (is_array($q->attributes)) {
                        $attributes = $q->attributes;
                    } else {
                        $attributes = json_decode($q->attributes, true);
                    }

                    foreach (($attributes ?? []) as $key => $value) {
                        $data[$key] = array_values($value);
                    }
                }
                return [
                    'id' => $q->id,
                    'name' => $q->name,
                    'date_start' => $q->date_start,
                    'date_end' => $q->date_end,
                    'attributes' => $data
                ];
            }),
        ];
    }
}

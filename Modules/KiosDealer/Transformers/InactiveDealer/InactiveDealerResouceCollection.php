<?php

namespace Modules\KiosDealer\Transformers\InactiveDealer;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InactiveDealerResouceCollection extends ResourceCollection
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
        return $this->collection->map(function ($data, $index) {
            if ($index == "data") {
                return collect($data)->transform(function ($dealer) {                    
                    $detail = [
                        "id" => $dealer->id,
                        "personel_id" => $dealer->personel_id,
                        "dealer_id" => $dealer->dealer_id,
                        "prefix" => $dealer->prefix,
                        "name" => $dealer->name,
                        "sufix" => $dealer->sufix,
                        "address" => $dealer->address,
                        "telephone" => $dealer->telephone,
                        "second_telephone" => $dealer->second_telephone,
                        "owner" => $dealer->owner,
                        "agency_level_id" => $dealer->agency_level_id,
                        "note" => $dealer->note,
                        "agency_level_name" => $dealer->agency_level_name,
                        "marketing_name" => $dealer->marketing_name,
                        "marketing_position_name" => $dealer->marketing_position_name,
                    ];

                    return $detail;
                });
            }
            return $data;
        });
    }
}

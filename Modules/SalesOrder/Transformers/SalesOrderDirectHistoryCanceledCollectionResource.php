<?php
 
namespace Modules\SalesOrder\Transformers;
 
use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesOrderDirectHistoryCanceledCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'response_code' => '00',
            'response_message' => 'success',
            'data' => $this->collection->map(function ($item) {
                return new SalesOrderDirectHistoryCanceledResource($item);
            }),
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
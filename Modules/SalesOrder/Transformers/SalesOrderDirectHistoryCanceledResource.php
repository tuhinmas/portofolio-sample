<?php
 
namespace Modules\SalesOrder\Transformers;
 
use Illuminate\Http\Resources\Json\JsonResource;
 
class SalesOrderDirectHistoryCanceledResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'sales_order_id' => $this->sales_order_id,
            'order_number' => $this->salesOrder?->order_number,
            'cust_id' => "CUST-".$this->salesOrder?->dealer?->dealer_id,
            'dealer_name' => $this->salesOrder?->dealer?->name,
            'order_date' => $this->salesOrder?->invoice?->created_at,
            'canceled_at' => $this->created_at,
            'canceled_by' => $this->personel?->name,
            'canceled_position_by' => $this->personel?->position?->name,
            'nominal_order' => $this->salesOrder?->invoice?->total,
            'marketing_name' => $this->salesOrder?->personel?->name,
            'marketing_position' => $this->salesOrder?->personel?->position->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
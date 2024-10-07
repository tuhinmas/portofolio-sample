<?php

namespace Modules\Personel\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FeeTargetOriginCollectionResources extends ResourceCollection
{
    public function with($request)
    {
        return [
            "response_code" => "00",
            "response_message" => "succes",
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $collections = $this->collection;
        foreach ($collections as $collection) {
            $collection->transaction_date = Carbon::parse(confirmation_time($collection))->format("Y-m-d");
            $collection->quantity_order = $collection->salesOrderDetail->sum("quantity_order") . " " . $collection->salesOrderDetail[0]->product->unit;
            $collection->quantity_actual = $collection->salesOrderDetail->sum("quantity") . " " . $collection->salesOrderDetail[0]->product->unit;
            $collection->quantity_return = $collection->salesOrderDetail->sum("returned_quantity") . " " . $collection->salesOrderDetail[0]->product->unit;
            $collection->handover_status = $collection->statusFee->name;
            $collection->settle_days = $collection->type == "2" ? 0 : $collection->invoice->payment_time;
            $collection->invoice_number = $collection->type == "1" ? $collection->invoice->invoice : $collection->reference_number;
            $collection->store_name = $collection->dealer ? $collection->dealer->prefix . " " . $collection->dealer->name . " " . $collection->dealer->sufix : $collection->subDealer->prefix . " " . $collection->subDealer->name . " " . $collection->subDealer->sufix;
            $collection->customer_id = $collection->dealer ? "CUST-ID-" . $collection->dealer->dealer_id : "CUST-SUB-ID-" . $collection->subDealer->sub_dealer_id;
            $collection->store_owner = $collection->dealer ? $collection->dealer->owner : $collection->subDealer->owner;
            $collection->distributor_name = $collection->distributor ? $collection->distributor->prefix . " " . $collection->distributor->name . " " . $collection->distributor->sufix : null;
            $collection->distributor_customer_id = $collection->distributor ? "CUST-ID-" . $collection->distributor->dealer_id : null;
            $collection->distributor_owner = $collection->distributor ? $collection->distributor->owner : null;
            $collection->marketing_name = $collection->personel ? $collection->personel->name : null;
            $collection->marketing_position = $collection->personel ? $collection->personel->position?->name : null;
            $collection->follow_up_days = $collection->follow_up_days;
            $collection->sales_counter_name = $collection->salesCounter?->name;
            $collection->sales_counter_position = $collection->salesCounter?->position?->name;
            $collection->unsetRelation("invoice");
            $collection->unsetRelation("salesOrderDetail");
            $collection->unsetRelation("statusFee");
            $collection->unsetRelation("dealer");
            $collection->unsetRelation("subDealer");
            $collection->unsetRelation("distributor");
            $collection->unsetRelation("personel");
            $collection->unsetRelation("salesCounter");
        }
        return [
            "data" => $collections,
        ];
    }
}

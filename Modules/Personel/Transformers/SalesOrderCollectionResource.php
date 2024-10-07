<?php

namespace Modules\Personel\Transformers;

use App\Traits\CollectionResourceWith;
use Orion\Http\Resources\CollectionResource;

class SalesOrderCollectionResource extends CollectionResource
{
    use CollectionResourceWith;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($order) {
            $order->nota_or_proforma = $order->type == "2" ? $order->reference_number : $order->invoice->invoice;
            $order->toko_name = $order->dealer ? ($order->dealer->prefix . " " . $order->dealer->name . " " . $order->dealer->sufix) : ($order->subDealer ? ($order->subDealer->prefix . " " . $order->subDealer->name . " " . $order->subDealer->sufix) : null);
            $order->amount_sales = $order->type == "2" ? $order->total : $order->invoice->total;
            $order->total_payment = $order->type == "2" ? null : $order->invoice->nominal;
            $order->last_payment = $order->type == "2" ? null : $order->invoice->last_payment;
            $order->remaining_payment = $order->type == "2" ? null : ($order->invoice->total + $order->invoice->ppn - $order->invoice->nominal);
            $order->payment_time = $order->type == "2" ? null : ($order->invoice->payment_time);
            $order->payment_status = $order->type == "2" ? "Lunas" : ($order->invoice->payment_status == "settle" ? "Lunas" : "Belum Lunas");
            return $order;
        });
    }
}

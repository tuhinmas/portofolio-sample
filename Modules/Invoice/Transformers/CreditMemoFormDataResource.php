<?php

namespace Modules\Invoice\Transformers;

use App\Traits\CollectionResourceWith;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditMemoFormDataResource extends JsonResource
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
        return [
            "dealer" => [
                "customer_id" => $this->resource["invoice"]->salesOrder->dealerV2->dealer_id,
                "name" => ($this->resource["invoice"]->salesOrder->dealerV2->prefix ? $this->resource["invoice"]->salesOrder->dealerV2->prefix . " " : "") . $this->resource["invoice"]->salesOrder->dealerV2->name . ($this->resource["invoice"]->salesOrder->dealerV2->sufix ? " " . $this->resource["invoice"]->salesOrder->dealerV2->sufix : ""),
                "agency_level" => $this->resource["invoice"]->salesOrder->dealerV2->agencyLevel->name,
                "owner" => $this->resource["invoice"]->salesOrder->dealerV2->owner,
                "address" => $this->resource["invoice"]->salesOrder->dealerV2->address
                    . ", " . $this->resource["invoice"]->salesOrder->dealerV2->addressDetail->first()?->district?->name
                    . ", " . $this->resource["invoice"]->salesOrder->dealerV2->addressDetail->first()?->city?->name
                    . ", " . $this->resource["invoice"]->salesOrder->dealerV2->addressDetail->first()?->province?->name,
            ],
            "products" => $this->resource["invoice"]
                ->salesOrder
                ->salesOrderDetail
                ->map(function ($order_detail) {
                    $product_memo = $this->resource["invoice"]
                        ->creditMemos
                        ->pluck("creditMemoDetail")
                        ->flatten()
                        ->filter(function ($memo_detail) use ($order_detail) {
                            return $memo_detail->product_id == $order_detail->product_id;
                        });

                    return [
                        "product_id" => $order_detail->product_id,
                        "product_name" => $order_detail->product?->name,
                        "product_size" => $order_detail->product?->size,
                        "product_unit" => $order_detail->product?->unit,
                        "product_category" => $order_detail->product?->categoryProduct?->name,
                        "package_name" => $order_detail->package_name,
                        "quantity_on_package" => $order_detail->quantity_on_package,
                        "quantity" => $order_detail->quantity,
                        "unit_price" => $order_detail->unit_price - ($order_detail->discount / $order_detail->quantity),
                        "was_return" => $product_memo->sum("quantity_return"),
                    ];
                }),
            "proforma_destination" => $this->resource["proforma_destination"]
        ];
    }
}

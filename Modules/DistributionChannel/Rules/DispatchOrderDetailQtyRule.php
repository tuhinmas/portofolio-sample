<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\DistributionChannel\Actions\GetQuantityLoadedByProductAction;
use Modules\DistributionChannel\Actions\GetQuantityOrderByProductAndDispatchAction;

class DispatchOrderDetailQtyRule implements Rule
{
    protected $receiving_good_detail_id;
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request, $receiving_good_detail_id = null)
    {
        $this->request = $request;
        $this->receiving_good_detail_id = $receiving_good_detail_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        
        if (!$this->request->has("resources")) {

            /* qty order */
            $sales_order_detail = (new GetQuantityOrderByProductAndDispatchAction)($this->request->id_dispatch_order, $this->request->id_product);
            if (!$sales_order_detail) {
                $this->messages = 'product is not inside order, choose the right one';
                return false;
            }

            /* order detail compare to new dispatch */
            if ($value > $sales_order_detail?->quantity) {
                $this->messages = 'quantity can not higher than quantity order';
                return false;
            }

            if ($this->receiving_good_detail_id) {
                $this->receiving_good_detail_id = $this->receiving_good_detail_id["dispatch_order_detail"];
            }

            /* remaining quantity compare to new dispatch */
            $qty_loaded = (new GetQuantityLoadedByProductAction)($sales_order_detail?->invoice_id, $this->request->id_product, $this->receiving_good_detail_id);

            if ($qty_loaded + $value > $sales_order_detail->quantity) {
                $this->messages = 'quantity can not higher than quantity remaining';
                return false;
            }
        }
        
        return true;

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->messages;
    }
}

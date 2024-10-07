<?php

namespace Modules\SalesOrder\Traits;

trait SalesOrderDetailTrait
{
    public function updateTotal()
    {
        /**
         * formula
         * ((qty * price) - discount) - ((qty_return * price) - (discount / qty * qty_retrun))
         */
        $new_total = (($this->quantity * $this->unit_price) - $this->discount)
             - (($this->returned_quantity * $this->unit_price) - ($this->discount > 0 ? $this->discount / $this->quantity * $this->returned_quantity : 0));
        $this->total = $new_total;
        $this->save();

        return (float) $new_total;
    }

    public function getTotalReturn()
    {
        /**
         * formula
         * (qty_return * price) - (discount / qty * qty_retrun)
         */
        return (float) (($this->returned_quantity * $this->unit_price) - ($this->discount > 0 ? $this->discount / $this->quantity * $this->returned_quantity : 0));
    }
}

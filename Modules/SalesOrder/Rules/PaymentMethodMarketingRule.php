<?php

namespace Modules\SalesOrder\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;

class PaymentMethodMarketingRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($sales_order_id = null, $store_id = null)
    {
        $this->sales_order_id = $sales_order_id;
        $this->store_id = $store_id;
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
        if ($value) {

            $payment_method = PaymentMethod::findOrFail($value);
            $personel = Personel::query()
                ->with([
                    "position",
                ])
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->whereIn("name", marketing_positions());
                })
                ->find(auth()->user()->personel_id);

            if (!$this->sales_order_id && $personel) {
                $this->messages = "metode pembayaran untuk masrketing hanya bisa diisi saat submit order";
                return false;
            }

            if ($personel) {

                if (!$payment_method->is_for_marketing) {
                    $this->messages = "Metode pembayaran ini tidak tersedia untuk marketing, hubungi support";
                    return false;
                }

                if ($this->sales_order_id) {
                    $sales_order = SalesOrder::findOrFail($this->sales_order_id["sales_order"]);

                    /* available payment method for marketing */
                    $payment_methods = PaymentMethod::query()
                        ->paymentMethodMarketing()
                        ->paymentAccordingGradeAndDealer($sales_order->store_id, $sales_order->id)
                        ->get()
                        ->pluck("id")
                        ->toArray();

                    if (!in_array($value, $payment_methods)) {
                        $this->messages = "Metode pembayaran ini tidak sesuai dengan kondisi dealer, hubungi support";
                        return false;
                    }
                }
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
        return 'Metode pembayaran ini tidak tersedia untuk marketing, hubungi support';
    }
}

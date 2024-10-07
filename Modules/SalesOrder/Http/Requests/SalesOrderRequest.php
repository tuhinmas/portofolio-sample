<?php

namespace Modules\SalesOrder\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Modules\SalesOrderV2\Rules\ReturnOrderRule;
use Modules\SalesOrder\Rules\DirectSaleLatitudeRule;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\SalesOrder\Rules\PaymentMethodMarketingRule;
use Modules\SalesOrder\Rules\DirectSalesOnCancellationRule;

class SalesOrderRequest extends FormRequest
{
    use ResponseHandler;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /* get parameter of route */
        $sales_order_id = Route::current()->parameters();
        $current_route = Route::current()->methods();

        return [
            'payment_method_id' => [
                'max:255',
                new PaymentMethodMarketingRule($sales_order_id)
            ],
            'recipient_phone_number' => 'max:255',
            'gmaps_link' => 'max:9999',
            'total' => 'max:255',
            'status' => [
                'max:255',

                new DirectSalesOnCancellationRule($sales_order_id)
                
                /* pending at the moment */
                // new DirectSaleLatitudeRule($sales_order_id, $current_route, $this->latitude, $this->longitude),
            ],
            "reference_number" => "unique:sales_orders,reference_number,NULL,id,deleted_at,NULL",
            'return' =>[
                
                  /* retrun order rule */
                  new ReturnOrderRule($sales_order_id)
            ],
            'returned_by' =>[
                "required_if:status,returned"
            ],
            "sales_mode" => [
                Rule::in(["office", "follow_up", "marketing"]),
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}

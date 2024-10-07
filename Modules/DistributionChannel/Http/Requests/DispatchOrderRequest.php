<?php

namespace Modules\DistributionChannel\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DistributionChannel\Rules\DispatchOrderInvoiceRule;
use Modules\DistributionChannel\Rules\DispatchOrderActivateRule;
use Modules\DistributionChannel\Rules\DispatchOrderDeactivateRule;

class DispatchOrderRequest extends Request
{

    use ResponseHandler;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "promotion_good_request_id" => 'sometimes|nullable',
            "invoice_id" => [
                "required_if:promotion_good_request_id,null",
                new DispatchOrderInvoiceRule
            ],
            "id_warehouse" => "required",
            "type_driver" => "required",
            "armada_identity_number" => "required",
            "date_delivery" => "required",
            "dispatch_order_weight" => "required",
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        /* get parameter of route */
        $dispatch_order_id = Route::current()->parameters();

        return [
            "is_active" => [
                new DispatchOrderDeactivateRule($dispatch_order_id),
                new DispatchOrderActivateRule($dispatch_order_id)
            ],
            "invoice_id" => [
                new DispatchOrderInvoiceRule($dispatch_order_id)
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

<?php

namespace Modules\DistributionChannel\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DistributionChannel\Rules\DispatchOrderDetailQtyRule;
use Modules\DistributionChannel\Rules\DispatchOrderDetailProductRule;

class DispatchOrderDetailRequest extends Request
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
            "id_dispatch_order" => "required",
            "id_product" => [
                "required",
                new DispatchOrderDetailProductRule($this),
            ],
            "quantity_packet_to_send" => "required",
            "quantity_unit" => [
                "required",
                "min:0",
                new DispatchOrderDetailQtyRule($this),
            ],
            "planned_quantity_unit" => [
                new DispatchOrderDetailQtyRule($this),
            ],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        $receiving_good_detail_id = Route::current()->parameters();
        return [
            "id_dispatch_order" => "required",
            "id_product" => [
                "required",
                new DispatchOrderDetailProductRule($this),
            ],
            "quantity_packet_to_send" => "required",
            "quantity_unit" => [
                "min:0",
                new DispatchOrderDetailQtyRule($this, $receiving_good_detail_id),
            ],
            "planned_quantity_unit" => [
                new DispatchOrderDetailQtyRule($this, $receiving_good_detail_id),
            ]
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

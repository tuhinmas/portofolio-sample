<?php

namespace Modules\Invoice\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\Invoice\Rules\UniqueInvoiceRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Invoice\Rules\InvoiceUniqueSalesOrderIdRule;

class InvoiceRequest extends Request
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
            "sales_order_id" => [
                "required",
                new InvoiceUniqueSalesOrderIdRule()
            ],
            "invoice" => [
                new UniqueInvoiceRule(),
            ],
            "sub_total" => "max:99999999999999999",
            "discount" => "max:99999999999999999",
            "total" => "max:99999999999999999",
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
        $invoice_id = Route::current()->parameters();
        
        return [
            "sub_total" => "max:99999999999999999",
            "discount" => "max:99999999999999999",
            "total" => "max:99999999999999999",
            "invoice" => [
                new UniqueInvoiceRule($invoice_id),
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

<?php

namespace Modules\Invoice\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class InvoiceProformaRequest extends Request
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
            "invoice_id" => "required|max:255",
            "invoice_proforma_number" => [
                "max:255",
                "required"
            ],
            "link" => "max:500"
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        return [
            "invoice_id" => "max:255",
            "invoice_proforma_number" => "max:255",
            "link" => "max:500"
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
    protected function failedValidation(Validator $validation)
    {
        $errors = $validation->errors();
        $response = $this->response("04", "invalida data send", $errors);
        throw new HttpResponseException($response);
    }
}

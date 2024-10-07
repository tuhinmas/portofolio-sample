<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class WarehouseRequest extends Request
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
            "code" => [
                "required",
                Rule::unique("warehouses")
                    ->where("code", $this->code)
                    ->whereNull("deleted_at"),
            ],
            "name" => [
                "required",
                Rule::unique("warehouses")
                    ->where("name", $this->name)
                    ->whereNull("deleted_at"),
            ],
            "address" => "required",
            "province_id" => "required",
            "city_id" => "required",
            "district_id" => "required",
            "personel_id" => "required",
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
        $warehouse_id = Route::current()->parameters();

        return [
            "code" => [
                "required",
                Rule::unique("warehouses")
                    ->ignore($warehouse_id["warehouse"])
                    ->where("code", $this->code)
                    ->whereNull("deleted_at"),
            ],
            "name" => [
                "required",
                Rule::unique("warehouses")
                    ->ignore($warehouse_id["warehouse"])
                    ->where("name", $this->name)
                    ->whereNull("deleted_at"),
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

    protected function failedValidation(Validator $validation)
    {
        $errors = $validation->errors();
        $response = $this->response("04", "invalid data send", $errors, 422);
        throw new HttpResponseException($response);
    }
}

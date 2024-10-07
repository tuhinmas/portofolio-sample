<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Validation\Validator;
use Modules\KiosDealer\Rule\NoTelpKiosRuleTemp;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\KiosDealer\Rule\UpdateNoTelpKiosRuleTemp;
use Modules\KiosDealer\Rules\StoreLatitudeValidationRule;
use Modules\KiosDealer\Rules\StoreTempOnChangeSubmissionRule;

class StoreTempRequest extends Request
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
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:99999',
            'telephone' => [
                'digits_between:6,15',
                new NoTelpKiosRuleTemp($this->personel_id, $this->store_id)
            ],
            "store_id" => [
                new StoreTempOnChangeSubmissionRule()
            ]
            // "gmaps_link" => 'string|max:700',
            // "latitude" => [
            //     "required",
            // ],
            // "longitude" => [
            //     "required",
            // ],
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
        $store_temp_id = Route::current()->parameters();

        return [
            "status" => [
                "max:255",
                /* pending */
                // new StoreLatitudeValidationRule($store_temp_id, $this->latitude, $this->longitude),
            ],
            'telephone' => [
                'digits_between:6,15',
                new UpdateNoTelpKiosRuleTemp($store_temp_id ,$this->telephone, $this->personel_id)
            ],
            "status_color" => "max:255",
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

<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Rules\SubdealerIdRule;
use Illuminate\Contracts\Validation\Validator;
use Modules\Contest\Entities\ContestParticipant;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\KiosDealer\Rules\DealerTempCheckOriginRule;
use Modules\KiosDealer\Rules\DealerTempFromSubDealerRule;
use Modules\KiosDealer\Rule\KiosToDealerSubDealerAcceptedRule as RuleKiosToDealerSubDealerAcceptedRule;

class DealerTempRequest extends Request
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
            'owner' => 'required|string|max:255',
            'owner_address' => 'required|string|max:255',
            'address' => [
                "required",
                "min:1",
                "max:255",
            ],
            'name' => [
                "nullable",
                "min:1",
                "max:255",
            ],
            'latitude' => 'required',
            'longitude' => 'required',
            'owner_ktp' => 'required',
            'owner_telephone' => 'required',
            'store_id' => new RuleKiosToDealerSubDealerAcceptedRule($this->store_id),
            'dealer_id' => [
                new DealerTempCheckOriginRule()
            ],
            'sub_dealer_id' => [
                new DealerTempFromSubDealerRule
            ]

        ];
    }

    public function updateRules(): array
    {
        /* get parameter of route */
        $dealer_temp_id = Route::current()->parameters();

        return [
            'status' => [
                'max:255',
                /* pending */
                // new DealerTempLatitudeValidationRule($dealer_temp_id, $this->latitude, $this->longitude)
            ],
            'status_color' => 'max:255',
            'telephone' => 'min:6|max:15',
            'owner' => 'string|max:255',
            'owner_address' => 'string|max:255',
            'address' => 'string|max:255',
            'owner_ktp' => 'string|max:255',
            'owner_telephone' => 'string|max:255',
            "sub_dealer_id" => [
                new SubdealerIdRule($dealer_temp_id)
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

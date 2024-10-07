<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Rule\NoTelpKiosRule;
use Modules\KiosDealer\Rules\SubdealerIdRule;
use Illuminate\Contracts\Validation\Validator;
use Modules\Contest\Entities\ContestParticipant;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\KiosDealer\Rules\DealerTempUpdateAcceptedRule;
use Modules\KiosDealer\Rules\SubDealerValidationChangeAddress;
use Modules\KiosDealer\Rules\KiosToDealerSubDealerAcceptedRule;
use Modules\KiosDealer\Rules\SubDealerCheckOriginOnSubmissionRule;
use Modules\KiosDealer\Rule\KiosToDealerSubDealerAcceptedRule as RuleKiosToDealerSubDealerAcceptedRule;

class SubDealerTempRequest extends Request
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
            'name' => [
                "min:1",
                "max:255",
            ],
            'email' => [
                "nullable",
                "min:1",
                "max:255",
            ],
            'telephone' => [
                'digits_between:6,15',
                "min:1",
                "max:255",
            ],
            'address' => [
                "required",
                "min:1",
                "max:255",
            ],
            'owner' => 'required|string|max:255',
            'owner_address' => 'required|string|max:255',
            'owner_telephone' => 'required|digits_between:6,15',
            "sub_dealer_id" => [
                new SubDealerCheckOriginOnSubmissionRule()
            ],
            'store_id' => [
                new RuleKiosToDealerSubDealerAcceptedRule($this->store_id)
            ]
        ];
    }

    public function updateRules(): array
    {
        $sub_dealer_id = Route::current()->parameters();
        return [
            'status' => 'max:255',
            'status_color' => 'max:255',
            'name' => [
                "nullable",
                "min:1",
                "max:255",
            ],
            'email' => [
                "nullable",
                "min:1",
                "max:255",
            ],
            'telephone' => [
                "nullable",
                'digits_between:6,15',
                "min:1",
                "max:255",
            ],
            'address' => [
                "min:1",
                "max:255",
            ],
            "sub_dealer_id" => [
                new SubDealerCheckOriginOnSubmissionRule($sub_dealer_id),
                new SubdealerIdRule(null, $sub_dealer_id)
            ],
            'store_id' => [
                new RuleKiosToDealerSubDealerAcceptedRule($this->store_id)
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

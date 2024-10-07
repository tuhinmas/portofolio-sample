<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Validation\Validator;
use Modules\KiosDealer\Rules\SubDealerAddressRule;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubDealerRequest extends Request
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
                'required',
            ],
            'telephone' => 'required',
            'owner' => 'required|string|max:255',
            'owner_address' => 'required|string|max:255',
            'address' => [
                'required',
                'max:255',
            ],
            'status' => [
                new SubDealerAddressRule($this)
            ],
            'owner_telephone' => 'required',

            /* pending at the moment */
            // 'latitude' => [
            //     "required",
            // ],
            // 'longitude' => [
            //     'required',
            // ],
        ];
    }

    public function updateRules(): array
    {
        /* get parameter of route */
        $sub_dealer_id = Route::current()->parameters();

        return [
            'status' => [
                'required',
                'max:255',

                new SubDealerAddressRule($this, $sub_dealer_id)
                
                /* pending at the moment */
                // new SubDealerLatitudeRule($sub_dealer_id, $this->latitude),
                // new SubDealerLongitudeRule($sub_dealer_id, $this->longitude),
            ],
            'status_color' => 'required|max:255',
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

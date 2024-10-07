<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Modules\KiosDealer\Rules\DealerAddressRule;

class DealerRequest extends FormRequest
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
        $dealer_id = Route::current()->parameters();

        return [
            'telephone' => 'required',
            'owner' => 'required|string|max:255',
            'owner_address' => 'required|string|max:255',
            'address' => [
                'required',
            ],
            "status" => [
                new DealerAddressRule($this, $dealer_id),
            ],
            'owner_ktp' => 'required',
            'owner_telephone' => 'required',
            'name' => 'required',
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

<?php

namespace Modules\PickupOrder\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Modules\PickupOrder\Entities\PickupOrder;
use Illuminate\Contracts\Validation\Validator;
use Modules\PickupOrder\Rules\PickupOrderLoadedRule;
use Illuminate\Http\Exceptions\HttpResponseException;

class PickupOrderDispatchRequest extends Request
{
    use ResponseHandler;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [];
    }

    public function updateRules(): array
    {
        return [];
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

<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Rules\ApplicatorOnMarketingAreaRule;

class MarketingAreaDistrictRequest extends Request
{
    use ResponseHandler;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            "province_id" => "required",
            "city_id" => "required",
            // "district_id" => "required|unique:marketing_area_districts,district_id,NULL,id,deleted_at,NULL",
            "sub_region_id" => "required",
        ];
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules() : array
    {
        $area_id = Route::current()->parameters();

        return [
            "sub_region_id" => "max:255",
            "province_id" => "max:255",
            "city_id" => "max:255",
            "personel_id" => [
                "required_with:applicator_id",
            ],
            "applicator_id" => [
                "different:personel_id",
                new ApplicatorOnMarketingAreaRule($this, $area_id)
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

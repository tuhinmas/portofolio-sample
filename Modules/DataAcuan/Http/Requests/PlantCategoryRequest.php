<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\PlantCategoryUniqueRule;

class PlantCategoryRequest extends Request
{
    use ResponseHandler;

    public function storeRules(): array
    {
        return [
            "name" => [
                "required",
                "min:3",
                "max:30",
                new PlantCategoryUniqueRule(),
            ],
        ];
    }

    public function updateRules(): array
    {
        /* get parameter of route */
        $category = Route::current()->parameters();

        return [
            "name" => [
                "required",
                "min:3",
                "max:30",
                new PlantCategoryUniqueRule($category),
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

<?php

namespace Modules\ReceivingGood\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Modules\ReceivingGood\Rules\FileTypeRule;

class ReceivingGoodFileIndirectSalesRequest extends Request
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
            "receiving_good_id" => "required|max:255",
            "attachment" => "required_without:file|max:255",
            "attachment_status" => "string|max:255",
            'file' => [
                "file",
                "mimetypes:image/jpeg,image/jpg,image/png",
                new FileTypeRule(),
            ],

        ];
    }

    public function updateRules(): array
    {
        return [
            "receiving_good_id" => "required|max:255",
            "attachment" => "required_without:file,max:255",
            "attachment_status" => "string|max:255",
            'file' => [
                "file",
                "mimetypes:image/jpeg,image/jpg,image/png",
                new FileTypeRule(),
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

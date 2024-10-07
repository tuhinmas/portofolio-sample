<?php

namespace App\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\SalesOrder\Entities\SalesOrder;

class IndexController extends Controller
{
    use ResponseHandler;
    public function __invoke(Request $request)
    {        
        $request->validate([
            "data_id" => "required_with:model_type",
        ]);

        $is_delete = false;
        if (Storage::disk('s3')->exists($request->link)) {
            if ($request->has("model_type")) {
                self::setFileToNull($request->all());
            } else {
                $is_delete = true;
            }

            if ($is_delete) {
                $is_delete = Storage::disk('s3')->delete($request->link);
            }

            return $this->response("00", "file deleted from s3 bucket", $is_delete);
        } else {
            self::setFileToNull($request->all());
            return $this->response("00", "file not found, data will set to null", true);
        }
    }

    public static function setFileToNull($request)
    {
        extract($request);
        switch ($model_type) {
            case "1":
                $sales_order = SalesOrder::findOrFail($data_id);
                $sales_order->link = null;
                $sales_order->save();
                break;
            default:
                break;
        }
    }
}

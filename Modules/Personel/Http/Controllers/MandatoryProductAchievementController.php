<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Repositories\PersonelRepository;

class MandatoryProductAchievementController extends Controller
{
    use ResponseHandlerV2;

    public function index(Request $request)
    {
        if ($request->has('group_by') && $request->group_by == "toko") {
            $validator = Validator::make($request->all(), [
                "marketing_id" => "required",
                "product_group_id" => "required",
            ]);
            
            if ($validator->fails()) {
                return $this->response("04", "invalid data send", $validator->errors());
            }
        }

        try {
            $repository = new PersonelRepository;
            $response = $repository->mandatoryProductAchievement($request->all());
            // $data =  collect($response)->first();
            // $data->data = json_decode($data->data);
            // return $data;
            return $this->response("00", "success", $response, 200);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}

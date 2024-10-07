<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\BloodRhesus;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Http\Requests\BloodRhesusRequest;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealerV2\Repositories\StoreRepository;

class JavamasController extends Controller
{
    use ResponseHandler;
   
    public function duplicateNumberTelephone(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "telephone" => "required",
        ]);

        if($validate->fails()){
            return $this->response("04", "invalid data send", $validate->errors());
        }

        try {
            $store = resolve(StoreRepository::class)->list($request);
            return $this->response('00', 'Success', $store);
        } catch (\Exception $th) {
            return $this->response('01', 'Failed', $th, 500);
        }
    }

}
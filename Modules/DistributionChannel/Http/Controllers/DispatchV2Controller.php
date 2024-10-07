<?php

namespace Modules\DistributionChannel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\DistributionChannel\Entities\DispatchList;
use Modules\DistributionChannel\Repositories\DispatchV2Repository;

class DispatchV2Controller extends Controller
{
    use ResponseHandlerV2;

    public function detailDispatch(Request $request, $dispatchId)
    {
        try {
            $repositories = new DispatchV2Repository();
            $response = $repositories->detailDispatch($request->all(), $dispatchId);
            return $this->response('00', 'Success', $response);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function updateDispatch(Request $request, $dispatchId)
    {
        try {
            $repositories = new DispatchV2Repository();
            $response = $repositories->updateDispatch($request->all(), $dispatchId);
            return $this->response('00', 'Success', $response);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function shippingList(Request $request)
    {
        try {
            $repositories = new DispatchV2Repository();
            $response = $repositories->shippingList($request->all());
            return $this->response('00', 'Success', $response);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function shippingListDispatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id_warehouse" => "required",
            "date_delivery" => "required",
            "police_number" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        try {
            $repositories = new DispatchV2Repository();
            $response = $repositories->shippingListDispatch($request->all());
            return $this->response('00', 'Success', $response);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function checkEditDispatch(Request $request, $idDispatch)
    {
        try {
            $checkDispatch = DispatchList::find($idDispatch);
            if ($checkDispatch->pickupDispatchPickuped->count() > 0) {
                $response = [
                    "can_edit" => false
                ];
            }else{
                $response = [
                    "can_edit" => true
                ];
            }

            return $this->response('00', 'Success', $response);
        } catch (\Exception$e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }
}
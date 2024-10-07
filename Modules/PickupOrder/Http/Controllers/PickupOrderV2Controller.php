<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PickupOrder\Events\PickupAutoCheckOnLoadEvent;
use Modules\PickupOrder\Events\PickupOrderLoadedEvent;
use Modules\PickupOrder\Http\Requests\PickupOrderV2StoreRequest;
use Modules\PickupOrder\Repositories\PickupOrderV2Repository;

class PickupOrderV2Controller extends Controller
{
    use ResponseHandlerV2;

    public function detailDispatch(Request $request)
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
            $repositories = new PickupOrderV2Repository();
            $response = $repositories->detailDispatch($request->all());
            return $this->response('00', 'Success', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function store(PickupOrderV2StoreRequest $request)
    {
        try {
            $repositories = new PickupOrderV2Repository();
            $response = $repositories->store($request->all());
            return $this->response('00', 'Success', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function update(Request $request, $pickUpOrderId)
    {
        try {
            $repositories = new PickupOrderV2Repository();
            $response = $repositories->update($pickUpOrderId, $request->except("is_auto_check"));

            $request->merge([
                "is_auto_check" => $request->is_auto_check ?? true,
            ]);

            if (in_array($model->status, ["loaded", "checked"])) {
                if ($request->is_auto_check) {
                    PickupAutoCheckOnLoadEvent::dispatch($model);
                }
                PickupOrderLoadedEvent::dispatch($model);
            }

            return $this->response('00', 'Success', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function detail(Request $request, $pickUpOrderId)
    {
        try {
            $repositories = new PickupOrderV2Repository();
            $response = $repositories->detail($request->all(), $pickUpOrderId);
            return $this->response('00', 'Success', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\UserDeviceRepository;
use App\Traits\ResponseHandlerV2;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

/**
 * Class UserDeviceAPIController
 */
class UserDeviceAPIController extends Controller
{
    use ResponseHandlerV2;

    /** @var UserDeviceRepository */
    public $userDeviceRepo;

    /**
     * UserDeviceAPIController constructor.
     * @param  UserDeviceRepository  $userDeviceRepo
     */
    public function __construct(UserDeviceRepository $userDeviceRepo)
    {
        $this->userDeviceRepo = $userDeviceRepo;
    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function registerDevice(Request $request)
    {
        $device = $this->userDeviceRepo->updateOrCreate($request->all());

        return $this->response("00", "The device has been registered successfully.", $device);
    }

    /**
     * @param $playerId
     *
     * @return JsonResponse
     */
    public function updateNotificationStatus($playerId)
    {
        $device = $this->userDeviceRepo->updateStatus($playerId);
        return $this->response("00", "The notification status has been updated successfully.", $device);
    }

    /**
     * @param $message
     *
     * @return JsonResponse
     */
    private function sendSuccess($message)
    {
        return Response::json([
            'success' => true,
            'message' => $message,
        ], 200);
    }
}

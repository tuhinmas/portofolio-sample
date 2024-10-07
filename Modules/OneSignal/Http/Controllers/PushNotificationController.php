<?php

namespace Modules\OneSignal\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;

class PushNotificationController extends Controller
{
    use ResponseHandlerV2;

    public function __invoke(Request $request)
    {
        try {
            $fields['include_player_ids'] = $request->include_player_ids;
            $message = 'hey!! this is test push.!';

            $notification = OneSignal::sendPush($fields, $message);
            return $this->response("00", "success", $notification);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th);
        }
    }

    public function testPush(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'message' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        try {
            $users = User::with(['userDevices', 'permissions'])
                ->withTrashed()
                ->where("id", $request->user_id)
                ->get();
            
            $userDevices = $users->map(function ($q) {
                return $q->userDevices->map(function ($q) {
                    return $q->os_player_id;
                })->toArray();
            })->flatten()->toArray();


            $textNotif = $request->message;
            $fields = [
                "include_player_ids" => $userDevices,
                "data" => [
                    "subtitle" => "Test",
                    "menu" => "Test",
                    "data_id" => "",
                    "mobile_link" => "",
                    "desktop_link" => "",
                    "notification" => $textNotif,
                    "is_supervisor" => false,
                ],
                "contents" => [
                    "en" => $textNotif,
                    "in" => $textNotif,
                ],
                "recipients" => 1,
            ];
            $response =  OneSignal::sendPush($fields, $textNotif);
            return $this->response("00", "success", $response);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th);
        }
    }
}

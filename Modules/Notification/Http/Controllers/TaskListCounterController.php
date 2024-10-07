<?php

namespace Modules\Notification\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Notification\Entities\NotificationGroupDetail;

class TaskListCounterController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(NotificationGroupDetail $notification_group_detail)
    {
        $this->notification_group_detail = $notification_group_detail;
    }

    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "specific_task" => "array",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        try {
            $notification_group_detail = $this->notification_group_detail->query()
                ->when($request->has("specific_task"), function ($QQQ) use ($request) {
                    return $QQQ->whereHas("notificationGroup", function ($QQQ) use ($request) {
                        return $QQQ->whereIn("menu", $request->specific_task);
                    });
                })
                ->sum("task_count");
            return $this->response("00", "success", [
                "task_list_count" => $notification_group_detail,
            ]);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th);
        }
    }
}

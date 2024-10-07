<?php

namespace Modules\Authentication\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Entities\Device;
use Modules\Personel\Entities\Personel;

class LoginTrackerController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(Device $device, Personel $personel)
    {
        $this->device = $device;
        $this->personel = $personel;
    }

    public function __invoke(Request $request)
    {
        try {
            $personels = $this->personel->query()
                ->with([
                    "position",
                    "firstLoginHistoryToday",
                    "lastLoginHistory",
                    "loginHistories" => function($q) use($request){
                        if ($request->has('by_login_date') && (count($request->by_login_date) > 0)) {
                            $firstDate = $request->by_login_date[0];
                            $endDate = $request->by_login_date[1];
                            $q->whereDate("user_access_histories.created_at", ">=", $firstDate)->whereDate("user_access_histories.created_at", "<=", $endDate);
                        }
                    }
                ])
                ->whereHas("user", function ($QQQ) {
                    return $QQQ->withTrashed();
                })

            /* filter supervisor */
                ->when($request->scope_supervisor, function ($QQQ) use ($request) {
                    return $QQQ->supervisor($request->personel_id ? $request->personel_id : auth()->user()->personel_id);
                })

            /* where has login today */
                ->when($request->login_today, function ($QQQ) {
                    return $QQQ->whereHas("lastLoginHistory", function ($QQQ) {
                        return $QQQ->whereDate("user_access_histories.created_at", now()->format("Y-m-d"));
                    });
                })

                ->whereHas("lastLoginHistory", function ($QQQ) {
                    return $QQQ
                        ->whereNotNull("latitude")
                        ->whereNotNull("longitude");
                })

                ->whereHas("position", function ($QQQ) {
                    return $QQQ->whereIn("name", marketing_positions());
                })

            /* filter marketing */
                ->when($request->by_marketing_name, function ($QQQ) use ($request) {
                    return $QQQ->where("name", "like", "%" . $request->by_marketing_name . "%");
                })

                ->when($request->by_personel_id, function ($QQQ) use ($request) {
                    return $QQQ->where("id", $request->by_personel_id);
                })

            /* filter login time */
                ->when($request->by_login_date, function ($QQQ) use ($request) {
                    return $QQQ->loginHistory($request->by_login_date[0], $request->by_login_date[1]);
                })

            /* when sort by marketing name */
                ->when($request->sort_by_marketing_name, function ($QQQ) use ($request) {
                    return $QQQ->orderBy("name", $request->direction ? $request->direction : "asc");
                })

                ->when($request->scopeMarketingApplicatorUnderSupervisor, function ($QQQ) use ($request){
                    return $QQQ->marketingApplicatorUnderSupervisor($request->personel_id);
                })

                ->when($request->scopeMarketingUnderSupervisor, function ($QQQ) use ($request){
                    return $QQQ->marketingMarketingUnderSupervisor($request->personel_id);
                })

            /* sort by first login today */
                ->when($request->sort_by_first_login_today, function ($QQQ) use ($request) {
                    return $QQQ
                        ->withAggregate("firstLoginHistoryToday", "min(user_access_histories.created_at)")
                        ->orderBy("first_login_history_today_minuser_access_historiescreated_at", $request->direction ? $request->direction : "asc");
                })

            /* sort by last login */
                ->when($request->sort_by_last_login, function ($QQQ) use ($request) {
                    return $QQQ
                        ->withAggregate("lastLoginHistory", "max(user_access_histories.created_at)")
                        ->orderBy("last_login_history_maxuser_access_historiescreated_at", $request->direction ? $request->direction : "asc");
                });

            if ($request->disabled_pagination) {
                $personels = $personels->get();
            } else {
                $personels = $personels->paginate($request->limit ? $request->limit : 15);
            }

            if ($request->has("by_login_date")) {
                foreach ($personels as $personel) {
                    $personel->unsetRelation("lastLoginHistory");
                    $personel->last_login_history = $personel->lastLoginHistory($request->by_login_date[0], $request->by_login_date[1])->first();
                }
            }

            return $this->response("00", "succes", $personels);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}

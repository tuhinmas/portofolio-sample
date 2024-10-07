<?php

namespace Modules\Authentication\Http\Controllers;

use App\Models\LogRequest;
use App\Traits\GmapsLinkGenerator;
use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;
// use Illuminate\Notifications\Notification;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\Device;
use Modules\Authentication\Entities\LoginLog;
use Modules\Authentication\Entities\User;
use Modules\Authentication\Entities\UserAccessHistory;
use Modules\Authentication\Http\Requests\Auth\LoginRequest;
use Modules\Authentication\Notifications\PlantingCalenderSubmission;
use Modules\ForeCast\Notifications\ForecastSubmission;
use Modules\Notification\Entities\Notification;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    use GmapsLinkGenerator;
    use ResponseHandler;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->middleware('auth')->except('login');
        $this->user = $user;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        Artisan::call("cache:clear");
        ini_set('max_execution_time', 1500); //3 minutes
        /* log request */
        if (empty(env("LOG_REQUEST")) || env("LOG_REQUEST") == true) {
            $log = LogRequest::create([
                "environment" => env("APP_ENV", "local"),
                "user_id" => auth()->id() ?: null,
                "request" => [
                    "method" => $request->getMethod(),
                    "body" => $request->all(),
                    "http_response" => app('Illuminate\Http\Response')->status(),
                ],
                "route" => $request->getRequestUri(),
                "http_code" => app('Illuminate\Http\Response')->status(),
                "user_agent" => $request->header('user-agent'),
            ]);
        }

        $user = $this->user->query()
            ->with([
                "roles",
                "profile.position",
                "hasStore" => function ($q) {
                    $q->withCount('core_farmer');
                },
                'permissions',
            ])
            ->withCount(["hasStore"])
            ->where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if ($user == null) {

            /* log request */
            if (empty(env("LOG_REQUEST")) || env("LOG_REQUEST") == true) {
                $log = LogRequest::query()
                    ->where("id", $log->id)
                    ->update([
                        "http_code" => 404,
                    ]);
            }

            return response()->json([
                'response_code' => '01',
                'response_message' => 'User tidak ditemukan.',
            ]);
        }

        /**
         * if user does not have role assign role
         * to user according his position
         * if fail return fail
         */
        if (count($user->roles) <= 0) {

            $user_position = $user->profile ? ($user?->profile?->position ? $user?->profile?->position?->name : null) : null;

            /* assign role to user if user has position */
            if ($user_position) {
                $user->assignRole($user_position);
                $user->refresh();
            }

            if (count($user->roles) <= 0) {

                /* log request */
                if (empty(env("LOG_REQUEST")) || env("LOG_REQUEST") == true) {
                    $log = LogRequest::query()
                        ->where("id", $log->id)
                        ->update([
                            "http_code" => 401,
                        ]);
                }

                return response()->json([
                    'response_code' => '01',
                    'response_message' => 'use does not have right role, please assgin position to user, and login again',
                    "data" => null,
                ], 401);
            }
        }

        if (!empty($user->hasStore) && in_array($user->roles[0]->name, marketing_positions())) {
            // bulan depan
            if (Carbon::now()->format("d") >= "25" && Carbon::now()->format("d") <= "30") {
                $data = User::with("planCalendar")->whereHas('planCalendar', function ($query) use ($request) {
                    return $query->where(function ($QQQ) use ($request) {
                        $month = [
                            1 => 'jan',
                            2 => 'feb',
                            3 => 'mar',
                            4 => 'apr',
                            5 => 'mei',
                            6 => 'jun',
                            7 => 'jul',
                            8 => 'aug',
                            9 => 'sep',
                            10 => 'okt',
                            11 => 'nov',
                            12 => 'dec',
                        ];
                        return $QQQ->where($month[Carbon::now()->addMonth()->month], "0");
                    });
                })->with("notificationHasOne", function ($query) {
                    return $query->where("notified_feature", "planting_calender");
                })->where("id", $user->id)->first();

                if ($data && $data->notificationHasOne == null) {
                    $details = [
                        'personel_id' => $data->personel_id,
                        'notified_feature' => "planting_calender",
                        'notification_text' => "Kalender tanam tanggal " . "25 bulan " . Carbon::now()->addMonth()->format("m") . " belum dibuat",
                        'mobile_link' => "/FilterPlantingCalendar",
                        'desktop_link' => "/marketing-staff/crop-calender",
                        'data_id' => $data->planCalendar[0]->id,
                        'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                        'as_marketing' => true,
                    ];

                    // Notication sales order indirect ditinjau supervisor
                    $member = User::withTrashed()->where("personel_id", $data->personel_id)->first();

                    if ($member) {
                        $member->notify(new PlantingCalenderSubmission($details));

                        $notification = $member->notifications->first();
                        $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                        $notification->notification_marketing_group_id = "10";
                        $notification->notified_feature = "planting_calender";
                        $notification->notification_text = "Kalender tanam tanggal " . "25  bulan " . Carbon::now()->addMonth()->format("F") . " belum dibuat";
                        $notification->mobile_link = "/FilterPlantingCalendar";
                        $notification->desktop_link = "/marketing-staff/crop-calender";
                        $notification->as_marketing = true;
                        $notification->status = "-";
                        $notification->data_id = $data->planCalendar[0]->id;

                        $notification->save();
                    }
                }

                $data_forecast = User::with(["foreCast" => function ($query) {
                    return $query->orderBy("created_at", "desc");
                }])->whereHas("foreCast")->where("id", $user->id)->latest("created_at")->first();

                if ($data_forecast && Carbon::now()->addMonth()->format("Y-m") != Carbon::parse($data_forecast->foreCast[0]->date)->format("Y-m")) {
                    $details = [
                        'personel_id' => $data_forecast->personel_id,
                        'notified_feature' => "forecast",
                        'notification_text' => "Forecast bulan " . Carbon::now()->addMonth()->format("M") . " belum dibuat",
                        'mobile_link' => "/ListForecastSales",
                        'desktop_link' => "/marketing-staff/forecast",
                        'data_id' => $data_forecast->foreCast[0]->id,
                        'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                        'as_marketing' => true,
                    ];

                    $external_data = [
                        "notification_marketing_group_id" => "9",
                        'personel_id' => $data_forecast->personel_id,
                        "notified_feature" => "forecast",
                        'notification_text' => "forecast bulan " . Carbon::now()->format("m") . " sudah dikunci",
                        'mobile_link' => "/ListForecastSales",
                        'desktop_link' => "/marketing-staff/forecast",
                        'data_id' => $data_forecast->foreCast[0]->id,
                        'status' => $data_forecast->status,
                        'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                        'as_marketing' => true,
                    ];

                    // Notication forecast setiap tanggal >= 25
                    $member = User::withTrashed()->where("personel_id", $data_forecast->personel_id)->first();

                    $notification = Notification::query()
                        ->whereHas("foreCast", function ($query) use ($user) {
                            return $query->where("notification_marketing_group_id", "9")
                                ->whereYear('created_at', '=', Carbon::now()->format("Y"))
                                ->whereMonth('created_at', '=', Carbon::now()->format("m"))
                                ->where("notifiable_id", $user->id);
                        })
                        ->first();

                    if ($member && !$notification) {
                        $member->notify(new ForecastSubmission($details, $external_data));

                        $notification = $member->notifications->first();
                        $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                        $notification->notification_marketing_group_id = "9";
                        $notification->notified_feature = "forecast";
                        $notification->notification_text = "Forecast bulan " . Carbon::now()->addMonth()->format("M") . " belum dibuat";
                        $notification->mobile_link = "/ListForecastSales";
                        $notification->desktop_link = "/marketing-staff/forecast";
                        $notification->as_marketing = true;
                        $notification->status = "-";
                        $notification->data_id = $data_forecast->foreCast[0]->id;

                        $notification->save();
                    }
                }
            }
            // DirectSalesAbandonedNotificationEvent::dispatch($user);
        }

        if (!$token = auth()->attempt($request->only($this->username_or_email($request), 'password'), true)) {
            /* log request */
            if (empty(env("LOG_REQUEST")) || env("LOG_REQUEST") == true) {
                $log = LogRequest::query()
                    ->where("id", $log->id)
                    ->update([
                        "http_code" => 401,
                    ]);
            }

            return response()->json([
                'response_code' => '01',
                'response_message' => 'Password salah',
                "data" => "wrong email or password",
            ]);
        }

        $xxx = LoginLog::create([
            "user_id" => auth()->id(),
            "date" => Carbon::now()->format("Y-m-d"),
            "token" => substr($token, -20),
        ]);

        auth()->user()->last_login_at = now();
        auth()->user()->save();

        /* save login history if user send latitude data, only for marketing */
        if ($request->has("latitude") && in_array($user->profile?->position?->name, marketing_positions())) {
            Device::create([
                "user_id" => $user->id,
                "device_id" => $request->device_id,
                "latitude" => $request->latitude,
                "longitude" => $request->longitude,
                "gmaps_link" => $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude),
                "manufacture" => $request->manufacture,
                "model" => $request->model,
                "version_app" => $request->version_app,
                "version_os" => $request->version_os,
                "is_mobile" => $request->is_mobile,
            ]);

            UserAccessHistory::create([
                "user_id" => $user->id,
                "device_id" => $request->device_id,
                "latitude" => $request->latitude,
                "longitude" => $request->longitude,
                "gmaps_link" => $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude),
                "manufacture" => $request->manufacture,
                "model" => $request->model,
                "version_app" => $request->version_app,
                "version_os" => $request->version_os,
                "is_mobile" => $request->is_mobile,
            ]);
        }

        /* log request */
        if (empty(env("LOG_REQUEST")) || env("LOG_REQUEST") == true) {
            $log = LogRequest::query()
                ->where("id", $log->id)
                ->update([
                    "user_id" => auth()->id() ?: null,
                ]);
        }

        $last_version = DB::table('mobile_versions')
            ->whereNull("deleted_at")
            ->where("environment", app()->environment())
            ->orderBy("id", "desc")
            ->first();

        return $this->respondWithToken($token, $user, $last_version, $request->set_environment_like_production);
    }

    public function username_or_email($request)
    {
        $input_type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $request->merge([
            $input_type => $request->login,
        ]);

        return $input_type;
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        Artisan::call("cache:clear");
        $data = [
            auth()->user(),
        ];

        if ($request->has("record_access")) {
            $record_access = $request->record_access;
            $record_access["gmaps_link"] = $this->generateGmapsLinkFromLatitude($request->record_access["latitude"], $request->record_access["longitude"]);
            auth()->user()->userAccessHistory()->create($record_access);
        }

        $user = $this->user->query()
            ->with([
                "profile.position",
                "hasStore" => function ($q) {
                    $q->withCount('core_farmer');
                },
                'permissions',
            ])
            ->withCount(["hasStore"])
            ->where("id", auth()->id())
            ->first();

        $token = $request->bearerToken();
        $last_version = DB::table('mobile_versions')
            ->whereNull("deleted_at")
            ->where("environment", app()->environment())
            ->orderBy("id", "desc")
            ->first();

        $user_detail = collect($this->respondWithToken($token, $user, null, $request->set_environment_like_production)->getData())
            ->only("data", "link")
            ->map(function ($data, $key) {
                if ($key == "data") {
                    return collect($data)->except("token");
                }
                return $data;
            });

        $user_detail["data"]["last_mobile_version"] = $last_version;
        return response()->json([
            "response_code" => "00",
            "response_message" => "you are loged in",
            "data" => $user_detail["data"],
            "link" => $user_detail["link"],
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        $xxx = LoginLog::query()
            ->where("user_id", auth()->id())
            ->whereDate("date", Carbon::now())
            ->where("token", substr($token, -20))
            ->update([
                "logout_at" => Carbon::now(),
            ]);

        $user = auth()->user();

        if (!isset($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token is not set, please retry action or login.',
            ], 500);
        }

        /*
         * Invalidate and blacklist methods
         */
        try {
            JWTAuth::setToken($token)->invalidate(true);
            return $this->response("00", "logout success", $user, 200);
        } catch (JWTException $exception) {
            return $this->response("01", "logout failed", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $user, $last_version = null, $set_environment_like_production = false)
    {
        $user->getPermissionsViaRoles();
        $tokenParts = explode(".", $token);

        /* get requirement of store count */
        $requirements_store_left = 0;
        if (in_array($user?->profile?->position?->name, marketing_positions())) {
            $requirement_store = match (true) {
                (bool) $set_environment_like_production => match ($user?->profile?->position?->name) {
                    "Regional Marketing (RM)" => "30",
                    "Regional Marketing Coordinator (RMC)" => "200",
                    "Marketing District Manager (MDM)" => "500",
                    "Assistant MDM" => "500",
                    "Aplikator" => 0,
                    default => 0
                },
                default => $user->requirement_store[$user?->profile?->position?->name],
            };

            /**
             * RM need to have at least 30 kios with 3 core farmer each kios,
             * apart RM, only need kios, without core farmer requirement
             * needed
             */
            $store_count = $user
                ->hasStore
                ->filter(function ($store) use ($user) {
                    if ($user->profile?->position?->name == position_rm()) {
                        return $store->core_farmer_count >= 3;
                    }
                    return $store;
                })
                ->count();
                
            $user->has_store_count = $store_count;
            $requirements_store_left = $requirement_store - $store_count;
        }

        $active_requirement = match (true) {
            (bool) $set_environment_like_production => [
                "Regional Marketing (RM)" => "30",
                "Regional Marketing Coordinator (RMC)" => "200",
                "Marketing District Manager (MDM)" => "500",
                "Assistant MDM" => "500",
                "Aplikator" => 0,
            ],
            default => $user->requirement_store,
        };

        $data = [
            'token' => $token,
            'expires_in' => auth()->factory()->getTTL() . " minutes",
            'active' => (bool) $set_environment_like_production ?? $user->is_active_marketing,
            'active_requirement' => $active_requirement,
            'user' => $user,
            'requirement_store_left' => $requirements_store_left,
            'last_mobile_version' => $last_version,
        ];

        $link = null;
        if (!$user->roles->isEmpty()) {
            $link = $user->roles[0]->name;
        } else {
            $link = 'login';
        }
        return response()->json([
            'response_code' => "00",
            'response_message' => 'Login berhasil',
            'data' => $data,
            'link' => $link,
            // 'position' => $position
        ]);
    }
}

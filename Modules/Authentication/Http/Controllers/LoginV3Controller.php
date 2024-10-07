<?php

namespace Modules\Authentication\Http\Controllers;

use App\Traits\GmapsLinkGenerator;
use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\Authentication\Entities\UserAccessHistory;
use Modules\Notification\Entities\Notification;

class LoginV3Controller extends Controller
{
    use GmapsLinkGenerator;
    use ResponseHandlerV2;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(
        protected User $user
    ) {
        $this->middleware('auth')->except('login');
    }

    public function login(Request $request)
    {
        $user = $this->user->query()
            ->with([
                "personelOnly.position",
                "hasStore" => function ($q) {
                    $q->withCount('core_farmer');
                },
            ])
            ->where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if ($user == null) {
            return $this->response("01", "User tidak ditemukan", null, 401);
        }

        if (!$token = auth()->attempt($request->only($this->username_or_email($request), 'password'), true)) {
            return $this->response("01", "password salah", null, 401);
        }

        auth()->user()->last_login_at = now();
        auth()->user()->save();
        return $this->response("00", "success", self::authDetail($user, $request, $token));
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $this->user->query()
                ->with([
                    "personelOnly.position",
                    "hasStore" => function ($q) {
                        $q->withCount('core_farmer');
                    },
                ])
                ->where("id", auth()->id())
                ->first();

            $token = $request->bearerToken();
            return $this->response("00", "success", self::authDetail($user, $request, $token));

        } catch (\Throwable $th) {
            return $this->response("01", "failed to login check", [
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    public function username_or_email($request)
    {
        $input_type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $request->merge([
            $input_type => $request->login,
        ]);

        return $input_type;
    }

    public static function authDetail($user, $request, $token = null)
    {
        /* save login history if user send latitude data, only for marketing */
        if ($request->has("record_access")) {
            $record_access = $request->record_access;
            $record_access["gmaps_link"] = (new class {use GmapsLinkGenerator;})->generateGmapsLinkFromLatitude($request->record_access["latitude"], $request->record_access["longitude"]);
            $log = auth()->user()->userAccessHistory()->create($record_access);
        }

        $last_version = DB::table('mobile_versions')
            ->whereNull("deleted_at")
            ->where("environment", app()->environment())
            ->orderBy("id", "desc")
            ->first();

        $requirement_store_left = 0;
        if (in_array($user->personelOnly->position?->name, marketing_positions())) {
            $requirement_store = match (true) {
                (bool) $request->set_environment_like_production => match ($user->personelOnly->position->name) {
                    "Regional Marketing (RM)" => "30",
                    "Regional Marketing Coordinator (RMC)" => "200",
                    "Marketing District Manager (MDM)" => "500",
                    "Assistant MDM" => "500",
                    "Aplikator" => 0,
                    default => 0
                },
                default => $user->requirement_store[$user->personelOnly->position->name],
            };

            $requirement_store_left = $requirement_store - $user
                ->hasStore
                ->filter(function ($store) use ($user) {
                    if ($user->profile?->position?->name == position_rm()) {
                        return $store->core_farmer_count >= 3;
                    }
                    return $store;
                })
                ->count();
        }

        $notification = Notification::query()
            ->where("notifiable_id", $user->id)
            ->consideredNotification()
            ->eventConditional()
            ->get();

        $is_store_fulfilled = match (true) {
            (bool) $request->set_environment_like_production => ($requirement_store_left > 0 ? false : true),
            default =>($user->is_active_marketing ? ($requirement_store_left > 0 ? false : true) : true),
        };

        return [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "username" => $user->username,
            "personel_id" => $user->personel_id,
            "position" => $user->personelOnly->position?->name,
            "photo" => $user->personelOnly->photoLink(),
            "last_version" => $last_version?->version,
            "last_version_url" => $last_version?->link,
            "requirement_store_left" => (int) $requirement_store_left <= 0 ? 0 : (int) $requirement_store_left,
            "is_store_fulfilled" => $is_store_fulfilled,
            "unread_notif_marketing" => $notification->filter(fn($notif) => $notif->as_marketing)->count(),
            "unread_notif_supervisor" => $notification->filter(fn($notif) => !$notif->as_marketing)->count(),
            "is_freeze" => $user->personelOnly?->status == 2 ? true : false,
            "token" => $token,
        ];
    }
}

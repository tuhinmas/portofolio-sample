<?php

namespace Modules\Notification\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Authentication\Entities\User;

class NotificationController extends Controller
{
    use ResponseHandler;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $detail_notif = [];
            if (auth()->user()->hasAnyRole(
                'Support Bagian Distributor',
                'Support Bagian Kegiatan',
                'Support Distributor',
                'Support Kegiatan',
                'Support Supervisor',
                'Marketing Support',
                'Distribution Channel (DC)'
            )) {
                $User = User::find(Auth::id());
                // return $User->notifications;
                $detail_notif[] = collect([
                    "notif" => $User->notifications,
                    'notif_count' => count($User->unreadNotifications),
                ]);
                // $detail_notif;
            }
            $detail_notif = collect($detail_notif)->sortByDesc("created_at");
            // return $User->notifications[0];
            return $this->response("00", "Success get notification", $detail_notif);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get notification index', $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Request $request, $id)
    {
        try {

            if ($request->personel_id) {
                $user = User::where('personel_id', $request->personel_id)->first();
            } else {
                $user = User::where('id', Auth::id())->first();
            }
            $notification = $user->unreadNotifications->find($id);

            if ($notification) {
                // Tandai notifikasi sebagai sudah dibaca
                $notification->markAsRead();

                // Atur read_at menjadi 1 jam ke depan
                $notification->update(['read_at' => Carbon::now()->addHour()]);

                /**
                 * mark as read for all payment due notification in same order number,
                 * if one of these read then all of notif will considred as readed
                 */
                $user
                    ->unreadNotifications
                    ->where("data_id", $notification->data_id)
                    ->where("as_marketing", true)
                    ->filter(fn($notif) => Str::contains($notification->type, "PaymentDueNotification"))
                    ->whenNotEmpty(function ($collection) {
                        return $collection
                            ->toQuery()
                            ->update([
                                "read_at" => Carbon::now()->addHour(),
                            ]);
                    });

            }
            return $this->response("00", "Success show notification", $notification);

        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get notification', $th->getMessage());
        }
    }

    public function readAll()
    {
        try {
            $User = User::where('id', Auth::id())->first();

            foreach ($User->unreadNotifications as $notification) {
                $notification->markAsRead();
            }

            $User = User::find(Auth::id());
            // return $User->notifications;
            $detail_notif[] = collect([
                "notif" => $User->notifications,
                'notif_count' => count($User->readNotifications),
            ]);

            return $this->response("00", "Success read All notification", $detail_notif);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get notification', $th->getMessage());
        }
    }

    public function readMarketing()
    {
        try {
            $detail_notif = [];

            $User = User::find(Auth::id());
            $detail_notif[] = collect([
                "notif" => collect($User->notifications)
                    ->whereNotNull('data.expired_at')
                    ->where("data.expired_at", ">=", Carbon::now()->format("Y-m-d"))->values(),
                'notif_count' => count($User->unreadNotifications->whereNotNull('data.expired_at')->where("data.expired_at", ">=", Carbon::now()->format("Y-m-d"))),
            ]);

            $detail_notif = collect($detail_notif)->sortByDesc("notif.created_at")->values();
            return $this->response("00", "Success get notification", $detail_notif);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to get notification index', $th->getMessage());
        }
    }

    public function personelChildNotif(Request $request)
    {

        try {
            // $User = Personel
            $user = User::where("personel_id", $request->personel_id)->first();
            $data = $user->notifications->whereNotNull('expired_at')
                ->where(function ($query) {
                    return $query
                        ->where("read_at", ">=", Carbon::now()->format("Y-m-d H:i:s"))
                        ->orWhereNull("read_at");
                })
                ->where("expired_at", ">=", Carbon::now()->format("Y-m-d"));
            return $this->response("00", "get supervisor notification success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed supervisor notification success", $th->getMessage());
        }
    }
}

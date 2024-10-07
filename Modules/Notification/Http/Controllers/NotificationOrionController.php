<?php

namespace Modules\Notification\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Authentication\Entities\User;
use Modules\Notification\Entities\Notification;
use Modules\Notification\Entities\NotificationMarketingGroup;
use Modules\Notification\Transformers\NotificationCollectionResource;
use Modules\Notification\Transformers\NotificationMarketingResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class NotificationOrionController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = NotificationMarketingGroup::class;
    // protected $request = NotificationGroupRequest::class;
    protected $resource = NotificationMarketingResource::class;
    protected $collectionResource = NotificationCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [];
    }

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function includes(): array
    {
        return [
            "notification",
            "notification.notifiable_id",
        ];
    }

    public function filterableBy(): array
    {
        return [
            "menu",
            "role",
            "notification.read_at",
            "notificationSupervisor.personel_id",
            "notification.notifiable_id",
            "notification.user.personel_id",
            "notificationSupervisor.user.personel_id",
            "created_at",
            "updated_at",
        ];
    }

    public function sortableBy(): array
    {
        return [
            "menu",
            "role",
            "created_at",
            "updated_at",
        ];
    }

    public function aggregates(): array
    {
        return [
            "notification",
            "notificationSupervisor",
            "notification.read_at",
            "notificationSupervisor",
            "notification.user.personel_id",
            "notificationSupervisor.user.personel_id",
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->has("disabled_pagination")) {
            return $query->get();
        } else {
            return $query->with(["notification as notification_read_at" => function ($query) use ($request) {
                return $query->whereNotNull('expired_at')
                    ->where("expired_at", ">=", Carbon::now()->format("Y-m-d"))
                    ->where(function ($query) {
                        return $query
                            ->where("read_at", ">=", Carbon::now()->format("Y-m-d H:i:s"))
                            ->orWhereNull("read_at");
                    });
            }])->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function countMarketingNotif(Request $request)
    {
        $User = User::find(Auth::id())->whereHas("notification");
        $count = [
            "count_notif" => Notification::query()
                ->where("notifiable_id", Auth::id())
                ->consideredNotification()
                ->where("as_marketing", "1")
                ->where(function ($query) {
                    return $query->eventConditional();
                })
                ->when($request->menus, function ($QQQ) use ($request) {
                    return $QQQ->whereHas("notificationGroup", function ($QQQ) use ($request) {
                        return $QQQ->whereIn("menu", $request->menus);
                    });
                })
                ->count(),

        ];

        return $this->response("00", "Success get notification", $count);
    }
}

<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Modules\Notification\Entities\Notification;
use Modules\Notification\Transformers\NotificationCollectionResource;
use Modules\Notification\Transformers\NotificationMarketingResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class NotificationOrionDetailController extends Controller
{
    // use ResponseHandler;
    use DisableAuthorization;

    protected $model = Notification::class;
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
        return [];
    }

    public function exposedScopes(): array
    {
        return [
            "consideredNotification",
        ];
    }

    public function filterableBy(): array
    {
        return [
            "id",
            "personel_id",
            "user.personel_id",
            "type",
            "notifiable_type",
            "notifiable_id",
            "data",
            "read_at",
            "expired_at",
            "notification_marketing_group_id",
            "notified_feature",
            "notification_text",
            "mobile_link",
            "desktop_link",
            "desktop_link",
            "data_id",
            "as_marketing",
            "created_at",
            "updated_at",
        ];
    }

    public function sortableBy(): array
    {
        return [
            "type",
            "notifiable_type",
            "notifiable_id",
            "data",
            "read_at",
            "expired_at",
            "notification_marketing_group_id",
            "notified_feature",
            "notification_text",
            "mobile_link",
            "desktop_link",
            "desktop_link",
            "data_id",
            "as_marketing",
            "created_at",
            "updated_at",
        ];
    }

    public function aggregates(): array
    {
        return [];
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
            return $query
            ->where("notifiable_id", auth()->id())
                // ->consideredNotification()
                ->get();
        } else {
            return $query
                ->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }
}

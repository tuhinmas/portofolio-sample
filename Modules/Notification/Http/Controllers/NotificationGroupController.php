<?php

namespace Modules\Notification\Http\Controllers;

use Modules\Notification\Entities\NotificationGroup;
use Modules\Notification\Transformers\NotificationGroupCollectionResource;
use Modules\Notification\Transformers\NotificationGroupResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class NotificationGroupController extends Controller
{
    use DisableAuthorization;

    protected $model = NotificationGroup::class;
    // protected $request = NotificationGroupRequest::class;
    protected $resource = NotificationGroupResource::class;
    protected $collectionResource = NotificationGroupCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "notificationGroupDetail",
        ];
    }

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function includes(): array
    {
        return [
            "notificationGroupDetail",
        ];
    }

    public function filterableBy(): array
    {
        return [
            "menu",
            "created_at",
            "updated_at",
        ];
    }

    public function sortableBy(): array
    {
        return [
            "menu",
            "created_at",
            "updated_at",
        ];
    }

    public function aggregates(): array
    {
        return [
            "notificationGroupDetail",
            "notificationGroupDetail.task_count",
        ];
    }
}

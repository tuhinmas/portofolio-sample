<?php

use Modules\OneSignal\Entities\UserDevice;

if (!function_exists("player_ids")) {
    function player_ids()
    {
        $one_signal_device = UserDevice::query()
            ->with([
                "user" => function ($QQQ) {
                    return $QQQ->with([
                        "permissions",
                        "roles",
                    ]);
                },
            ])
            ->whereHas("user", function ($QQQ) {
                return $QQQ->whereHas("personel");
            })
            ->where("is_active", true)
            ->get()
            ->filter(function ($device) {
                if (event_notification_permission($device->user)) {
                    return $device;
                }
            })
            ->pluck("os_player_id");

        return $one_signal_device;
    }
}


if (!function_exists("player_id_by_permissions")) {
    function player_id_by_permissions($permissions)
    {
        $one_signal_device = UserDevice::query()
            ->with([
                "user" => function ($QQQ) {
                    return $QQQ->with([
                        "permissions",
                        "roles",
                    ]);
                },
            ])
            ->whereHas("user", function ($QQQ) {
                return $QQQ->whereHas("personel");
            })
            ->where("is_active", true)
            ->get()
            ->filter(function ($device) use($permissions) {
                if (event_notification_by_permission($device->user, $permissions)) {
                    return $device;
                }
            })
            ->pluck("os_player_id");

        return $one_signal_device;
    }
}

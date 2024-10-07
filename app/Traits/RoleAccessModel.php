<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Administrator\Entities\AccessFeatureModel;
use Modules\Authentication\Entities\FeaturePermission;

/**
 * trait handle role acces on model
 */
trait RoleAccessModel
{
    public function roleListToAccesssModel($model_name)
    {
        $feature_id = DB::table('features')->whereNull("deleted_at")->where("model", $model_name)->first()->id;
        $role_access = AccessFeatureModel::query()
            ->with("role")
            ->where("feature_id", $feature_id)
            ->get();

        $role_access = collect($role_access)->pluck("role.name")->all();
        return $role_access;
    }

    public function userHasPermission($feature)
    {
        $feature = FeaturePermission::query()
            ->with([
                "permission",
            ])
            ->where("permission_for", $feature)
            ->get()
            ->pluck("permission.name")
            ->toArray();

        return $feature;

    }

    /**
     * support list
     *
     * @return void
     */
    public function supportRole()
    {
        return [
            "Marketing Support",
            "Support Distributor",
            "Support Kegiatan",
            "Support Supervisor",
        ];
    }
}

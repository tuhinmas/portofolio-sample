<?php

namespace Modules\Authentication\Http\Controllers;

use App\Models\Permission;
use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Entities\FeaturePermission;

class FeaturePermissionController extends Controller
{
    use ResponseHandler;

    public function __construct(Permission $permission, FeaturePermission $feature_permission)
    {
        $this->permission = $permission;
        $this->feature_permission = $feature_permission;
    }

    public function __invoke(Request $request)
    {
        $request->merge([
            "permission_name" => collect($request->permission_name)->reject(function ($permission_name) {return !$permission_name;})->toArray(),
        ]);

        try {

            /*
            |--------------------
            | Validation
            |---------------
             */
            $validator = Validator::make($request->all(), [
                "permission_name" => [
                    "required",
                    "array",
                ],
                "permission_for" => [
                    "required",
                    "string",
                ],
            ]);

            if ($validator->fails()) {
                return $this->response("04", "invalid data send", $validator->errors(), 422);
            }

            collect($request->permission_name)->each(function ($permission_name) use($request) {
                $permission = $this->permission->firstOrCreate([
                    "name" => $permission_name
                ]);

                $feature_permission = $this->feature_permission->firstOrCreate([
                    "permission_for" => $request->permission_for,
                    "permission_id" => $permission->id
                ]);
            });

            $permissions = $this->permission->whereIn("name", $request->permission_name)->get();
            return $this->response("00", "permissions", $permissions);
        } catch (\Throwable$th) {
            return $this->response("01", "failed to get permission", $th->getMessage(), 500);
        }
    }
}

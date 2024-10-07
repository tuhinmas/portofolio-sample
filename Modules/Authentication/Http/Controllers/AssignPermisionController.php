<?php

namespace Modules\Authentication\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Entities\User;

class AssignPermisionController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function __invoke(Request $request, $user_id)
    {
        $validator = Validator::make($request->all(), [
            "permissions" => [
                "array",
                "required",
            ],
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalida data send", $validator->errors());
        }

        try {
            $user = $this->user->findOrFail($user_id);
            $user->syncPermissions($request->permissions);
            
            if ($request->all_permission) {
                $user->syncPermissions(Permission::all());
            }

            return $this->response("00", "success", $user->permissions);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}

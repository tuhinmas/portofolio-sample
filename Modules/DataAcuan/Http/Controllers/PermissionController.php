<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ResponseHandler;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $exception = [
            "approve event",
            "assign administrator permission",
            "Assistant MDM",
            "create administrator permission",
            "crud data acuan",
            "crud dealer",
            "crud holding",
            "crud holding-organisation",
            "crud personel",
            "crud sales order",
            "crud store",
            "crud user",
            "dealer confirmation",
            "dealer grading",
            "dealer inactive",
            "dealer submission",
            "delete administrator permission",
            "edit administrator permission",
            "event submission",
            "marketing support",
            "MDM",
            "MM",
            "RM",
            "RMC",
            "see own profile",
            "show administrator permission",
            "supervisor",
        ];
        $permission = Permission::whereNotIn('name', $exception)->pluck('name');
        return $this->response('00', 'get all Direct permission', compact('permission'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(Request $request)
    {
        return null;
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $user = User::query()
            ->withTrashed()
            ->where("personel_id", $request->personel_id)
            ->first();

        if ($user) {
            $user->syncPermissions($request->permission);
            if ($request->sync_all_permission) {
                $permissions = DB::table('permissions')->get()->pluck("name");
                $user->syncPermissions($permissions);
            }
            $user->refresh();
        }
        return $this->response('00', 'Success assign permission', $user?->permissions);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $user = User::whereHas('personel', function ($q) use ($request) {
            return $q->where('id', $request->personel_id);
        })->with('permissions')->first();
        return $this->response('00', 'get user permission', $user->permission);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('dataacuan::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}

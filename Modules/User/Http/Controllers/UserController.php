<?php

namespace Modules\User\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Modules\DataAcuan\Entities\Position;
use Illuminate\Contracts\Support\Renderable;

class UserController extends Controller
{
    use HasRoles;

    
    public function __construct(Role $role, Permission $permission, Position $position, User $user)
    {
        $this->role = $role;
        $this->permission = $permission;
        $this->position = $position;
        $this->user = $user;
        $this->user = Auth::check();
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $user = $this->user->query()
            ->where('user_id', $this->user)
            ->with('profile')
            ->first();

        return response()->json([
            'response_code' => '00',
            'response_message' => 'profile displayed',
            'data' => $user,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $roles = $this->role->all();

        $role_default_id = $this->role->findByName('default');
        $position = $this->position->all();
        $position_default_id = Position::where('position', 'staff')->first();

        //pass role collections to create view
        return view('user::create', compact('roles', 'role_default_id', 'position', 'position_default_id'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|users',
            'password' => 'required|string|confirmed|min:8',
            'hoby' => 'required',
            'address' => 'required',
            'hp' => 'required',
        ]);

        //create user from request
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        //create profile for user
        Profile::create([
            'name' => $request->name,
            'address' => $request->address,
            'hp' => $request->hp,
            'hoby' => $request->hoby,
            'user_id' => $user->id,
            'position_id' => $request->position,
        ]);

        //assign role for user was created
        $user->assignRole($request->role);

        return redirect()->route('users.list');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('user::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $profile = Profile::with('position', 'users')
            ->where('id', $id)
            ->first();
        $position_roles = $this->position_role();
        return view('user::edit', compact('profile', 'position_roles'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $profile = Profile::find($id);
        $user = User::find($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->user_id,
            'hoby' => 'required',
            'address' => 'required',
            'hp' => 'required',
        ]);

        $profile->name = $request->name;
        $profile->hoby = $request->hoby;
        $profile->address = $request->address;
        $profile->hp = $request->hp;
        $profile->position_id = $request->position;
        $profile->user_id = $request->user_id;
        $profile->save();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        $user->syncRoles($request->role);
        return redirect()->route('users.list');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();

        return redirect()->route('users.list');
    }

    public function position_role()
    {
        $position = Position::all();
        $roles = Role::all();
        $position_roles = (object) [
            'position' => $position,
            'roles' => $roles,
        ];
        return $position_roles;
    }

    public function edit_permission($id)
    {
        $user = User::with('roles', 'profile')->where('id', $id)->first();

        //get user's role and permission
        $roles = $user->getAllPermissions();
        $permissions = Permission::all();

        return view('user::editPermission', compact('user', 'permissions', 'roles'));
    }

    /**
     * update permission
     *
     * @param Request $request
     * $request->permission = string
     * @param [type] $id
     * @return void
     */
    public function update_permission(Request $request, $id)
    {
        $user = User::find($id);

        //assaign permission to user directly
        $user->givePermissionTo($request->permission);

        return redirect()->route('users.list');
    }
}

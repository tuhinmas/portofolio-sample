<?php
namespace Modules\KiosDealer\Test;

use JWTAuth;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;

trait KiosTrait
{
    public function __construct(User $user, Role $role, Permission $permission){
        $this->user = $user;
        $this->role = $role;
        $this->permission = $permission;
    }
    /**
     * user
     *
     * @return void
     */
    public function user()
    {
        $user = $this->user->factory()->create();
        $role = Role::create(['name' => 'marketing staff']);
        $permission = Permission::create(['name' => 'crud store']);
        $role->givePermissionTo(Permission::all());
        $user->assignRole('marketing staff');

        return $user;
    }

    public function token($user)
    {
        $token = JWTAuth::fromUser($user);
        return $token;
    }
    /**
     * header with jwt token
     *
     * @return void
     */
    public function authorize()
    {
        $authorize = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token($this->user),
        ];

        return $authorize;
    }
}

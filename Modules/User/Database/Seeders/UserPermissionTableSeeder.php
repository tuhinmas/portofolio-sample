<?php

namespace Modules\User\Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class UserPermissionTableSeeder extends Seeder
{
    public function __construct(Role $role, Permission $permission){
        $this->role = $role;
        $this->permission = $permission;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $this->permission->create(['name' => 'default permission', 'see own profile']);

        // create roles and assign created permissions
        // or may be done by chaining

        $role = $this->role->create(['name' => 'default']);
        $role->givePermissionTo(['default permission','see own profile']);
    }
}

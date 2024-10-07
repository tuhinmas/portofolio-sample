<?php

namespace Modules\Personel\Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\Position;

class ScAndOmRolePersmissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        /* get all supprt permission */
        $role_support = Role::findByName("Marketing Support");
        $support_permission = $role_support->permissions->pluck('name');

        /* get sc permission */
        $role_sc = Role::where("name", "Sales Counter (SC)")->first();

        /* assign support permission to sc */
        if($role_sc){
            foreach ($support_permission as $permission) {
                if (!$role_sc->hasPermissionTo($permission)) {
                    $role_sc->givePermissionTo($permission);
                }
            }
        }

        /* get Operational Manager role */
        $role_OM = Role::where("name", "Operational Manager")->first();

        /* assign all permission to role om */
        if ($role_OM) {
            $role_OM->givePermissionTo(Permission::all());
        }
        else {
            $division_OM = Division::firstOrCreate([
                "name" => "Management",
                "description" => "managing"
            ]);
            
            $position_OM = Position::firstOrCreate([
                "name" => "Operational Manager",
                "division_id" => DB::table('divisions')->where("name", "Management")->whereNull("deleted_at")->first()->id,
                "job_description" => "head of manager"
            ]);
            $role_OM = Role::create(['name' => "Operational Manager"]);
            $role_OM->givePermissionTo(Permission::all());
        }

    }
}

<?php

namespace Modules\Personel\Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Modules\Organisation\Entities\Organisation;

class PersonelAndUserTableSeeder extends Seeder
{
    public function __construct(Role $role, Permission $permission)
    {
        $this->role = $role;
        $this->permission = $permission;
    }

    private $roles = [
        'Marketing Manager (MM)',
        'Marketing District Manager (MDM)',
        'Assistant MDM',
        'Regional Marketing Coordinator (RMC)',
        'Marketing Support',
        'super-admin',
        'administrator'
    ];

    private $roles_1 = [
        'Marketing Manager (MM)',
        'Marketing District Manager (MDM)',
        'Assistant MDM',
        'Regional Marketing Coordinator (RMC)',
        'Regional Marketing (RM)',
        'Marketing Support'
    ];

    private $names = [
        'mm',
        'mdm',
        'Assistant MDM',
        'rmc',
        'rm'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $personels = [
            [
                "name" => "mm",
                "position_id" => DB::table('positions')->where("name", "Marketing Manager (MM)")->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => DB::table('religions')->where("name", "islam")->first()->id,
                "gender" => "L",
                "citizenship" => DB::table('countries')->where("code", "ID")->first()->id,
                "organisation_id" => Organisation::inRandomOrder(1)->first()->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "mdm",
                "position_id" => DB::table('positions')->where("name", "Marketing District Manager (MDM)")->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => DB::table('religions')->where("name", "islam")->first()->id,
                "gender" => "L",
                "citizenship" => DB::table('countries')->where("code", "ID")->first()->id,
                "organisation_id" => Organisation::inRandomOrder(1)->first()->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "Assistant MDM",
                "position_id" => DB::table('positions')->where("name", "Assistant MDM")->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => DB::table('religions')->where("name", "islam")->first()->id,
                "gender" => "L",
                "citizenship" => DB::table('countries')->where("code", "ID")->first()->id,
                "organisation_id" => Organisation::inRandomOrder(1)->first()->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "rmc",
                "position_id" => DB::table('positions')->where("name", "Regional Marketing Coordinator (RMC)")->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => DB::table('religions')->where("name", "islam")->first()->id,
                "gender" => "L",
                "citizenship" => DB::table('countries')->where("code", "ID")->first()->id,
                "organisation_id" => Organisation::inRandomOrder(1)->first()->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "rm",
                "position_id" => DB::table('positions')->where("name", "Regional Marketing (RM)")->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => DB::table('religions')->where("name", "islam")->first()->id,
                "gender" => "L",
                "citizenship" => DB::table('countries')->where("code", "ID")->first()->id,
                "organisation_id" => Organisation::inRandomOrder(1)->first()->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
            [
                "name" => "support",
                "position_id" => DB::table('positions')->where("name", "Marketing Support")->first()->id,
                "born_place" => "Gunung Kidul,",
                "born_date" => "1975-08-07",
                "religion_id" => DB::table('religions')->where("name", "islam")->first()->id,
                "gender" => "L",
                "citizenship" => DB::table('countries')->where("code", "ID")->first()->id,
                "organisation_id" => Organisation::inRandomOrder(1)->first()->id,
                "identity_card_type" => "1",
                "identity_number" => "12341234",
                "npwp" => "12341234",
                "blood_group" => "B negative",
            ],
        ];

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $this->permission->firstOrCreate(['name' => 'supervisor']);

        foreach ($this->roles as $role) {
            $role = $this->role->findByName($role);
            $role->givePermissionTo('supervisor');
        }

        foreach ($personels as $key => $personel) {
            $personel = Personel::firstOrCreate($personel);
            if ($personel->name != "mm" && $personel->name != "support") {
                $personel->supervisor_id = DB::table('personels')->where("name", $this->names[$key - 1])->first()->id;
                $personel->save();
            }
        }

        $users = [
            [
                "name" => "mm",
                "username" => "mm@mail.com",
                "email" => "mm@mail.com",
                "password" => bcrypt("password"),
                "personel_id" => DB::table('personels')->where("name", "mm")->first()->id,
            ],
            [
                "name" => "mdm",
                "username" => "mdm@mail.com",
                "email" => "mdm@mail.com",
                "password" => bcrypt("password"),
                "personel_id" => DB::table('personels')->where("name", "mdm")->first()->id,
            ],
            [
                "name" => "assistant_mdm",
                "username" => "assistant_mdm@mail.com",
                "email" => "assistant_mdm@mail.com",
                "password" => bcrypt("password"),
                "personel_id" => DB::table('personels')->where("name", "Assistant MDM")->first()->id,
            ],
            [
                "name" => "rmc",
                "username" => "rmc@mail.com",
                "email" => "rmc@mail.com",
                "password" => bcrypt("password"),
                "personel_id" => DB::table('personels')->where("name", "rmc")->first()->id,
            ],
            [
                "name" => "rm",
                "username" => "rm@mail.com",
                "email" => "rm@mail.com",
                "password" => bcrypt("password"),
                "personel_id" => DB::table('personels')->where("name", "rm")->first()->id,
            ],
            [
                "name" => "support",
                "username" => "support@mail.com",
                "email" => "support@mail.com",
                "password" => bcrypt("password"),
                "personel_id" => DB::table('personels')->where("name", "support")->first()->id,
            ],
        ];
        foreach ($users as $key => $user) {
            $user = User::firstOrCreate($user);
            $user->assignRole($this->roles_1[$key]);
        }
    }
}

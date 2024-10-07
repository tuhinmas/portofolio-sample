<?php

namespace Modules\Personel\Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\Position;
use Modules\Organisation\Entities\Organisation;
use Modules\Personel\Entities\Personel;

class PersonelDCTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $role_dc = Role::firstOrCreate([
            "name" => "Distribution Channel",
        ]);

        $role_dc = Role::firstOrCreate([
            "name" => "Warehouse",
        ]);

        $position_dc = Position::firstOrCreate([
            "name" => "distribution channel",
            "division_id" => DB::table('divisions')->where("name", "Sales & Marketing")->first()->id,
        ]);

        $position_wrh = Position::firstOrCreate([
            "name" => "warehouse",
            "division_id" => DB::table('divisions')->where("name", "Sales & Marketing")->first()->id,
        ]);

        $personel_dc = [
            "name" => "distribution channel",
            "position_id" => $position_dc->id,
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
        ];

        $personel_wrh = [
            "name" => "gudang",
            "position_id" => $position_wrh->id,
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
        ];

        $personel_dc = Personel::firstOrCreate($personel_dc);
        $personel_wrh = Personel::firstOrCreate($personel_wrh);

        $user_dc = [
            "name" => "distribution channel",
            "username" => "dc@mail.com",
            "email" => "dc@mail.com",
            "password" => bcrypt("password"),
            "personel_id" => $personel_dc->id,
        ];

        $user_wrh = [
            "name" => "gudang",
            "username" => "gudang@mail.com",
            "email" => "gudang@mail.com",
            "password" => bcrypt("password"),
            "personel_id" => $personel_wrh->id,
        ];

        $user_dc = User::firstOrCreate($user_dc);
        $user_wrh = User::firstOrCreate($user_wrh);
        $user_dc->assignRole("Distribution Channel");
        $user_wrh->assignRole("Warehouse");
    }
}

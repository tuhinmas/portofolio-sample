<?php

namespace Modules\Authentication\Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\Position;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        Position::all()
            ->each(function ($position) {
                Role::firstOrCreate([
                    "name" => $position->name,
                ]);
            });
    }
}

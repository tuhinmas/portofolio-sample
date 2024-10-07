<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::query()->delete();

        $users = [
            [
                'name' => 'administrator',
                'email' => 'administrator@mail.com',
                'password' => bcrypt('password'),
                'username' => 'administrator',
            ],
            [
                'name' => 'tuhinmas',
                'email' => 'mastuhin33@gmail.com',
                'password' => bcrypt('password'),
                'username' => 'tuhinmas',
            ],
            [
                'name' => 'admin',
                'email' => 'admin@mail.com',
                'password' => bcrypt('password'),
                'username' => 'admin',
            ],
            [
                'name' => 'user',
                'email' => 'user@mail.com',
                'password' => bcrypt('secret'),
                'username' => 'user',
            ],
            [
                'name' => 'supervisor',
                'email' => 'supervisor@mail.com',
                'password' => bcrypt('secret'),
                'username' => 'supervisor',
            ],
            [
                'name' => 'budi kartika',
                'email' => 'budikartika@gmail.com',
                'password' => bcrypt('password'),
                'username' => 'budikartika',
            ],
            [
                'name' => 'support',
                'email' => 'support@mail.com',
                'password' => bcrypt('password'),
                'username' => 'support',
            ],

        ];

        $position = Position::firstOrCreate([
            "name" => "support",
        ]);
        
        foreach ($users as $user) {
            $user = User::create($user);
            if ($user["name"] == "support") {
                $personel = Personel::factory()->create([
                    "name" => $user["name"],
                    "position_id" => $position->id
                ]);
                $user->personel_id = $personel->id;
                $user->Save();
            }
        }
    }
}

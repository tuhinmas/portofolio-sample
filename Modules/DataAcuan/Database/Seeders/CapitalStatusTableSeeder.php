<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\CapitalStatus;

class CapitalStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $capitals = [
            [
                "name" => "Penanaman Modal Asing"
            ],
            [
                "name" => "Penanaman Modal Dalam Negeri"
            ],
        ];

        foreach ($capitals as $capital) {
            CapitalStatus::firstOrCreate($capital);
        }
    }
}

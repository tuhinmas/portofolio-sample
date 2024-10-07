<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Division;

class DivisionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('divisions')->delete();
        $divisions = [
            [
                'name' => 'HRD',
                'description' => 'Human Resource Departement',
                'parent_id' => null
            ],
            [
                'name' => 'Sales & Marketing',
                'description' => 'Dodolan',
                'parent_id' => null
            ],

        ];
        foreach($divisions as $division){
            Division::create($division);
        }
    }
}

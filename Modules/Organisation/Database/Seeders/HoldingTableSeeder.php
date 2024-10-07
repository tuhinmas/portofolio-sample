<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Entities\Holding;

class HoldingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Holding::create([
            'name' => 'Javamas Group',
            'date_standing' => '2015-12-5',
            'note' => 'Javamas adalah',
        ]);
    }
}

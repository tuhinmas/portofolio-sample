<?php

namespace Modules\DataAcuan\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class PpnTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('ppn')->delete();
        DB::insert('insert into ppn (id, ppn, created_at, updated_at) values (?, ?, ?, ?)', ["b84e5197-6362-41ca-9ea3-683a3d9318fa", 10,Carbon::now(), Carbon::now()]);  
    }
}

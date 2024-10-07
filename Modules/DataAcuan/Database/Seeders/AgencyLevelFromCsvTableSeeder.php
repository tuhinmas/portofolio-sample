<?php

namespace Modules\DataAcuan\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use JeroenZwart\CsvSeeder\CsvSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class AgencyLevelFromCsvTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->file = './Modules/DataAcuan/Database/Seeders/csv/agency_levels.csv';
        $this->timestamps = Carbon::now();
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        Schema::disableForeignKeyConstraints();
        DB::table('agency_levels')->delete();
        parent::run();
        Schema::enableForeignKeyConstraints();
    }
}

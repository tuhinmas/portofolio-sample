<?php

namespace Modules\DataAcuan\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use JeroenZwart\CsvSeeder\CsvSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class ProductPackagesTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->file = './Modules/DataAcuan/Database/Seeders/csv/packages.csv';
        $this->foreignKeyCheck = false;
        $this->timestamps = Carbon::now();
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Model::unguard();
        DB::table('packages')->delete();
        parent::run();
        Schema::enableForeignKeyConstraints();
    }
}

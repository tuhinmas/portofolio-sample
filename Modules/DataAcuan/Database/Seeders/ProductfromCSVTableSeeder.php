<?php

namespace Modules\DataAcuan\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JeroenZwart\CsvSeeder\CsvSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class ProductfromCSVTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->file = './Modules/DataAcuan/Database/Seeders/csv/products.csv';
        $this->timestamps = Carbon::now();
        // $this->table = 'products';        
        $this->aliases = [
            'id' => 'id',
            'name' => 'name',
            'type' => 'type',
            'size' => 'size',
            'unit' => 'unit'
        ];
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

        // Recommended when importing larger CSVs
        DB::disableQueryLog();
        parent::run();
        Schema::enableForeignKeyConstraints();
    }
}

<?php

namespace Modules\DataAcuan\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JeroenZwart\CsvSeeder\CsvSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

class ProductBTableSeeder extends CsvSeeder
{
    public function __construct(){
        $this->file = './Modules/DataAcuan/Database/Seeders/csv/products_B.csv';
        $this->timestamps = Carbon::now();
        $this->tablename = 'products';
        $this->aliases = [
            'id' => 'id',
            'name' => 'name',
            'type' => 'type',
            'size' => 'size',
            'unit' => 'unit',
            'weight' => 'weight',
            'category' => 'category'
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

<?php

namespace Modules\DataAcuan\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use JeroenZwart\CsvSeeder\CsvSeeder;
use Illuminate\Database\Eloquent\Model;

class PricesFromCsvTableSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->file = './Modules/DataAcuan/Database/Seeders/csv/prices.csv';
        $this->timestamps = Carbon::now();
        $this->aliases = [
            'id' => 'id',
            'product_id' => 'product_id',
            'agency_level_id' => 'agency_level_id',
            'price' => 'price',
            'minimum_order' => 'minimum_order',
            'het' => 'het'
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
        parent::run();
    }
}

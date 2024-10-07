<?php

namespace Modules\SalesOrder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\SalesOrder\Database\Seeders\SalesOrderOriginTableSeeder;

class SalesOrderDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call(SalesOrderTableSeeder::class);
        $this->call(SalesOrderDetailTableSeeder::class);
        // $this->call(DirectSaleTableSeeder::class);
        // $this->call(SalesOrderOriginTableSeeder::class);
    }
}

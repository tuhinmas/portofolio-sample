<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class OrganisationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(HoldingTableSeeder::class);
        $this->call(EntitiesTableSeeder::class);
        $this->call(CategoryTableSeeder::class);
        $this->call(OrganisationTableSeeder::class);
    }
}

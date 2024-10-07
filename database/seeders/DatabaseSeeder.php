<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PermissionSeeder;
use Modules\DataAcuan\Database\Seeders\SubRegionTableSeeder;
use Modules\Personel\Database\Seeders\PersonelDatabaseSeeder;
use Modules\Dashboard\Database\Seeders\DashboardDatabaseSeeder;
use Modules\DataAcuan\Database\Seeders\DataAcuanDatabaseSeeder;
use Modules\DataAcuan\Database\Seeders\RegionSeederTableSeeder;
use Modules\KiosDealer\Database\Seeders\KiosDealerDatabaseSeeder;
use Modules\SalesOrder\Database\Seeders\SalesOrderDatabaseSeeder;
use Modules\DataAcuan\Database\Seeders\MarketingAreaCityTableSeeder;
use Modules\Organisation\Database\Seeders\OrganisationDatabaseSeeder;
use Modules\Administrator\Database\Seeders\AdministratorDatabaseSeeder;
use Modules\DataAcuan\Database\Seeders\MarketingAreaDistrictTableSeeder;
use Modules\Authentication\Database\Seeders\AuthenticationDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        // $this->call(AdministratorDatabaseSeeder::class);
        // $this->call(ContactTableSeeder::class);
        // $this->call(DashboardDatabaseSeeder::class);
        $this->call(DataAcuanDatabaseSeeder::class);
        $this->call(AuthenticationDatabaseSeeder::class);
        // $this->call(OrganisationDatabaseSeeder::class);
        // $this->call(PersonelDatabaseSeeder::class);
        // $this->call(KiosDealerDatabaseSeeder::class);
        // $this->call(SalesOrderDatabaseSeeder::class);
        // $this->call(RegionSeederTableSeeder::class);
        // $this->call(SubRegionTableSeeder::class);
        // $this->call(MarketingAreaCityTableSeeder::class);
        // $this->call(MarketingAreaDistrictTableSeeder::class);
    }
}

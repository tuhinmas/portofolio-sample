<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class KiosDealerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call(HandoverTableSeeder::class);
        // $this->call(StoreTableSeeder::class);
        // $this->call(CoreFarmerTableSeeder::class);
        // $this->call(DealerTableSeeder::class);
        $this->call(StoresCsvV1TableSeeder::class);
        $this->call(CoreFarmerFromCsvTableSeeder::class);
        $this->call(DealerCsv1TableSeeder::class);
        $this->call(DealerHanoverTableSeeder::class);
        $this->call(DealerGradingSeederTableSeeder::class);
        $this->call(DelaerEntityTableSeeder::class);
        $this->call(DealerAddressTableSeeder::class);
        $this->call(SetDealerGradingTableSeeder::class);
        // $this->call(StoreFromCsvPerMarketingTableSeeder::class);
        // $this->call(DealerCsv3TableSeeder::class);
        // $this->call(SubDealerCsv1TableSeeder::class);
    }
}

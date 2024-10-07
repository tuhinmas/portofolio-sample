<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Modules\DataAcuan\Database\Seeders\AgencyLevelTableSeeder;
use Modules\DataAcuan\Database\Seeders\FeePositionTableSeeder;
use Modules\Personel\Database\Seeders\IdentityCardTableSeeder;
use Modules\DataAcuan\Database\Seeders\ProformaReceiptTableSeeder;
use Modules\Administrator\Database\Seeders\PositionSupportTableSeeder;

class DataAcuanDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        Artisan::call("laravolt:indonesia:seed");

        $this->call(AgencyLevelTableSeeder::class);
        $this->call(FeeFollowUpTableSeeder::class);
        $this->call(BloodTableSeeder::class);
        $this->call(BloodRhesusTableSeeder::class);
        $this->call(CapitalStatusTableSeeder::class);
        $this->call(ReligionTableSeeder::class);
        $this->call(DivisionTableSeeder::class);
        $this->call(PositionTableSeeder::class);
        $this->call(FeePositionTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(BankTableSeeder::class);
        $this->call(BussinessSectorCategoryTableSeeder::class);
        $this->call(BussinessSectorTableSeeder::class);
        $this->call(ProductCategoryTableSeeder::class);
        $this->call(IdentityCardTableSeeder::class);
        // $this->call(ProductTableSeeder::class);
        $this->call(PlantCategoryTableSeeder::class);
        $this->call(StatusFeeTableSeeder::class);
        // $this->call(CounterFeeTableSeeder::class);
        // $this->call(AdditionalPaymentMethodTableSeeder::class);
        $this->call(GradingTableSeeder::class);
        // $this->call(PpnTableSeeder::class);
        //new
        // $this->call(AgencyLevelFromCsvTableSeeder::class);
        // $this->call(PriceTableSeeder::class);
        // $this->call(PackageTableSeeder::class);
        $this->call(PaymentMethodTableSeeder::class);
        //new
        // $this->call(MarketingPositionTableSeeder::class);
        // $this->call(ProductfromCSVTableSeeder::class);
        // $this->call(ProductBTableSeeder::class);
        // $this->call(ProducCategoryUpdateTableSeeder::class);
        // $this->call(ProductPackagesTableSeeder::class);
        // $this->call(PricesFromCsvTableSeeder::class);
        // $this->call(InactiveParameterTableSeeder::class);
        // $this->call(PositionSupportTableSeeder::class);
        // $this->call(DealerPaymentTableSeeder::class);
        // $this->call(DealerBenefitTableSeeder::class);
        $this->call(DefaultGradingTableSeeder::class);
        $this->call(ProformaReceiptTableSeeder::class);
        // csv seeder//
        // $this->call(MarketingAreaDistrictCsv2TableSeeder::class);
    }
}

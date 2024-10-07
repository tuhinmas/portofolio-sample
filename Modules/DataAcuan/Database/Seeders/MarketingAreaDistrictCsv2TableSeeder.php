<?php

namespace Modules\DataAcuan\Database\Seeders;

use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use ogrrd\CsvIterator\CsvIterator;

class MarketingAreaDistrictCsv2TableSeeder extends Seeder
{
    use ResponseHandler;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/DataAcuan/Database/Seeders/csv/marketing_area_district_2.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        $sales_order = null;
        $personel_checker = true;
        $sub_region_checker = true;

        foreach ($rows as $row) {
            $row = (object) $row;
            $district = MarketingAreaDistrict::updateOrCreate([
                "district_id" => $row->district_id,
            ], [
                "province_id" => $row->province_id,
                "city_id" => $row->city_id,
                "personel_id" => $row->personel_id,
                "sub_region_id" => $row->sub_region_id,
            ]);
        }
    }
}

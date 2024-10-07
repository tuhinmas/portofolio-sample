<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class MarketingAreaDistrictTableSeeder extends Seeder
{
    public function __construct(MarketingAreaDistrict $district)
    {
        $this->district = $district;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('marketing_area_districts')->delete();
        $DistrictIn = [
            'Probolinggo',
            'Situbondo',
            'Bondowoso',
            'Lumajang',
            'Lamongan',
            'Tuban',
            'Bojonegoro',
            'Surabaya',
            'Gresik',
            'Jombang',
            'Mojokerto',
            'Sidoarjo',
            'Kediri',
            'Blitar',
            'Tulungagung',
            'Trenggalek',
            'Nganjuk',
            'Madiun',
            'Ngawi',
            'Magetan',
            'Ponorogo',
            'Pacitan',
            'Rembang',
            'Blora',
            'Pati',
            'Kudus',
            'Jepara',
            'Demak',
            'Grobogan',
            'Kendal',
            'Batang',
            'Pekalongan',
            'Pemalang',
            'Tegal',
            'Sleman',
            'Bantul',
            'Kulon Progo',
            'Magelang',
            'Temanggung',
            'Cilacap',
            'Kebumen',
            'Purworejo',
            'Cirebon',
            'Kuningan',
            'Majalengka',
            'Indramayu',
            'Subang',
            'Karawang',
            'Bekasi',
            'Purwakarta',
            'Cianjur',
            'Sukabumi',
            'Bogor',
            'Lebak',
            'Pandeglang',
            'Cilegon'
        ];

        foreach ($DistrictIn as $district) {
            $city = DB::table('indonesia_cities')->where('name', 'like', '%' . $district . '%')->first();
            $province = DB::table('indonesia_provinces')->where("id", $city->province_id)->first();
            $marketing_area_city = DB::table('marketing_area_cities')->where("city_id", $city->id)->first();
            $sub_region = DB::table('marketing_area_sub_regions')->where("id", $marketing_area_city->sub_region_id)->first();
            if ($city) {
                if ($sub_region) {
                    $districts = DB::table('indonesia_districts')->where('city_id', $city->id)->get();
                    foreach ($districts as $district) {
                        $this->district->create([
                            'province_id' => $province->id,
                            'city_id' => $city->id,
                            'district_id' => $district->id,
                            'sub_region_id' => $marketing_area_city->sub_region_id,
                        ]);
                    }
                }
            }
        }
    }
}

<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Modules\DataAcuan\Entities\Region;
use Illuminate\Database\Eloquent\Model;
use Laravolt\Indonesia\Models\Provinsi;

class RegionSeederTableSeeder extends Seeder
{
    public function __construct(Region $region, Provinsi $province){
        $this->region = $region;
        $this->province = $province;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('province_regions')->delete();
        DB::table('marketing_area_regions')->delete();
        Artisan::call('laravolt:indonesia:seed');

        $region = $this->region->create([
            'name' => 'Jatim Timur',
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id,
        ]);
        $province = DB::table('indonesia_provinces')->where('name', 'LIKE','Jawa Timur')->first()->id;
        $region->provinceRegion()->attach($province);

        $region = $this->region->create([
            'name' => 'Jatim Barat',
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id,
        ]);
        $province = DB::table('indonesia_provinces')->where('name', 'LIKE','Jawa Timur')->first()->id;
        $region->provinceRegion()->attach($province);

        $region = $this->region->create([
            'name' => 'Jawa Tengah & DIY',
            'personel_id' => DB::table('personels')->where('name','like','%Heru Susilo%')->First()->id,
        ]);
        $province_1 = DB::table('indonesia_provinces')->where('name', 'LIKE','Jawa Tengah')->first()->id;
        $province_2 = DB::table('indonesia_provinces')->where('name', 'LIKE','DI YOGYAKARTA')->first()->id;
        $region->provinceRegion()->attach([$province_1, $province_2]);

        $region = $this->region->create([
            'name' => 'Jawa Barat & Banten',
            'personel_id' => DB::table('personels')->where('name','like','%Dede Jumaedi%')->First()->id,
        ]);
        $province_1 = DB::table('indonesia_provinces')->where('name', 'LIKE','Jawa Barat')->first()->id;
        $province_2 = DB::table('indonesia_provinces')->where('name', 'LIKE','Banten')->first()->id;
        $region->provinceRegion()->attach([$province_1, $province_2]);

        $region = $this->region->create([
            'name' => 'Lampung',
            'personel_id' => DB::table('personels')->where('name','like','Samsul Arifin')->First()->id,
        ]);
        $province = DB::table('indonesia_provinces')->where('name', 'LIKE','Lampung')->first()->id;
        $region->provinceRegion()->attach($province);

        $region = $this->region->create([
            'name' => 'Sumatera Selatan & Bengkulu',
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id
        ]);
        $province_1 = DB::table('indonesia_provinces')->where('name', 'LIKE','Sumatera Selatan')->first()->id;
        $province_2 = DB::table('indonesia_provinces')->where('name', 'LIKE','BENGKULU')->first()->id;
        $region->provinceRegion()->attach([$province_1, $province_2]);

        $region = $this->region->create([
            'name' => 'Sumatera Barat',
            'personel_id' => DB::table('personels')->where('name','like','Teguh Setiawan')->First()->id,
        ]);
        $province = DB::table('indonesia_provinces')->where('name', 'LIKE','Sumatera Barat')->first()->id;
        $region->provinceRegion()->attach($province);

        $region = $this->region->create([
            'name' => 'Sumatera Utara & Aceh',
            'personel_id' => DB::table('personels')->where('name','like','Heri Murdani%')->First()->id,
        ]);
        $province_1 = DB::table('indonesia_provinces')->where('name', 'LIKE','Sumatera Utara')->first()->id;
        $province_2 = DB::table('indonesia_provinces')->where('name', 'LIKE','Aceh')->first()->id;
        $region->provinceRegion()->attach([$province_1, $province_2]);

        $region = $this->region->create([
            'name' => 'Sulawesi',
            'personel_id' => DB::table('personels')->where('name','Budi Kartika')->First()->id
        ]);
        $province = DB::table('indonesia_provinces')->where('name', 'LIKE','Sulawesi Utara')->first()->id;
        $region->provinceRegion()->attach($province);

        $region = $this->region->create([
            'name' => 'Riau',
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id
        ]);
        $province = DB::table('indonesia_provinces')->where('name', 'LIKE','Riau')->first()->id;
        $region->provinceRegion()->attach($province);

        $region = $this->region->create([
            'name' => 'Jambi',
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id
        ]);
        $province = DB::table('indonesia_provinces')->where('name', 'LIKE','Jambi')->first()->id;
        $region->provinceRegion()->attach($province);
    }
}

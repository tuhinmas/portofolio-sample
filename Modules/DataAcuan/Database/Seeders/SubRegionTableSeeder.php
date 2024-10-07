<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\SubRegion;

class SubRegionTableSeeder extends Seeder
{
    public function __construct(SubRegion $sub_region){
        $this->sub_region = $sub_region;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('marketing_area_sub_regions')->delete();
        
        $this->sub_region->create([
            'name' => 'Jatim Timur I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jatim Timur')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','%Syaefudin Zuhri')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jatim Timur II',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jatim Timur')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','%Syaefudin Zuhri')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jatim Barat I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jatim Barat')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Andi Fianto')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jatim Barat II',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jatim Barat')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','%Athok Risdiyanto%')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jatim Barat III',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jatim Barat')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Alid Hermawan')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jateng I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Tengah & DIY')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Dwi Totok Supriyadi')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jateng II',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Tengah & DIY')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Eko Mardiyanto')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jateng III',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Tengah & DIY')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Dwi Lanang%')->first()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jateng IV',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Tengah & DIY')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','%Heru Susilo%')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jabar I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Barat & Banten')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Dede Jumaedi')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jabar II',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Barat & Banten')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Tasman')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jabar III',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Barat & Banten')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','%Bambang Kusuma Wijaya%')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Jabar IV',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Barat & Banten')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Dede Jumaedi')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Banten',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jawa Barat & Banten')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Dede Jumaedi')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Lampung I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Lampung')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Danang%')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Lampung II',
            'region_id' => DB::table('marketing_area_regions')->where('name','Lampung')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Samsul Arifin')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Lampung III',
            'region_id' => DB::table('marketing_area_regions')->where('name','Lampung')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Danang%')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Lampung IV',
            'region_id' => DB::table('marketing_area_regions')->where('name','Lampung')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Samsul Arifin')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Sumsel I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Selatan & Bengkulu')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Ahmad Rudi%')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Sumsel II',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Selatan & Bengkulu')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Bengkulu',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Selatan & Bengkulu')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Sumbar I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Barat')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Ngatiman')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'Sumbar II',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Barat')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Alex Sugianto')->First()->id,
        ]);
        $this->sub_region->create([
            'name' => 'SumUt I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Utara & Aceh')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Heri Murdani%')->First()->id
        ]);
        $this->sub_region->create([
            'name' => 'SumUt I',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Utara & Aceh')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Heri Murdani%')->First()->id
        ]);
        $this->sub_region->create([
            'name' => 'Aceh',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sumatera Utara & Aceh')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Heri Murdani%')->First()->id
        ]);
        $this->sub_region->create([
            'name' => 'Sulawesi Tengah',
            'region_id' => DB::table('marketing_area_regions')->where('name','Sulawesi')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Tian Pangloli')->First()->id
        ]);
        $this->sub_region->create([
            'name' => 'Riau',
            'region_id' => DB::table('marketing_area_regions')->where('name','Riau')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id
        ]);
        $this->sub_region->create([
            'name' => 'Jambi',
            'region_id' => DB::table('marketing_area_regions')->where('name','Jambi')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Budi Kartika')->First()->id
        ]);
        
    }
}

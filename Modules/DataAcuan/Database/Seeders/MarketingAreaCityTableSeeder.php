<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\MarketingAreaCity;

class MarketingAreaCityTableSeeder extends Seeder
{
    public function __construct(MarketingAreaCity $city){
        $this->city = $city;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('marketing_area_cities')->delete();
        $this->city->create([
            // 'name' => 'Banyuwangi Utara',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur I')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Ilzam Nuzuli')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Banyuwangi%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Banyuwangi Selatan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur I')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Trisno Aji')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Banyuwangi%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kabupaten Probolinggo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Fajar Yuliyanto')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kabupaten Probolinggo%')->first()->id,
        ]);  
        
        $this->city->create([
            // 'name' => 'Kota Probolinggo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Fajar Yuliyanto')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kota Probolinggo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Situbondo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Wendri muji%')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Situbondo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Bondowoso',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Wendri muji%')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Bondowoso%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Jember Utara',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Wendri muji%')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Jember%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Jember Selatan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => DB::table('personels')->where('name','like','Arista Wahyudiyanto')->first()->id,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Jember%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Lumajang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Lumajang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Pasuruan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Timur II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Pasuruan%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Tuban',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Tuban%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Lamongan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Lamongan%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Bojonegoro',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Bojonegoro%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Madura',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Sampang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Surabaya',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Surabaya%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Gresik',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Gresik%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Malang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Malang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Tutur',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Malang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Nongkojajar',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Malang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Batu',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Batu%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Pujon',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Malang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Jombang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Jombang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Mojokerto',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Mojokerto%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Sidoarjo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Sidoarjo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kediri',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kediri%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Blitar',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Blitar%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Tulungagung',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Tulungagung%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Trenggalek',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Trenggalek%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Nganjuk',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Nganjuk%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Ngawi',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Ngawi%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Magetan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Magetan%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Ponorogo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Ponorogo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Madiun',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Madiun%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Pacitan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jatim Barat III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Pacitan%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Rembang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Rembang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Blora',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Blora%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Jepara',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Jepara%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Demak',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Demak%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Pati',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Pati%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kudus',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kudus%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Grobogan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Grobogan%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kendal',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kendal%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Pekalongan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Pekalongan%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Batang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Batang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Pemalang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Pemalang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Tegal',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Tegal%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Brebes Timur',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Brebes%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Brebes Barat',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Brebes%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Sragen',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Sragen%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Karanganyar',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Karanganyar%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Wonogiri',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Wonogiri%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Klaten',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Klaten%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Sukoharjo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Sukoharjo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Boyolali',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Boyolali%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kabupaten Semarang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kabupaten Semarang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Sleman',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Sleman%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kulon Progo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kulon Progo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Bantul',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Bantul%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Magelang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Magelang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Temanggung',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Temanggung%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Wonosobo Bawah',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Wonosobo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Banjarnegara Atas',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Banjarnegara%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Wonosobo Atas',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Wonosobo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Banjarnegara Bawah',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Banjarnegara%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Purbalingga',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Purbalingga%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Banyumas',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Banyumas%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Cilacap',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Cilacap%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kebumen',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kebumen%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Purworejo',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jateng IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Purworejo%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kuningan',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kuningan%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Majalengka',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Majalengka%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Indramayu',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Indramayu%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Cirebon',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar I')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Cirebon%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Subang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Subang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Karawang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Karawang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Bekasi',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Bekasi%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Purwakarta',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar II')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Purwakarta%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Garut',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Garut%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kabupaten Tasikmalaya',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kabupaten Tasikmalaya%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Kota Tasikmalaya',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Kota Tasikmalaya%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Ciamis',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Ciamis%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Banjar',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Banjar%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Bandung',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar III')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Bandung%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Cianjur',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Cianjur%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Sukabumi',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Sukabumi%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Bogor',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Jabar IV')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Bogor%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Tangerang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Banten')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Tangerang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Serang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Banten')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Serang%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Cilegon',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Banten')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Cilegon%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Lebak',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Banten')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Lebak%')->first()->id,
        ]);  
        $this->city->create([
            // 'name' => 'Pandeglang',
            'sub_region_id' => DB::table('marketing_area_sub_regions')->where('name','Banten')->first()->id,
            'personel_id' => null,
            'city_id' => DB::table('indonesia_cities')->where('name','like', '%Pandeglang%')->first()->id,
        ]);
    }
}

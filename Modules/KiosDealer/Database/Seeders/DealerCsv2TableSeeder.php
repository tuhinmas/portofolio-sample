<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ogrrd\CsvIterator\CsvIterator;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\AgencyLevel;

class DealerCsv2TableSeeder extends Seeder
{
    public function __construct(Dealer $dealer, Personel $personel, AgencyLevel $agency_level){
        $this->dealer = $dealer;
        $this->personel = $personel;
        $this->agency_level = $agency_level;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        Model::unguard();
        DB::table('dealers')->delete();
        $pathToFile = 'Modules/KiosDealer/Database/Seeders/csv/dealer - v2.csv';
        $delimiter = ';'; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach ($rows as $row) {
            // dd($row);
            $marketing = $row['marketing'];
            $dealer_id = $row['dealer_id'];
            $name = $row['name'];
            $owner = $row['owner'];
            $owner_address = $row['owner_address'];
            $owner_telephone = $row['owner_telephone'];
            $ktp = $row['ktp'];
            $npwp = $row['npwp'];
            $address = $row['address'];
            $telephone = $row['telephone'];
            $email = $row['email'];
            $gmaps_link = $row['gmaps_link'];

            $personel = $this->personel->where('name', 'like', '%'.$marketing."%")->first();
            if($personel == null){
                dd($ktp);
            }
            $agency_level = $this->agency_level->where('name','R3')->first();
            $this->dealer->create([
                'personel_id' => $personel->id,
                'dealer_id' => $dealer_id,
                'name' => $name,
                'address' => $address,
                'telephone' => $telephone,
                'status' => 'accepted',
                'status_color' => '000000',
                'owner' => $owner,
                'owner_address' => $owner_address,
                'owner_ktp' => $ktp,
                'owner_npwp' => $npwp,
                'owner_telephone' => $owner_telephone,
                'agency_level_id' => $agency_level->id,
                'email' => $email,
                'gmaps_link' => $gmaps_link
            ]);
        }
    }
}

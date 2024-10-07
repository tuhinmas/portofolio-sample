<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\Bank;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Country;

class BankTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries = Country::where('code', 'ID')->first();
        // dd($countries);
        $banks =[
            [
                'code' => '002',
                'name' => 'BRI',
                'country_id' => $countries->id,
                'IBAN' => '002',
                'swift_code' => 'BRINIDJA',
            ],
        ];
        foreach($banks as $bank ){
            Bank::create($bank);
        }
    }
}

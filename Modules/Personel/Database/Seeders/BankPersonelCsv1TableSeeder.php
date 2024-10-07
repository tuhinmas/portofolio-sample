<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\PersonelBank;

class BankPersonelCsv1TableSeeder extends Seeder
{
    public function __construct(PersonelBank $personel_bank)
    {
        $this->personel_bank = $personel_bank;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('bank_personels')->delete();
        $personels = DB::table('personels')->get();
        $bank = DB::table('banks')->where('name', 'BRI')->first();
        foreach ($personels as $personel) {
            $this->personel_bank->create([
                'personel_id' => $personel->id,
                'bank_id' => $bank->id,
                'branch' => 'Ambarrukmo',
                'owner' => $personel->name,
                'rek_number' => '123123456',
            ]);
        }
    }
}

<?php

namespace Modules\KiosDealer\Import;

use Illuminate\Support\Facades\Hash;
use Modules\KiosDealer\Entities\Store;
use Maatwebsite\Excel\Concerns\ToModel;

class StoreImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return User|null
     */
    public function model(array $row)
    {
        $store = Store::updateOrCreate([

        ]);
        
        return new User([
           'name'     => $row[0],
           'email'    => $row[1], 
           'password' => Hash::make($row[2]),
        ]);
    }
}
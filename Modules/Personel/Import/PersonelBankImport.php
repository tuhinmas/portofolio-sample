<?php

namespace Modules\Personel\Import;

use App\Models\Address;
use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Modules\DataAcuan\Entities\Country;
use Modules\Personel\Entities\Personel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Str;
use Modules\DataAcuan\Entities\Bank;
use Modules\Personel\Entities\PersonelBank;

class PersonelBankImport implements ToArray, WithHeadingRow
{
    use ResponseHandler;
    
    protected $data = [];

    public function array(array $row)
    {
        $failed = [];
        $dataImport = [];
        foreach ($row as $key => $value) {
            $data = $this->mapingData($value);
            if (!$data) {
                $failed[] = $value;
            }else{
                $dataImport[] = $data;
            }
        }

        PersonelBank::insert($dataImport);

        $this->data = [
            'insert_import' => count(($dataImport)),
            'failed_import' => $failed,
        ];
    }

    private function mapingData($value)
    {

        $rules = [
            'personel_id' => 'required',
            'bank_id' => 'required',
            'branch' => 'required',
            'owner' => 'required',
            'rek_number' => 'required'
        ];

        $personel = Personel::where("name", $value['name'])->first();
        $personel_id = $personel->id ?? null; 

        $bank = Bank::where("name", $value["bank"])->first();
        $bank_id = $bank->id ?? null; 

        $requestData = [
            'personel_id' => $personel_id, // ID personel yang berkaitan
            'bank_id' => $bank_id,
            'branch' => $value['branch'],
            'owner' => $value['owner'],
            'rek_number' => $value['rek_number'],
            'id' => (string) Str::uuid(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $requestData = new Request($requestData);
        $requestData = $requestData->all();
        $validator = Validator::make($requestData, $rules);

        if ($validator->fails()) {
            return false;
        }else{
            return $requestData;
        }

    }

    public function getData(): array
    {
        return $this->data;
    }


}

<?php

namespace Modules\Personel\Import;

use App\Models\Address;
use App\Models\Contact;
use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Personel\Entities\Personel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Str;
use Modules\DataAcuan\Entities\Bank;
use Modules\Personel\Entities\PersonelBank;

class PersonelContactImport implements ToArray, WithHeadingRow
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

        Contact::insert($dataImport);

        $this->data = [
            'insert_import' => count(($dataImport)),
            'failed_import' => $failed,
        ];
    }

    private function mapingData($value)
    {

        $rules = [
            'parent_id' => 'required',
            'contact_type' => 'required',
            'data' => 'required'
        ];

        $personel = Personel::where("name", $value['name'])->first();
        $personel_id = $personel->id ?? null; 

        $contact_type = ['email','telephone','website'];
        if(in_array($value['contact_type'], $contact_type)){
            $contact_type = $value['contact_type'];
        }else{
            $contact_type = null;
        };

        $requestData = [
            'parent_id' => $personel_id, // ID personel yang berkaitan
            'contact_type' => $contact_type,
            'data' => $value['data'],
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

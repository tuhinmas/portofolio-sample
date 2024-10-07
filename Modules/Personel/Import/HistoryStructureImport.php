<?php

namespace Modules\Personel\Import;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Rules\UniqueFeeProductRule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonnelStructureHistory;

class HistoryStructureImport implements ToArray, WithStartRow, WithMultipleSheets
{
    use ResponseHandler;

    protected $data = [];

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function startRow(): int
    {
        return 2;
    }

    public function array(array $row)
    {
        if ($this->checkDuplicate($row)) {
           return $this->data = [
                "status" => false,
                "message" => "Ada Data Yang rusak"
           ];
        }

        $failed = [];
        $dataImport = [];
        foreach ($row as $value) {
            $mapData = $this->mapingData($value);
            if (!$mapData) {
                $failed[] = [
                    "start_date" => $value[0] != '' || $value[0] != null ? $this->convertToYMD($value[0]) : null,
                    'end_date' => $value[1] != '' || $value[1] != null? $this->convertToYMD($value[1]) : null,
                    "personel_id" => $value[2] ?? null,
                    "rmc_id" => $value[3] ?? null,
                    "asst_mdm_id" => $value[4] ?? null,
                    "mdm_id" => $value[5] ?? null,
                    "mm_id" => $value[6] ?? null
                ];
            }else{
                $dataImport[] = $mapData;
            }
        }
        
        $personelIds = array_column($dataImport, 'personel_id');

        //bulk delete
        PersonnelStructureHistory::whereIn('personel_id', $personelIds)->delete();

        //bulk inserts
        PersonnelStructureHistory::insert($dataImport);

        $this->data = [
            "status" => true,
            "message" => "Success",
            'Delete Import' => count($personelIds),
            'Insert import' => count($dataImport),
            'Failed import' => $failed,
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function mapingData($value)
    {
        $findPersonel = Personel::where('name', ($value[2] ?? null))->first();
        if ($findPersonel) {
            $array = [
                "start_date" => $value[0] != '' || $value[0] != null ? $this->convertToYMD($value[0]) : null,
                'end_date' => $value[1] != '' || $value[1] != null? $this->convertToYMD($value[1]) : null,
                "personel_id" => Personel::where('name', $value[2])->select('id')->first()->id ?? null,
                "rmc_id" => Personel::where('name', ($value[3] ?? null))->select('id')->first()->id ?? null,
                "asst_mdm_id" => Personel::where('name', ($value[4] ?? null))->select('id')->first()->id ?? null,
                "mdm_id" => Personel::where('name', ($value[5] ?? null))->select('id')->first()->id ?? null,
                "mm_id" => Personel::where('name', ($value[6] ?? null))->select('id')->first()->id ?? null
            ];

            $requestData = new Request($array);
            $requestData = $requestData->all();
            if ($this->validationUnique([
                "rmc_id" => $value[3],
                "asst_mdm_id" => $value[4],
                "mdm_id" => $value[5],
                "mm_id" => $value[6]
            ])) {
                return false;
            }else{
                return array_merge($requestData, [
                    'id' => (string) Str::uuid(),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        return false;
    }

    private function convertToYMD($numericDate) 
    {
        if (!is_numeric($numericDate)) {
            return DateTime::createFromFormat('d/m/Y', $numericDate)->format('Y-m-d');
        }

        $timestamp = ($numericDate - 25569) * 86400;
        $carbonDate = Carbon::createFromTimestamp($timestamp);
        return $carbonDate->format('Y-m-d');
    }

    private function validationUnique($dataRequest)
    {
        $dataWithoutNull = array_filter($dataRequest);
        $valueCounts = array_count_values($dataWithoutNull);
        $duplicates = [];
        foreach ($valueCounts as $value => $count) {
            if ($count > 1) {
                $keys = array_keys($dataWithoutNull, $value);
                $duplicates[$value] = $keys;
            }
        }

        if (!empty($duplicates)) {
            return true;
        }

        return false;
    }

    private function hasDuplicatePersonnelAndEndDate($data) 
    {
        $personelIds = [];
        foreach ($data as $item) {
            if (isset($item[2]) && $item[1] === null) {
                $personelId = $item[2];
                if (in_array($personelId, $personelIds)) {
                    return true; // Found a duplicate
                } else {
                    $personelIds[] = $personelId; // Add personel_id to the array
                }
            }
        }

        return false;  // No duplicate found
    }

    private function checkDuplicate($data) 
    {
        foreach ($data as $key1 => $item1) {
            foreach ($data as $key2 => $item2) {
                // Aturan 1: Jika dalam nama marketing yang sama terdapat berlakuSejak dan berakhirPada yang sama
                if ($item1[2] == $item2[2] &&
                    $item1[0] == $item2[0] &&
                    $item1[1] == $item2[1] &&
                    $key1 != $key2) {
                    // dd('a', $item1);
                    return true;
                }

                // Aturan 2: Jika masih dalam periode yang sama
                if ($item1[2] == $item2[2] &&
                    $item2[0] > $item1[0] &&
                    $item2[0] < $item1[1] &&
                    $key1 != $key2) {
                    // dd('b', $item1, $item2);
                    return true;
                }

                // Aturan 3: Jika1= null lalu0berada di antara data yang ada
                if ($item1[2] == $item2[2] &&
                    $item1[1] === null &&
                    $item2[0] <= $item1[0] &&
                    $item2[1] >= $item1[0] &&
                    $key1 != $key2) {
                    // dd('c', $item1);
                    return true;
                }

                //Aturan 4 : Tidak boleh ada marketing yang berakhir pada sama-sama null
                if ($item1[1] === null &&
                    $item2[1] === null &&
                    $item1[2] === $item2[2] && 
                    $key1 != $key2) {
                    // dd('d', $item1, $item2, $item1[1], $item2[1]);
                    return true;
                }
            }
        }

        // Aturan 4: Jika0= null
        foreach ($data as $item) {
            if ($item[0] === null) {
                // dd('e', $item);
                return true;
            }
        }

        return false;
    }
}
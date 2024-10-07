<?php

namespace Modules\DataAcuan\Import;

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

class FeeProductImport implements ToArray, WithStartRow
{
    protected $data = [];

    public function startRow(): int
    {
        return 2;
    }

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

        //delete fee exist
        $this->deleteFeeProduct($this->groupingByProductId($dataImport));

        //bulk inserts
        Fee::insert($dataImport);

        $this->data = [
            'Delete Import' => count($this->groupingByProductId($dataImport)),
            'Insert import' => count(($dataImport)),
            'Failed import' => $failed,
            'Product Import' => $this->groupingByProductId($dataImport)
        ];
    }

    private function groupingByProductId($array)
    {
        $groupedArray = [];
        foreach ($array as $item) {
            $year = $item['year'];
            $quartal = $item['quartal'];
            $product_id = $item['product_id'];
            $key = $year . '_' . $quartal . '_' . $product_id;
            if (!isset($groupedArray[$key])) {
                $groupedArray[$key] = [
                    "year" => $year,
                    "quartal" => $quartal,
                    "product_id" => $product_id,
                ];
            }
        }

        return $groupedArray;
    }

    private function deleteFeeProduct($data = [])
    {
        foreach ($data as $key => $value) {
            Fee::where('product_id', $value['product_id'])
                ->where('year', $value['year'])
                ->where('quartal', $value['quartal'])
                ->delete();
        }
    }

    private function mapingData($value)
    {
        $findProduct = Product::where('name', $value[0])
                    ->where('size', $value[1])
                    ->first();
        if ($findProduct) {
            $array = [
                "year" => $value[3],
                'quartal' => $value[4],
                "type" => !is_null($value[2]) ? ($value[2] == 'Reguler' ? 1 : 2) : null,
                "product_id" => $findProduct->id ?? null,
                "quantity" => $value[5],
                "fee" => $value[6],
            ];
    
            $rules = [
                "year" => "required",
                'quartal' => "required|numeric|between:1,4",
                "type" => "required",
                "product_id" => [
                    "required",
                    "max:255",
                ],
                "quantity" => "required|numeric|min:1|max:100000000000",
                "fee" => "required|numeric|min:1|max:100000000000",
            ];
    
            $requestData = new Request($array);
            $requestData = $requestData->all();
            $validator = Validator::make($requestData, $rules);
            if ($validator->fails()) {
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

    public function getData(): array
    {
        return $this->data;
    }

}
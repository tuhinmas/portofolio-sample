<?php

namespace Modules\Personel\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Personel\Entities\PersonnelStructureHistory;

class PersonnelStructureHistoryRequest extends Request
{
    use ResponseHandler;


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            
        ];
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules() : array
    {
        return [
            // "end_date" => "required"
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator){
        $errors = $validator->errors();
        $response = $this->response("04", "invalid data send", $errors);
        throw new HttpResponseException($response);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $routeAction = substr(strstr($this->route()->getActionName(), "@"), 1);
            
            //validasi when data duplicate
            if (in_array($routeAction,['store','update'])) {
                $dataRequest = $this->all();
                unset($dataRequest['start_date'], $dataRequest['end_date'], $dataRequest['personel_id']);
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
                    $message = [];
                    foreach ($duplicates as $key => $values) {
                        $string = implode(', ', $values);
                        $message[$string] = [
                            ' data tidak boleh sama : '.$value
                        ];
                    }
                    $response = $this->response('04', 'invalid data send', $message , 422);
                    throw new HttpResponseException($response); 
                }
            }
    
            if ($routeAction == "store") {
                if ($this->storeValidation()['next'] == false) {
                    $response = $this->response('04', 'invalid data send', $this->storeValidation()['message'], 422);
                    throw new HttpResponseException($response); 
                }
            }elseif ($routeAction == 'update' && $this->history_structure) {
                if ($this->updateValidation()['next'] == false) {
                    $response = $this->response('04', 'invalid data send', $this->updateValidation()['message'], 422);
                    throw new HttpResponseException($response); 
                }
            }
        });
    }

    private function storeValidation()
    {
        $findRangeDate = PersonnelStructureHistory::where('personel_id', $this->personel_id)
            ->whereDate('start_date', $this->start_date)
            ->first();
            

        //validation if daate exist
        if ($findRangeDate) {
            return [
                "next" => false,
                "message" => [
                    "start_date" => [
                        "start_date sudah ada yang mengisi, ganti dengan tanggal diatasnya"
                    ]
                ]
            ];
        }

        if ($this->end_date) {
            $findRangeDate = PersonnelStructureHistory::where('id', '!=', $this->history_structure)
                ->where('personel_id', $this->personel_id)
                ->where(function ($query){
                    $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                        ->orWhereBetween('end_date', [$this->start_date, $this->end_date]);
                })->exists();

            if ($findRangeDate) {
                return [
                    "next" => false,
                    "message" => [
                        "end_date, start_date" => [
                            "end_date / start_date sudah ada, ganti tanggal lain"
                        ]
                    ]
                ];
            }
        }else{
            $findRangeDate = PersonnelStructureHistory::where('personel_id', $this->personel_id)
                ->whereDate('start_date', '<=', $this->start_date)
                ->whereDate('end_date', '>=', $this->start_date)
                ->first();

            if ($findRangeDate) {
                return [
                    "next" => false,
                    "message" => [
                        "start_date" => [
                            "start_data ada diantara tanggal data yang sudah ada !!"
                        ]
                    ]
                ];
            }
        }


        return  [
            "next" => true
        ];
    }

    private function updateValidation()
    {
        $existData = PersonnelStructureHistory::where('id', $this->history_structure)->first();
        if ($existData) {
            if ($existData->personel_id != $this->personel_id) {
                return [
                    "next" => false,
                    "message" => [
                        "personel_id" => [
                            "Marketing tidak dapat dirubah"
                        ]
                    ]
                ];
            }
        }

        if ($this->end_date) {
            $findRangeDate = PersonnelStructureHistory::where('id', '!=', $this->history_structure)
                ->where('personel_id', $this->personel_id)
                ->where(function ($query){
                    $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                        ->orWhereBetween('end_date', [$this->start_date, $this->end_date]);
                })->exists();

            if ($findRangeDate) {
                return [
                    "next" => false,
                    "message" => [
                        "end_date, start_date" => [
                            "end_date / start_date sudah ada, ganti tanggal lain"
                        ]
                    ]
                ];
            }

            if ($this->start_date > $this->end_date) {
                return [
                    "next" => false,
                    "message" => [
                        "start_date" => [
                            "start date lebih dari tanggal end date"
                        ]
                    ]
                ];
            }
        }else{
            $findRangeDate = PersonnelStructureHistory::where('personel_id', $this->personel_id)
                ->where('id', '!=', $this->history_structure)
                ->where('start_date', '<=', $this->start_date)
                ->where('end_date', '>=', $this->start_date)
                ->exists();

            if ($findRangeDate) {
                return [
                    "next" => false,
                    "message" => [
                        "start_date" => [
                            "start_data ada diantara tanggal data yang sudah ada !!"
                        ]
                    ]
                ];
            }
        }

        $lastActivePeriode = PersonnelStructureHistory::where('id', '!=', $this->history_structure)
            ->where('start_date', '<=', $this->start_date)
            ->where('personel_id', $this->personel_id)
            ->orderBy('start_date', 'asc')
            ->first();

        if ($lastActivePeriode && $this->end_date == null) {
            return [
                "next" => false,
                "message" => [
                    "start_date" => [
                        "start date tidak boleh kurang dari tanggal periode yang sudah ada"
                    ]
                ]
            ];
        }
        
        return [
            "next" => true,
        ];

    }
}

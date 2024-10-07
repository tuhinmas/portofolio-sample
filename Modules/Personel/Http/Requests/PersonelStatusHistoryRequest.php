<?php

namespace Modules\Personel\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelStatusHistory;

class PersonelStatusHistoryRequest extends Request
{
    use ResponseHandler;


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "start_date" => "required|date",
            "personel_id" => "required",
            "change_by" => "max:40",
            "status" => "required",

        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        return [
            "start_date" => "date",
            "end_date" => "date",
            "personel_id" => "max:40",
            "change_by" => "max:40",
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

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $response = $this->response("04", "invalid data send", $errors);
        throw new HttpResponseException($response);
    }

    public function withValidator($validator)
    {
        // check dulu position statusnya MM atau marketing biasa atau check apakah marketing mempunyai applikator ???
        // dd($this->personel_id);
        // if ($this->personel_id) {
        //     $statushistory = PersonelStatusHistory::where("personel_id", $this->personel_id)->get();
        // }

        $personelStatusHistoryId = Route::current()->parameter("personnel_status_history");

        $statushistory = null;
        if ($this->isMethod('post') && $this->personel_id) {
            $statushistory = PersonelStatusHistory::where("personel_id", $this->personel_id)->get();
        } elseif ($this->isMethod('put') && $this->personel_id) {
            $statushistory =  PersonelStatusHistory::where("personel_id", $this->personel_id)->where("id", "!=", $personelStatusHistoryId)->where("start_date", "<", $this->start_date)->get();
        }

        if ($statushistory) {

            $validator->after(function ($validator) use ($statushistory) {
                if ($this->isMethod('post') || $this->isMethod('put')) {
                    $personel = Personel::with("position")->with("personelUnder", function ($query) {
                        return $query->whereHas("position", function ($query) {
                            return $query->where("name", "Aplikator");
                        });
                    })->with("region")->findOrFail($this->personel_id);

                    // dd($personel->personelUnder);
                    if ($personel->position->name == "Marketing Manager (MM)" && $this->status == "3") {
                        if ($personel->region) {
                            $validator->errors()->add('personel_id', 
                            [
                                "error_status" => "1",
                                "error_title" => "MM masih memegang region",
                                "error_message" => "Pastikan MM tidak memegang Region terlebih dahulu!"
                            ]);
                        }

                        $position = Personel::whereHas("position", function ($query) {
                            return $query->where("name", "Marketing Manager (MM)");
                        })->where("status", "1")->with("region")->get();
                        if (count($position) <= 1) {
                            // dd(count($position));
                            $validator->errors()->add('personel_id', [
                                "error_status" => "2",
                                "error_title" => "Tidak Ditemukan Pengganti MM",
                                "error_message" => "Tidak ditemukan Marketing pengganti, penonaktifkan MM gagal"
                            ]);
                        }
                        // dd("waiitt");
                    }

                    // jika status history terakhir adalah non-active lalu ingin diupdate ke 2. Maka tidak boleh dan muncul alert
                    $personelLastHistoryStatus = PersonelStatusHistory::where("personel_id", $this->personel_id)->latest("start_date")->first()?->status;

                    if ($personelLastHistoryStatus == "3" && $this->status == "2") {
                        $validator->errors()->add('status', 'Cant update to freeze if personel last history is non-active');
                    }

                    // if ($this->start_date) {
                    //     foreach ($statushistory as $data) {
                    //         if ($this->start_date <= $data->start_date) {
                    //             $validator->errors()->add('start_date', 'tidak boleh lebih kecil atau sama dengan dari riwayat sebelumnya');
                    //         }
                    //     }
                    // }
                }
            });
        }
    }
}

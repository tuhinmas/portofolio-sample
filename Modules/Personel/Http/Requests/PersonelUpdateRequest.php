<?php

namespace Modules\Personel\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Entities\Region;
use Modules\Personel\Entities\Personel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Modules\Personel\Rules\PersonelPositionRule;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonelUpdateRequest extends FormRequest
{
    use ResponseHandler;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /* get parameter of route */
        $personel_id = Route::current()->parameters();

        return [
            "join_date" => [
                "date",
                "nullable",
            ],
            'position_id' => [
                new PersonelPositionRule($this->supervisor_id, $personel_id),
            ],

            "status" => [
                "string",
                Rule::in([1,2,3]),
            ]
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $personel = Personel::find($this->personnel);
            // dd($this->status);
            //jika dirubah 1 dan pilihan baru =1 juga sudah pernah didaftarkan
            if ($this->status == 1 && $this->is_new == 1 && $personel->personel_id_new != null) {
                return $validator->errors()->add('message_error', [
                    'title' => 'MM Pengganti sudah ada',
                    'message' => 'Marketing Sudah pernah diaktifkan dengan nama baru : ' . $personel->name,
                ]);
            }

            //jika non aktif dan resign date kosong
            if ($this->status == 3 && $this->resign_date == '') {
                return $validator->errors()->add('message_error', [
                    'title' => 'resign date required',
                    'message' => 'Tanggal Resign Tidak boleh kosong',
                ]);
            }

            //ketika non aktif dirubah ke frezze
            if ($personel->status == 3 && $this->status == 2) {
                $validator->errors()->add('message_error', [
                    'title' => 'status salah',
                    'message' => 'Tidak bisa merubah status non aktif ke frezze',
                ]);
            }
            if ($this->status == 3 && $personel->position->is_mm == true) {
                $marketingRegionIsActive = Region::where('personel_id', $personel->id)->first();
                if ($marketingRegionIsActive) {
                    //jika MM masih memegang region
                    $validator->errors()->add('message_error', [
                        'title' => 'MM Masih Memegang Region',
                        'message' => 'Pastikan MM Tidak Memegang Region terlebih dahulu!',
                    ]);
                }

                $anotherMarketingMM = Personel::whereHas('position', function ($q) {
                    $q->where('is_mm', true);
                })->whereIn('status', [1])->where('id', '<>', $personel->id)->get()->count();

                if ($anotherMarketingMM == 0) {
                    //jika hanya ada 1 MM
                    $validator->errors()->add('message_error', [
                        'title' => 'Tidak Ditemukan Pengganti MM',
                        'message' => 'Tidak ditemukan marketing pengganti, Penonaktifkan MM Gagal',
                    ]);
                }
            }
        });
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
}

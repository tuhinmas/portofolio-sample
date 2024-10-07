<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelStatusHistory;
use Modules\Personel\Events\PersonelUpdateFromHistoryPersonelStatusEvent;
use Modules\Personel\Http\Requests\PersonelStatusHistoryRequest;
use Modules\Personel\Transformers\PersonelStatusHistoryCollectionResource;
use Modules\Personel\Transformers\PersonelStatusHistoryResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class HistoryPersonelStatusController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = PersonelStatusHistory::class;
    protected $request = PersonelStatusHistoryRequest::class;
    protected $resource = PersonelStatusHistoryResource::class;
    protected $collectionResource = PersonelStatusHistoryCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            "personel.*",
            "change.*",
        ];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterAbleBy(): array
    {
        return [
            "id",
            "start_date",
            "end_date",
            "status",
            "personel_id",
            "personel.*",
            "change.*",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchAbleBy(): array
    {
        return [
            "id",
            "start_date",
            "end_date",
            "personel.*",
            "change.*",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortAbleBy(): array
    {
        return [
            "id",
            "start_date",
            "end_date",
            "status",
            "personel.*",
            "change.*",
            "created_at",
            "updated_at",
        ];
    }

    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        return $query;
    }

    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->has("disabled_pagination")) {
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function buildShowFetchQuery(Request $request, array $requestedRelations): Builder
    {
        return parent::buildShowFetchQuery($request, $requestedRelations);
    }

    public function runShowFetchQuery(Request $request, Builder $query, $key): Model
    {
        $data = $query->findOrFail($key);
        return $data;
    }

    public function beforeStore(Request $request, $model)
    {
        $date_end_for_last_data = PersonelStatusHistory::query()
            ->where("personel_id", $request->personel_id)
            ->orderByDesc('created_at')
            ->first();

        $date_end_for_last_data_applicator = PersonelStatusHistory::query()
            ->where("personel_id", $request->personel_id)
            ->whereHas("personel", function ($query) {
                return $query->whereHas("position", function ($query) {
                    return $query->where("name", "Aplikator");
                });
            })
            ->orderByDesc('created_at')
            ->first();

        if ($date_end_for_last_data_applicator?->status == "3" && $request->status == "1") {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Tidak dapat mengaktifkan kembali Aplikator ini",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }

        if ($date_end_for_last_data?->status == $request->status) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "Tidak bisa memilih status yang sama dengan sebelumnya",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }

        if (PersonelStatusHistory::where("personel_id", $request->personel_id)->orderByDesc('start_date', 'desc')->where('start_date', '>', Carbon::now()->format("Y-m-d"))->count() > 0) {
            $response = $this->response("04", "invalid data send", [
                "error_title" => "Sudah Ada Riwayat Status untuk Masa Mendatang!",
                "message" => "Anda tidak dapat mengatur status untuk masa mendatang lebih dari satu kali!",

            ], 422);
            throw new HttpResponseException($response);
        }
    }

    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->save();

        $personel = Personel::findOrFail($entity->personel_id);
        $position = Position::findOrFail($personel->position_id);

        PersonelUpdateFromHistoryPersonelStatusEvent::dispatch($personel, $position, $entity, $request);
    }

    public function beforeUpdate(Request $request, $model)
    {
        $last_data = PersonelStatusHistory::orderByDesc('start_date', 'desc')->findOrFail($model->id);

        $data_before_last = PersonelStatusHistory::where("personel_id", $model->personel_id)
            ->where("created_at", "<", function ($query) use ($model) {
                $query->select(DB::raw('MAX(created_at)'))
                    ->from('personel_status_histories')
                    ->where('id', $model->id);
            })
            ->orderByDesc('created_at')->first();

        if ($data_before_last) {
            if ($data_before_last->status == $request->status) {
                $response = $this->response("04", "invalid data send", [
                    "message" => [
                        "Tidak bisa memilih status yang sama dengan sebelumnya",
                    ],
                ], 422);
                throw new HttpResponseException($response);
            }

            $data_before_last->end_date = Carbon::parse($request->start_date)->subDay();
            $data_before_last->save();
        }
    }

    public function beforeDestroy(Request $request, $model)
    {

        $lastingData = PersonelStatusHistory::where("start_date", ">", Carbon::now())->where("id", $model->id)->first();
        // dd($lastingData);
        $personelLastHistoryStatus = PersonelStatusHistory::where("personel_id", $model->personel_id)->latest()->first()->status;

        if ($model->status == "1" && $personelLastHistoryStatus == "2") {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "can not delete this data " . "next status data is freeze",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }

        // if($model->status == "1" && $personelLastHistoryStatus == "3" && $personelLastHistoryStatus->personel->position == "Aplikator"){
        //     $response = $this->response("04", "invalid data send", [
        //         "message" => [
        //             "can not delete this data " . "next status Aplicator is active",
        //         ],
        //     ], 422);
        //     throw new HttpResponseException($response);
        // }

        if ($lastingData && ($lastingData->start_date != $model->start_date)) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "can not delete this data",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }

        $data_before_last = PersonelStatusHistory::where("personel_id", $model->personel_id)
            ->where("created_at", "<", function ($query) use ($model) {
                $query->select(DB::raw('MAX(created_at)'))
                    ->from('personel_status_histories')
                    ->where('id', $model->id);
            })
            ->orderByDesc('created_at')->first();

        if ($data_before_last) {

            $data_before_last->end_date = null;
            $data_before_last->save();

            //
            $lastingData2 = PersonelStatusHistory::where("personel_id", $model->personel_id)->orderByDesc('start_date', 'desc')->first();

            $update = Personel::findOrFail($lastingData2->personel_id);
            $update->status = $data_before_last->status;
            $update->update();
        }
    }

    public function afterStore(Request $request, $model)
    {
    }

    public function afterUpdate(Request $request, $model)
    {
    }

    public function lastDataHistoryPersonel(Request $request)
    {
        Personel::findOrFail($request->personel_id);
        $last_data = PersonelStatusHistory::when($request->has("personel_id"), function ($query) use ($request) {
            return $query->where("personel_id", $request->personel_id);
        })->orderByDesc('created_at', 'desc')->first();

        $data = [
            "personel_id" => $last_data->personel_id,
            "name_personel" => $last_data->personel->name,
            "last_status" => $last_data->status, // 1, 2,3
        ];

        return $this->response("00", "success get latest data personel history", $data);
    }
}

<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandler;
use Modules\Personel\Entities\PersonnelStructureHistory;
use Modules\Personel\Http\Requests\PersonnelStructureHistoryRequest;
use Modules\Personel\Transformers\PersonnelStructureHistoryCollectionResource;
use Modules\Personel\Transformers\PersonnelStructureHistoryResource;
use Carbon\Carbon;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Personel\Exports\PersonnelExport;
use Modules\Personel\Exports\PersonnelStrukturHistoryExport;
use Modules\Personel\Import\HistoryStructureImport;

class HistoryStructurePersonnelController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = PersonnelStructureHistory::class;
    protected $request = PersonnelStructureHistoryRequest::class;
    protected $resource = PersonnelStructureHistoryResource::class;
    protected $collectionResource = PersonnelStructureHistoryCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            '*'
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
            "personel.*",
            "rmc.*",
            "asstMdm.*",
            "mdm.*",
            "mm.*",
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
            "rmc.*",
            "asstMdm.*",
            "mdm.*",
            "mm.*",
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
            "personel.*",
            "rmc.*",
            "asstMdm.*",
            "mdm.*",
            "mm.*",
            "created_at",
            "updated_at",
        ];
    }

  
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        if ($request->has('periode')) {
            $query->where(function($query) use($request){
                $now = now(); 
                if (in_array(1, $request->periode)) {
                    $query->where(function($query) use ($now) {
                        $query->where(function($query) use ($now) {
                            $query->whereDate('start_date', '<=', $now)->whereDate('end_date', '>=', $now);
                        })->orWhere(function($query) use($now){
                            $query->whereDate('start_date', '<=', $now)->where('end_date', null);
                        });
                    });
                }
    
                if (in_array(0, $request->periode)) {
                    $query->orWhere(function($query) use ($now) {
                        $query->whereDate('end_date', '<', $now);
                    });
                }
    
                if (in_array(2, $request->periode)) {
                    $query->orWhere(function($query) use ($now) {
                        $query->whereDate('start_date', '>', $now);
                    });
                }
            });
        }

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
        $existingData = PersonnelStructureHistory::where('personel_id', $request->personel_id)->whereNull('end_date')->first();
        $lastingData = PersonnelStructureHistory::where('personel_id', $request->personel_id)->whereDate('start_date', '
        >=', $request->start_date)->orderBy('start_date', 'asc')->first();

        if ($existingData) {
            if ($request->start_date > $existingData->start_date) {
                PersonnelStructureHistory::where('id', $existingData->id)->update([
                    'end_date' => date("Y-m-d", strtotime('-1 day', strtotime($request->start_date)))
                ]);
            }else{
                $model->start_date = $model->start_date;
                $model->end_date = date("Y-m-d", strtotime('-1 day', strtotime($lastingData->start_date)));
            }
        }else{
            if ($lastingData != null && $request->start_date <= $lastingData->start_date) {
                $model->end_date = date("Y-m-d", strtotime('-1 day', strtotime($lastingData->start_date)));
            }
        }
    }
    
    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->save();
    }

    public function beforeUpdate(Request $request, $model)
    {
        $data = PersonnelStructureHistory::findOrFail($request->history_structure);
        $existingData = PersonnelStructureHistory::where('id','!=', $request->history_structure)->where('personel_id', $request->personel_id)->whereNull('end_date')->first();
        $lastingData = PersonnelStructureHistory::where('id','!=', $request->history_structure)->where('personel_id', $request->personel_id)->whereDate('start_date', '
        >=', $request->start_date)->orderBy('start_date', 'asc')->first();

        if (!$request->has('end_date') && $existingData) {
            PersonnelStructureHistory::where('id', $existingData->id)->update([
                'end_date' => date("Y-m-d", strtotime('-1 day', strtotime($request->start_date)))
            ]); 
        }elseif($lastingData && $request->start_date <= $lastingData->start_date){
            $model->end_date = date("Y-m-d", strtotime('-1 day', strtotime($lastingData->start_date))); 
        }elseif($existingData && $request->start_date > $existingData->start_date){
            PersonnelStructureHistory::where('id', $existingData->id)->update([
                'end_date' => date("Y-m-d", strtotime('-1 day', strtotime($request->start_date)))
            ]);
        }
    }

    public function import(HttpRequest $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }
        
        if (!in_array($request->file->getClientOriginalExtension(), ["xlsx", "xlsm", "xlsb", "xls"])) {
            return $this->response("00", "success","you insert invalid excel/file extension", 422);
        }
        
        try {
            ini_set('max_execution_time', 300);
            $import = new HistoryStructureImport;
            Excel::import($import, $request->file);
            $response = $import->getData();
            if ($response['status'] == false) {
                return $this->response('04', 'invalid data send', $response['message'], 422);
            }
            return $this->response("00", "success", $response);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage(), 500);
        }
    }

    public function exportMarketing(Request $request)
    {
        try {
            Excel::store(new PersonnelExport(), 'public/export/marketing/list-marketing.xlsx', 's3');
            $path = Storage::disk('s3')->url('public/export/marketing/list-marketing.xlsx');
            return $this->response("00", "success export marketing", [
                "url" => $path
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "Failed export marketing", $th->getMessage());
        }
    }

    public function exportHistoryMarketing(Request $request)
    {
        try {
            Excel::store(new PersonnelStrukturHistoryExport(), 'public/export/marketing/list-struktur-marketing.xlsx', 's3');
            $path = Storage::disk('s3')->url('public/export/marketing/list-struktur-marketing.xlsx');
            return $this->response("00", "success export marketing", [
                "url" => $path
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "Failed export marketing", $th->getMessage());
        }
    }

}

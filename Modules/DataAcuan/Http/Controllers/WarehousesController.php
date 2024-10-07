<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Concerns\DisablePagination;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\DataAcuan\Entities\Warehouse;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\DataAcuan\Entities\LogPorter;
use Modules\DataAcuan\Entities\Porter;
use Modules\DataAcuan\Http\Requests\WarehouseRequest;
use Modules\DataAcuan\Transformers\WarehouseResource;
use Modules\DataAcuan\Transformers\WarehouseCollectionResource;

class WarehousesController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = Warehouse::class;
    protected $request = WarehouseRequest::class;
    protected $resource = WarehouseResource::class;
    protected $collectionResource = WarehouseCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "province",
            "city",
            "district",
            "personel.position",
            "organisation"

        ];
    }


    public function includes(): array
    {
        return [
            "porter",
            "porter.personel",
            "porter.personel.position",
            "logPorter"
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'code',
            "name",
            "address",
            "porter.name",
            "province.id",
            "city.id",
            "district.id",
            "province.id",
            'city_id',
            'district_id',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [
            "codeNamePorter",
            "hasPorter"
        ];
    }


    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'code',
            "name",
            "address",
            "province_id",
            'city_id',
            "organisation.name",
            "personel.name",
            "telp",
            'district_id',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'code',
            "name",
            "address",
            "province_id",
            "organisation.name",
            "personel.name",
            "telp",
            'city_id',
            'district_id',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->has("disabled_pagination")) {
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

    public function attachPorter(Request $request)
    {

        try {

            $validation = Validator::make($request->all(), [
                "warehouse_id" => "required",
                "personel_id" => "array",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "invalid data send", $validation->errors());
            }

            $model = new $this->model;
            $whereHouse = $model->with("porter.personel.position")->findOrFail($request->warehouse_id);

            $this->beforePorter($whereHouse);

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $whereHouse->attachPorter()->sync($request->personel_id);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $whereHouse->load("porter.personel.position");
            return $this->response("00", "Success add porter to warehouse", $whereHouse);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to save warehouse", $th->getMessage(), 500);
        }
    }

    public function beforePorter($model)
    {
        // if whereHouse_id ada di porters berarti update
        $porter = $model->porter()->first();

        $logPorter = new LogPorter();
        $logPorter->porter_id = $porter ? $porter->id : null;
        $logPorter->warehouse_id = $model->id;
        $logPorter->personel_id = auth()->user()->personel_id;
        $logPorter->status = $porter ? "updated" : "created";
        $logPorter->save();
    }

    public function porterDestroy($id)
    {
        try {
            $model = new $this->model;

            $porter = $model->findOrFail($id)->porter()->with("personel", "warehouse")->get()->each(function ($query) use ($model) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                $query->forceDelete();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                $this->afterDestroyPorter($model);
            });
            return $this->response("00", "Success destroy porter", $porter);
        } catch (\Throwable $th) {
            return $this->response("01", "failed destory porter", $th->getMessage(), 500);
        }
    }

    public function afterDestroyPorter($model)
    {
        // Jika dihapus

        $logPorter = new LogPorter();
        $logPorter->porter_id = null;
        $logPorter->warehouse_id = $model->id;
        $logPorter->personel_id = auth()->user()->personel_id;
        $logPorter->status = "destroy";
        $logPorter->save();
    }
}

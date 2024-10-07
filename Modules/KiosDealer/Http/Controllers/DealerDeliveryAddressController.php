<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Modules\KiosDealer\Entities\DealerDeliveryAddress;
use Modules\KiosDealer\Http\Requests\DealerDeliveryAddressRequest;
use Modules\KiosDealer\Import\DeliveryAddressImport;
use Modules\KiosDealer\Transformers\DealerDeliveryAddressCollectionResource;
use Modules\KiosDealer\Transformers\DealerDeliveryAddressResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class DealerDeliveryAddressController extends Controller
{
    use ResponseHandlerV2;
    use DisableAuthorization;

    protected $model = DealerDeliveryAddress::class;
    protected $request = DealerDeliveryAddressRequest::class;
    protected $resource = DealerDeliveryAddressResource::class;
    protected $collectionResource = DealerDeliveryAddressCollectionResource::class;

    /**
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    /**
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function includes(): array
    {
        return [
            "dealer",
            "dispatchOrders",
            "province",
            "district",
            "city",
        ];
    }

    public function exposedScopes(): array
    {
        return [

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
            "province_id",
            "district_id",
            "postal_code",
            "gmaps_link",
            "created_at",
            "dealer_id",
            "telephone",
            "longitude",
            "is_active",
            "updated_at",
            "latitude",
            "address",
            "city_id",
            "name",
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "province_id",
            "district_id",
            "postal_code",
            "gmaps_link",
            "created_at",
            "dealer_id",
            "telephone",
            "longitude",
            "is_active",
            "updated_at",
            "latitude",
            "address",
            "city_id",
            "name",
        ];
    }

    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->disabled_pagination) {
            return $query
                ->when($request->limit, function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })
                ->get();
        }
        return $query->paginate($request->limit ? $request->limit : 15);
    }

    public function beforeDestroy(Request $request, $model)
    {
        if ($model->is_used) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "can not delete this data, dealer delivery address is used",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }
    }

    public function import(Request $request)
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
            $import = new DeliveryAddressImport;
            \Excel::import($import, $request->file);
            $response = $import->getData();
          return $this->response("00", "success", $response);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}

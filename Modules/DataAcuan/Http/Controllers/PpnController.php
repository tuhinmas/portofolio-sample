<?php

namespace Modules\DataAcuan\Http\Controllers;

use Carbon\Carbon;

use App\Traits\ResponseHandler;
use Modules\DataAcuan\Entities\Ppn;
use Orion\Http\Controllers\Controller;
use Modules\DataAcuan\Http\Requests\PpnRequest;
use Modules\DataAcuan\Transformers\PpnResource;
use Modules\DataAcuan\Transformers\PpnCollectionResource;
use Orion\Concerns\DisableAuthorization;

class PpnController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = Ppn::class;
    protected $request = PpnRequest::class;
    protected $resource = PpnResource::class;
    protected $collectionResource = PpnCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "user"
        ];
    }


    public function includes(): array
    {
        return [
        ];
    }

    /**
    * The attributes that are used for filtering.
    *
    * @return array
    */
    public function filterableBy() : array
    {
        return [
            'id', 
            'ppn', 
            "code_account",
            "user_id",
            'period_date', 
            'created_at', 
            'updated_at'
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
            'ppn', 
            'period_date',
            "code_account",
            "user_id",
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
            'ppn', 
            'period_date',
            "code_account",
            "user_id",
            'created_at', 
            'updated_at'
        ];
    }

    public function activePpn(){
        try {
            $ppn = Ppn::query()
                ->where("period_date", "<=", Carbon::now())
                ->orderBy("period_date", "desc")
                ->first();
            return $this->response("00", "success", $ppn);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

}

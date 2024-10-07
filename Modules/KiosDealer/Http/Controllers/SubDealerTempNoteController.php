<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealer\Entities\SubDealerTempNote;
use Modules\KiosDealer\Http\Requests\SubDealerTempNoteRequest;
use Modules\KiosDealer\Transformers\SubDealerTempNoteCollectionResource;
use Modules\KiosDealer\Transformers\SubDealerTempNoteResource;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class SubDealerTempNoteController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = SubDealerTempNote::class;
    protected $request = SubDealerTempNoteRequest::class;
    protected $resource = SubDealerTempNoteResource::class;
    protected $collectionResource = SubDealerTempNoteCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function includes(): array
    {
        return [
            "subDealerTemp",
            "personel",
            "personel.position",
        ];
    }

    /**
     * The list of available query scopes.
     *
     * @return array
     */
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
    public function filterAbleBy(): array
    {
        return [
            "sub_dealer_temp_id",
            "personel_id",
            "note",
            "status",
            "created_at",
            "updated_at",
        ];
    }

    public function searchableBy(): array
    {
        return [
            "sub_dealer_temp_id",
            "personel_id",
            "note",
            "status",
            "created_at",
            "updated_at",
        ];
    }

    public function sortAbleBy(): array
    {
        return [
            "sub_dealer_temp_id",
            "personel_id",
            "note",
            "status",
            "created_at",
            "updated_at",
        ];
    }

    public function beforeStore(Request $request, $model)
    {
        Personel::findOrFail($request->personel_id);
    }

    public function afterStore(Request $request, $model)
    {
        $sub_dealer_temp = SubDealerTemp::findOrFail($model->sub_dealer_temp_id);
        $sub_dealer_temp->status = $model->status;
        $sub_dealer_temp->save();
    }
}

<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Http\Request;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\DealerTempNote;
use Modules\KiosDealer\Events\DealerNotifRevisedDataChangeEvent;
use Modules\KiosDealer\Events\DealerNotifRevisedEvent;
use Modules\KiosDealer\Http\Requests\DealerTempNoteRequest;
use Modules\KiosDealer\Transformers\DealerTempCollectionResource;
use Modules\KiosDealer\Transformers\DealerTempResource;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;

class DealerTempNoteController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = DealerTempNote::class;
    protected $request = DealerTempNoteRequest::class;
    protected $resource = DealerTempResource::class;
    protected $collectionResource = DealerTempCollectionResource::class;

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
            "dealerTemp",
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
            "dealer_temp_id",
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
            "dealer_temp_id",
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
            "dealer_temp_id",
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
        $dealer_temp = DealerTemp::findOrFail($model->dealer_temp_id);
        $dealer_temp->status = $model->status;
        $dealer_temp->save();

        if($model->status == "revised"){
            DealerNotifRevisedEvent::dispatch($dealer_temp);
        }

        if ($model->status == "revised change") {
            DealerNotifRevisedDataChangeEvent::dispatch($dealer_temp);
        }
    }

}

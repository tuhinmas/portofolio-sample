<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use App\Traits\GmapsLinkGenerator;
use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\DealerTempNote;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Events\DealerNotifChangeRejectedEvent;
use Modules\KiosDealer\Events\DealerNotifRevisedDataChangeEvent;
use Modules\KiosDealer\Events\DealerNotifRevisedEvent;
use Modules\KiosDealer\Events\DealerNotifWaitingApprovalDataChangeEvent;
use Modules\KiosDealer\Events\DealerNotifWaitingApprovalEvent;
use Modules\KiosDealer\Http\Requests\DealerTempRequest;
use Modules\KiosDealer\Jobs\DealerTempNotificationJob;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;
use Modules\KiosDealer\Transformers\DealerTempCollectionResource;
use Modules\KiosDealer\Transformers\DealerTempResource;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class DealerTempController extends Controller
{
    use ResponseHandler;
    use GmapsLinkGenerator;
    use DisableAuthorization;

    protected $model = DealerTemp::class;
    protected $request = DealerTempRequest::class;
    protected $resource = DealerTempResource::class;
    protected $collectionResource = DealerTempCollectionResource::class;

    /**
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            'adress_detail',
            'adress_detail.province',
            'adress_detail.city',
            'adress_detail.district',
            'dealer_file',
            'grading',
            'personel',
            'entity',
            'agencyLevel',
            'dealer_file_confirmation',
            'handover',
            'statusFee',
            'dealerBank',
            'ownerBank',
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
            'dealerTempNote',
            'dealerTempNote.personel.position',
            'dealerTempNote',
            'dealerTempNoteLast.personel.position',

            'subDealerFix',
            'subDealerFix.personel.position',
            'subDealerFix.adressDetail',

            'dealerFix',
            'dealerFix.personel.position',
            'dealerFix.addressDetail',
            'dealerFix.addressDetail.province',
            'dealerFix.addressDetail.city',
            'dealerFix.addressDetail.district',
            'dealerFix.dealerBank',
            'dealerFix.ownerBank',

            'personel.position',
            'logConfirmation',
            'logConfirmation.user',
            'logConfirmation.user.profile',
            'submitedBy',
            'personel',
            'personel.position',
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
            'whereName',
            'whereOwner',
            'whereNameOrOwner',
            'whereNameOrOwnerOrDealerId',
            'dealerConfirmation',
            'supervisor',
            'region',
            'filterStatusDealer',
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
            'name',
            'submited_at',
            'owner',
            'personel_id',
            'dealer_id',
            'status',
            'agency_level_id',
            'grading_id',
            'handover_status',
            'created_at',
            'updated_at',
            'personel.name',
            'submited_by',
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
            'id',
            'name',
            'owner',
            'personel_id',
            'dealer_id',
            'status',
            'agency_level_id',
            'grading_id',
            'handover_status',
            'created_at',
            'updated_at',
            'personel.name',
            'submited_at',
            'submited_by',
        ];
    }

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
            return $query
                ->when($request->is_blocked == true, function ($query) {
                    return $query->whereNull("blocked_at");
                })
                ->when($request->limit, function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })
                ->get();
        } else {
            $paginator = $query
                ->when($request->has("sorting_column"), function ($query) use ($request) {
                    $sort_type = "desc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction == "desc" ? "asc" : "desc";
                    }

                    if ($request->sorting_column == 'submission_time') {
                        return $query->orderByRaw("ifnull(submited_at, created_at) " . $sort_type);
                    }
                })
                ->paginate($request->limit > 0 ? $request->limit : 15);
            $collection = $paginator->getCollection()->map(function ($event) {
                $event->submission_time = $event->submited_at ? Carbon::parse($event->submited_at)->startOfDay()->diffInDays(Carbon::now()->format("Y-m-d")) : $event->created_at->startOfDay()->diffInDays(Carbon::now()->format("Y-m-d"));
                return $event;
            });
            $paginator->setCollection($collection);
            return $paginator;
        }
    }

    public function countDealerTempNote(Request $request, $id)
    {
        $dealerTemp = DealerTempNote::where("dealer_temp_id", $id)->get()->collect()->count("dealer_temp_id");
        $data = [
            "count" => $dealerTemp,
        ];
        return $this->response("00", "success", $data);
    }

    public function beforeUpdate(Request $request, $model)
    {
        if ($model->status == "submission of changes" && $request->status == "wait approval") {
            $dealer_change_history = DealerChangeHistory::where("dealer_temp_id", $model->id)->first();
            if ($dealer_change_history) {
                $dealer_change_history->confirmed_by = auth()->user()->personel_id;
                $dealer_change_history->confirmed_at = Carbon::now();
            } else {
                $dealer_change_history = new DealerChangeHistory();
                $dealer_change_history->dealer_id = $model->dealer_id;
                $dealer_change_history->dealer_temp_id = $model->id;
                $dealer_change_history->submited_at = $model->submited_at;
                $dealer_change_history->submited_by = $model->submited_by;
                $dealer_change_history->confirmed_by = auth()->user()->personel_id;
                $dealer_change_history->confirmed_at = Carbon::now();
            }

            $dealer_change_history->save();

            DealerNotifWaitingApprovalDataChangeEvent::dispatch($model);
        }elseif ($request->status == "submission of changes" && $model->dealer_id) {
            $dealer_change_history = new DealerChangeHistory();

            $dealer_change_history->dealer_id = $model->dealer_id;
            $dealer_change_history->dealer_temp_id = $model->id;
            $dealer_change_history->submited_at = Carbon::now();
            $dealer_change_history->submited_by = auth()->user()->personel_id;

            $dealer_change_history->save();
        } elseif ($request->status == "submission of changes" && !$model->dealer_id) {
            return $this->response("04", "invalid data send", [
                "message" => [
                    "cannot update to submission of changes this dealer, dealer_id is null",
                ],
            ]);
        } 
    }

    public function beforeStore(Request $request, $model)
    {
        $subDealer = SubDealer::find($request->sub_dealer_id);
        $dealertemp = null;
        if ($subDealer) {
            $dealertemp = DealerTemp::query()
                ->where("sub_dealer_id", $subDealer->id)
                ->whereNotIn("status", ["filed rejected", "change rejected"])
                ->first();
        }

        if ($dealertemp || ($subDealer ? $subDealer->dealer_id : false)) {

            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "can not add this data, dealer have draft in dealer temp or already transfer to dealer",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }
    }

    public function beforeDestroy(Request $request, $model)
    {
        /* cannot delete except draft or wait approval */
        if (!in_array($model->status, ["draft", "wait approval"])) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "can note delete dealer temp except draft or wait approval",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }
    }

    public function afterDestroy(Request $request, $model)
    {
        /**
         * if status now in dealer fix not equal to
         * acceppted then update to acceppted
         */
        if ($model->dealer_id) {
            $dealer_fix = Dealer::find($model->dealer_id);
            if ($dealer_fix) {
                $dealer_fix->status = "accepted";
                $dealer_fix->save();
            }
        }

        /* update sub dealer if it was from sub dealer */
        if ($model->sub_dealer_id) {
            $sub_dealer = SubDealer::find($model->sub_dealer_id);
            if ($sub_dealer) {
                $sub_dealer->status = "accepted";
                $sub_dealer->save();
            }
        }

        /* update kios if it from kios */
        if ($model->store_id) {
            $store = Store::find($model->store_id);
            if ($store) {
                $store->status = "accepted";
                $store->save();
            }
        }
    }

    protected function performDestroy(Model $entity): void
    {
        $entity->delete();
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $personel_id = null;

        $errors = [];

        /**
         * dealer submission of change
         */
        if (array_key_exists("dealer_id", $attributes)) {
            if ($attributes["dealer_id"]) {
                $dealer_fix = Dealer::findOrFail($attributes["dealer_id"]);
                $personel_id = $dealer_fix->personel_id;
            }
        }

        $agency_level = DB::table('agency_levels')->where('name', 'R3')->first();

        if ($request->latitude && $request->longitude) {
            $attributes["gmaps_link"] = $this->generateGmapsLinkFromLatitude($attributes["latitude"], $attributes["longitude"]);
        } else {
            // $attributes["gmaps_link"] = null;
        }

        $entity->fill($attributes);
        $entity->personel_id = $personel_id;
        $entity->agency_level_id = $agency_level->id;
        if ($request->status != 'draft') {
            $entity->submited_by = auth()->user()->personel_id;
            $entity->submited_at = Carbon::now()->format("Y-m-d H:i:s");

            if ($request->status == "filed" && $entity->dealer_id) {
                $entity->status = "submission of changes";
            }
        }
        $entity->save();
    }

    public function afterStore(Request $request, $model)
    {
        if ($model->dealer_id) {
            $dealer = Dealer::query()
                ->whereHas("statusFee")
                ->find($model->dealer_id);
            if ($dealer) {
                $model->status_fee = $dealer->status_fee;
                $model->save();
            }
        }

        if ($model->sub_dealer_id) {
            $sub_dealer = SubDealer::findOrFail($model->sub_dealer_id);
            $sub_dealer->status = "transfered";
            $sub_dealer->save();
        }

        // jika kondisi draft dan ada data yg sesuai dg request maka delete yg sesuai kecuali dealer yg sdg distore
        // $dealer = Dealer::where("name", $model->name)->where("telephone",$model->telephone)->where("address",$model->address)->where("id", "!=", $model->id)->get();
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     */
    public function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return DealerTemp::findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $attributes = collect($attributes)->except("personel_id")->toArray();
        if ($request->latitude && $request->longitude) {
            $attributes["gmaps_link"] = $this->generateGmapsLinkFromLatitude($attributes["latitude"], $attributes["longitude"]);
        }

        if (!empty($attributes['submited_at'])) {
            unset($attributes['submited_at']);
        }

        if (array_key_exists("status", $attributes)) {
            if (in_array($attributes["status"], ['filed', 'submission of changes', 'filed rejected', 'change rejected', 'wait approval', 'revised', 'revised change']) && $entity->submited_at == null) {
                $entity->submited_at = Carbon::now()->format("Y-m-d H:i:s");
            }

            if (in_array($attributes["status"], ['filed', 'submission of changes'])) {
                $entity->submited_at = Carbon::now()->format("Y-m-d H:i:s");
                $entity->submited_by = auth()->user()->personel_id;

                if ($entity->dealer_id) {
                    $attributes["status"] = "submission of changes";
                }
            }
        }
        $entity->fill($attributes);
        $entity->save();
    }

    public function afterUpdate(Request $request, $model)
    {
        /**
         * rollback
         */
        if (in_array($model->status, ['filed rejected', 'change rejected'])) {
            if ($model->sub_dealer_id) {
                $model->load("subDealerFix");
                $sub_dealer = $model->subDealerFix;
                $sub_dealer->status = "accepted";
                $sub_dealer->save();
            }

            if ($model->store_id) {
                $model->load("storeFix");
                $store = $model->storeFix;
                $store->status = "accepted";
                $store->save();
            }
        }
    }

    /**
     * back up store if orion error
     *
     * @param Request $request
     * @param DealerTemp $dealer_temp
     * @return void
     */
    protected function dealerTempStore(Request $request, DealerTemp $dealer_temp)
    {
        /* pending */
        // $request->merge([
        //     "gmaps_link" => null,
        // ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'owner' => 'required|string|max:255',
            'owner_address' => 'required|string|max:255',
            'address' => 'required',
            'owner_ktp' => 'required',
            'owner_telephone' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        try {
            $personel_id = null;
            $errors = [];

            /**
             * pending
             * dealer submission of change
             */
            // if ($request->dealer_id) {
            //     $dealer_fix = Dealer::findOrFail($request->dealer_id);
            //     $personel_id = $dealer_fix->personel_id;
            //     if (!$dealer_fix->latitude && !$request->latitude) {
            //         $errors["latitude"] = [
            //             "validation.required",
            //         ];}

            //     if (!$dealer_fix->longitude && !$request->longitude) {
            //         $errors["longitude"] = [
            //             "validation.required",
            //         ];
            //     }
            // }

            /* pending */
            /* new dealer submission */
            // else {
            //     $errors["latitude"] = [
            //         "validation.required",
            //     ];
            //     $errors["longitude"] = [
            //         "validation.required",
            //     ];
            // }

            // if (collect($errors)->count()) {
            //     return $this->response("04", "invalid data send", $errors, 422);
            // }

            $agency_level = DB::table('agency_levels')->whereNull("deleted_at")->where('name', 'R3')->first();
            $grading = DB::table('gradings')->whereNull("deleted_at")->where("name", "Putih")->first();

            if ($request->latitude && $request->longitude) {
                $request["gmaps_link"] = $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude);
            }
            $dealer_temp->fill($request->all());
            $dealer_temp->personel_id = $request->personel_id ?: $personel_id;
            $dealer_temp->agency_level_id = $agency_level->id;
            $dealer_temp->grading_id = $grading ? $grading->id : null;
            $dealer_temp->submited_by = auth()->user()->personel_id;
            $dealer_temp->save();

            $dealer_temp = $dealer_temp
                ->where("id", $dealer_temp->id)
                ->with([
                    'adress_detail',
                    'adress_detail.province',
                    'adress_detail.city',
                    'adress_detail.district',
                    'dealer_file',
                    'grading',
                    'personel',
                    'entity',
                    'agencyLevel',
                    'dealer_file_confirmation',
                    'handover',
                    'statusFee',
                    'dealerBank',
                    'ownerBank',
                    'submitedBy',
                ])
                ->first();

            return $this->response("00", "success", $dealer_temp);
        } catch (\Throwable $th) {
            return $th;
            return $this->response("01", "failed", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ]);
        }
    }

    public function dupicationNoTelp(Request $request, $id)
    {
        try {
            $dealerTemp = DealerTemp::findOrFail($id);
            $store_telephone = $dealerTemp->telephone;
            $stores = DealerV2::where("telephone", $store_telephone)->where(function ($q) use ($dealerTemp) {
                $q->where('id', '!=', $dealerTemp->dealer_id)->orWhereHas("dealerTemps", function ($q) use ($dealerTemp) {
                    $q->where("id", "!=", $dealerTemp->id);
                });
            })->with("personel");

            if ($request->has('disabled_pagination')) {
                $stores = $stores->get();
            } else {
                $stores = $stores->paginate($request->limit ? $request->limit : 5);
            }
            return $this->response('00', 'Duplication dealer', $stores);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display duplication number', $th->getMessage());
        }
    }
}

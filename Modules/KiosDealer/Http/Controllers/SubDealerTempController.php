<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\GmapsLinkGenerator;
use App\Traits\OrionValidationBeforeSave;
use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerChangeHistory;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealer\Events\SubDealerFilledRejectedEvent;
use Modules\KiosDealer\Events\SubDealerNotifChangeRejectedEvent;
use Modules\KiosDealer\Http\Requests\SubDealerTempRequest;
use Modules\KiosDealer\Notifications\SubDealerSubmission;
use Modules\KiosDealer\Transformers\SubDealerTempCollectionResource;
use Modules\KiosDealer\Transformers\SubDealerTempResource;
use Modules\KiosDealerV2\Entities\SubDealerV2;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class SubDealerTempController extends Controller
{
    use ResponseHandlerV2;
    use GmapsLinkGenerator;
    use DisableAuthorization;
    use OrionValidationBeforeSave;

    protected $model = SubDealerTemp::class;
    protected $request = SubDealerTempRequest::class;
    protected $resource = SubDealerTempResource::class;
    protected $collectionResource = SubDealerTempCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            'adressDetail.marketingAreaDistrict.subRegion.Region',
            'subDealerFile',
            'adressDetail',
            'adressDetail.province',
            'adressDetail.city',
            'adressDetail.district',
            'grading',
            'personel',
            'entity',
            'handover',
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
            'logConfirmation.user.profile',
            'logConfirmation.user',
            'personel.position',
            'logConfirmation',

            'subDealerFix',
            'subDealerFix.personel.position',
            'subDealerFix.addressDetail.province',
            'subDealerFix.addressDetail.city',
            'subDealerFix.addressDetail.district',


            'submitedBy',
            'personel',
            'subDealerTempNote',
            'subDealerTempNoteLast',
            'subDealerTempNoteLast.personel.position',
            'subDealerTempNote.personel.position',
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
            'subDealerConfirmation',
            'supervisor',
            'personelBranch',
            'region',
            'filterStatusSubDealer'
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
            'personel.name',
            'subDealerFix.sub_dealer_id',
            'distributor_id',
            'sub_dealer_id',
            'status',
            'grading_id',
            'handover_status',
            'telephone',
            'submited_by',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id',
            'name',
            'submited_at',
            'owner',
            'personel_id',
            'distributor_id',
            'sub_dealer_id',
            'subDealerFix.sub_dealer_id',
            'subDealerFix.name',
            'personel.name',
            'status',
            'grading_id',
            'handover_status',
            'telephone',
            'submited_by',
            'created_at',
            'updated_at',
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
                ->when($request->limit, function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })
                ->get();
        } else {
            $paginator = $query
                ->when($request->has("sorting_column"), function ($query) use ($request) {
                    $sort_type = "desc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction == "desc" ? "desc" : "asc";
                    }

                    if ($request->sorting_column == 'submission_time') {
                        return $query->orderByRaw("ifnull(submited_at, created_at) " . $sort_type);
                    }

                    if ($request->sorting_column == 'status') {
                        // dd($sort_type);
                        return $query->orderBy(DB::raw('CASE
                        WHEN status = "draft" THEN "Draft"
                        WHEN status = "filed" THEN "Sedang diajukan"
                        WHEN status = "submission of changes" THEN "Pengajuan perubahan"
                        WHEN status = "change rejected" THEN "Pengajuan perubahan ditolak"
                        WHEN status = "filed rejected" THEN "Pengajuan ditolak"
                        WHEN status = "revised" THEN "Revisi Pengajuan Baru"
                        WHEN status = "revised change" THEN "Revisi Perubahan"
                    END'), $sort_type);
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

    public function beforeStore(Request $request, $model)
    {
        if ($request->distributor_id) {
            Dealer::findOrFail($request->distributor_id);
        }
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

        /* sub dealer submission of change */
        if (array_key_exists("sub_dealer_id", $attributes)) {
            if ($attributes["sub_dealer_id"]) {
                $sub_dealer_fix = SubDealer::findOrFail($attributes["sub_dealer_id"]);
                $personel_id = $sub_dealer_fix->personel_id;
                // if (!$sub_dealer_fix->latitude && !$request->latitude) {
                //     $errors["latitude"] = [
                //         "validation.required",
                //     ];
                // }

                // if (!$sub_dealer_fix->longitude && !$request->longitude) {
                //     $errors["longitude"] = [
                //         "validation.required",
                //     ];
                // }
            }
        }

        /* pending at the moment */

        /* new dealer submission */
        // else if (!$request->latitude || !$request->longitude) {
        //     $errors["latitude"] = [
        //         "validation.required",
        //     ];
        //     $errors["longitude"] = [
        //         "validation.required",
        //     ];
        // }

        // if (collect($errors)->count()) {
        //     $response = $this->response("04", "invalid data send", $errors, 422);
        //     throw new HttpResponseException($response);
        // }

        if ($request->latitude && $request->longitude) {
            $attributes["gmaps_link"] = $this->generateGmapsLinkFromLatitude($attributes["latitude"], $attributes["longitude"]);
        } else {
            // $attributes["gmaps_link"] = null;
        }

        $entity->fill($attributes);
        $entity->personel_id = $personel_id;
        $entity->submited_by = auth()->user()->personel_id;
        $entity->save();
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
        return SubDealerTemp::findOrFail($key);
    }

    public function beforeUpdate(Request $request, $model)
    {
        if($request->status == "submission of changes" && !$model->sub_dealer_id){
            return $this->response("04", "invalid data send", [
                "message" => [
                    "cannot update to submission of changes this sub dealer, sub_dealer_id is null",
                ],
            ]);
        }
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $personel_id_auth = auth()->user()->personel_id;
        $personel_support = Personel::query()
            ->whereNull("deleted_at")
            ->whereHas('position', function ($qqq) {
                return $qqq->whereIn('name', [
                    'administrator',
                    'Support Bagian Distributor',
                    'Support Bagian Kegiatan',
                    'Support Distributor',
                    'Support Kegiatan',
                    'Support Supervisor',
                    'Marketing Support',
                ]);
            })
            ->pluck('id')
            ->toArray();

        /**
         * notification
         */
        $personel_detail = Personel::query()
            ->where('id', $personel_id_auth)
            ->with([
                "areaMarketing" => function ($Q) {
                    return $Q->with([
                        "subRegionWithRegion" => function ($Q) {
                            return $Q->with([
                                "region",
                            ]);
                        },
                    ]);
                },
            ])
            ->first();

        /**
         * all personel support notification
         */
        $Users = User::query()
            ->whereNull("deleted_at")
            ->whereIn('personel_id', $personel_support)
            ->pluck('id')
            ->toArray();

        $notif = $personel_detail->areaMarketing ? $personel_detail->areaMarketing->subRegionWithRegion : "-";

        if (array_key_exists("status", $attributes)) {

            /* detail notification */
            $details = [
                'title_notif' => $request->status == 'submission of changes' ? 'Pengajuan Perubahan Sub Dealer' : 'Pengajuan Sub Dealer Baru ',
                'marketing_name' => $personel_detail->name,
                'area' => $notif,
                'id_data' => $entity->id,
                'kode_notif' => 'pengajuan-subdealer',
            ];

            if (in_array($attributes["status"], ['filed', 'submission of changes'])) {
                if (auth()->user()->hasAnyRole(
                    'administrator',
                    'super-admin',
                    'marketing staff',
                    'Regional Marketing (RM)',
                    'Regional Marketing Coordinator (RMC)',
                    'Marketing District Manager (MDM)',
                    'Marketing Manager (MM)',
                    'Sales Counter (SC)',
                    'Operational Manager',
                    'Distribution Channel (DC)',
                    'User Jember',
                    'Support Distributor',
                    'Support Kegiatan',
                    'Support Supervisor',
                    'Marketing Support'
                )) {
                    foreach ($Users as $key => $value) {
                        $member = User::find($value);
                        $member->notify(new SubDealerSubmission($details));
                    }
                }

                if (!$entity->submited_at) {
                    $entity->submited_at = Carbon::now()->format("Y-m-d");
                }
            }
        }

        $entity->fill($attributes);
        $entity->save();
    }

    public function afterUpdate(Request $request, $model)
    {
        if ($model->status == "filed rejected") {
            SubDealerFilledRejectedEvent::dispatch($model);
        } elseif ($model->status == "change rejected") {
            SubDealerNotifChangeRejectedEvent::dispatch($model);
        }

        if (in_array($model->status, ['filed rejected', 'change rejected']) && $model->store_id != null) {
            Store::where('id', $model->store_id)->update([
                'status' => 'accepted'
            ]);
        }

        if ($model->status == "submission of changes" && $model->sub_dealer_id) {
            $sub_dealer_change_history = new SubDealerChangeHistory();

            $sub_dealer_change_history->sub_dealer_id = $model->sub_dealer_id;
            $sub_dealer_change_history->sub_dealer_temp_id = $model->id;
            $sub_dealer_change_history->submited_at = Carbon::now();
            $sub_dealer_change_history->submited_by = auth()->user()->personel_id;

            $sub_dealer_change_history->save();
        }

    }

    public function performDestroy($model): void
    {
        $model->delete();
    }

    public function beforeDestroy(Request $request, $model)
    {
        if (!in_array($model->status, ['draft', 'filed', 'submission of changes'])) {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "sub dealer temp can not be deleted except draft or wait to confirm",
                ],
            ], 422);

            throw new HttpResponseException($response);
        }
    }

    public function afterDestroy(Request $request, $model)
    {
        if ($model->sub_dealer_id) {
            $sub_dealer = SubDealer::find($model->sub_dealer_id);
            if ($sub_dealer) {
                $sub_dealer->status = "accepted";
                $sub_dealer->save();
            }
        }

        if ($model->store_id) {
            $store = Store::find($model->store_id);
            if ($store) {
                $store->status = "accepted";
                $store->save();
            }
        }
    }


    public function dupicationNoTelp(Request $request, $id)
    {
        try {
            $subDealerTemp = SubDealerTemp::with("subDealerFix")->findOrFail($id);

            // $store_id = $store->store ? $store->store->id : null;
            $store_telephone = $subDealerTemp->telephone;

            $stores = SubDealerV2::where("telephone", $store_telephone)
                // ->where("personel_id","!=",$subDealerTemp->personel_id)
                ->where("id", "!=", $subDealerTemp->sub_dealer_id)->with("personel");

            if ($request->has('disabled_pagination')) {
                $stores = $stores->get();
            } else {
                $stores = $stores->paginate($request->limit ? $request->limit : 5);
            }

            return $this->response('00', 'Duplication sub dealer', $stores);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display duplication number', $th->getMessage());
        }
    }
}

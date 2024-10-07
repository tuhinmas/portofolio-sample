<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\CoreFarmer;
use Modules\KiosDealer\Entities\CoreFarmerTemp;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Events\StoreOnUpdateEvent;
use Modules\KiosDealer\Http\Requests\StoreTempRequest;
use Modules\KiosDealer\Notifications\StoreTempNotification;
use Modules\KiosDealer\Transformers\StoreTempCollectionResource;
use Modules\KiosDealer\Transformers\StoreTempResource;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class StoreTempController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = StoreTemp::class;
    protected $request = StoreTempRequest::class;
    protected $resource = StoreTempResource::class;
    protected $collectionResource = StoreTempCollectionResource::class;

    /**
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            'core_farmer',
            'personel',
            'personel.position',
            'agencyLevel',
            "province",
            "city",
            "district",
            "marketingAreaDistrict.subRegion",
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
            "store",
            "store.*",
            "store.province",
            "store.district",
            "store.city",
            "store.core_farmer",
            "telephoneReference",
            'logConfirmation',
            'logConfirmation.user',
            'logConfirmation.user.profile',
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
            'countFarmer',
            'supervisor',
            'personelBranch',
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
            'personel.status',
            'personel_id',
            'address',
            'telephone',
            'status',
            'agency_level_id',
            'status_color',
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
            'personel_id',
            'address',
            'telephone',
            'personel.name',
            'status',
            'agency_level_id',
            'status_color',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * The attributes that are used for searching.
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            'id',
            'name',
            'personel_id',
            'address',
            'telephone',
            'status',
            'agency_level_id',
            'status_color',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
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
        $data = $query
            // ->whereHas("invoice")
            ->when($request->has("sorting_column"), function ($query) use ($request) {
                $sort_type = "desc";
                if ($request->has("direction")) {
                    $sort_type = $request->direction == "desc" ? "desc" : "asc";
                }
                //enum('filed','submission of changes','filed rejected','change rejected','draft')
                if ($request->sorting_column == 'status') {
                    // dd($sort_type);
                    return $query->orderBy(DB::raw('CASE
                    WHEN status = "draft" THEN "Draft"
                    WHEN status = "filed" THEN "Sedang diajukan"
                    WHEN status = "submission of changes" THEN "Pengajuan perubahan"
                    WHEN status = "change rejected" THEN "Pengajuan perubahan ditolak"
                    WHEN status = "filed rejected" THEN "Pengajuan ditolak"
                END'), $sort_type);
                }
            })
            ->paginate($request->limit > 0 ? $request->limit : 15);
        if ($request->sort_by == 'sub_region') {
            if ($request->direction == "desc") {
                // dd("sas");
                $sortedResult = $data->getCollection()->sortByDesc(function ($item) {
                    return $item->marketingAreaDistrict?->subRegion?->name;
                })->values();
            } elseif ($request->direction == "asc") {
                $sortedResult = $data->getCollection()->sortBy(function ($item) {
                    return $item->marketingAreaDistrict?->subRegion?->name;
                })->values();
            }

            $data->setCollection($sortedResult);
        }

        return $data;
    }

    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $personel_id = $request->has("personel_id") ? $request->personel_id : auth()->user()->personel_id;

        $entity->fill($attributes);
        $entity->personel_id = $personel_id;
        $entity->save();
    }

    public function afterStore(Request $request, $model)
    {
        $personel = Personel::findOrFail($model->personel_id);
        $model->personel()->associate($personel);
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
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
    protected function runUpdateFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        $attributes = collect($attributes)->except("personel_id")->toArray();
        $entity->fill($attributes);
        $entity->save();
    }

    public function afterUpdate(Request $request, $model)
    {
        $store_temp = StoreOnUpdateEvent::dispatch($model);
    }

    public function beforeUpdate(Request $request, $model)
    {
        if ($request->status == "filed rejected") {

            $details = [
                'notification_marketing_group_id' => 8,
                'personel_id' => $model->personel_id,
                'notified_feature' => "kios",
                'notification_text' => "Pengajuan kios " . $model->name . " telah Ditolak oleh support",
                'mobile_link' => "",
                'desktop_link' => "/marketing-staff/kios",
                'data_id' => $model->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => $model->status,
                'notifiable_id' => $model->personel_id,
            ];

            $member = User::withTrashed()->where("personel_id", $model->personel_id)->first();

            if ($member) {
                $member->notify(new StoreTempNotification($details));
                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "8";
                $notification->notified_feature = "kios";
                // $notification->notifiable_id = $member->id;
                $notification->personel_id = $model->personel_id;
                $notification->notification_text = "Pengajuan kios " . $model->name . " telah Ditolak oleh support";
                $notification->mobile_link = "";
                $notification->desktop_link = "/marketing-staff/kios";
                $notification->as_marketing = true;
                $notification->status = "filed rejected";
                $notification->data_id = $model->id;
                $notification->save();
                // }
            }


            $users = User::with(['userDevices'])
                ->withTrashed()
                ->where("personel_id", $member->personel_id)
                ->get();

            $userDevices = $users->map(function ($q) {
                return $q->userDevices->map(function ($q) {
                    return $q->os_player_id;
                })->toArray();
            })->flatten()->toArray();

            $textNotif = "Pengajuan kios " . $model->name . " telah Ditolak oleh support";

            $fields = [
                "include_player_ids" => $userDevices,
                "data" => [
                    "subtitle" => "Kios",
                    "menu" => "Kios",
                    "data_id" => $model->id,
                    "mobile_link" => "",
                    "desktop_link" => "/marketing-staff/kios",
                    "notification" => $textNotif,
                    "is_supervisor" => false,
                ],
                "contents" => [
                    "en" => $textNotif,
                    "in" => $textNotif,
                ],
                "recipients" => 1,
            ];

            return OneSignal::sendPush($fields, $textNotif);
        }
    }

    public function dupicationNoTelp(Request $request, $id)
    {
        try {
            $store = StoreTemp::with("store")->findOrFail($id);

            // $store_id = $store->store ? $store->store->id : null;
            $store_telephone = $store->telephone;

            $stores = Store::where("telephone", $store_telephone)
                ->where("personel_id", $store->personel_id)
                ->where("id", "!=", $store->store_id)
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction;
                    }
                    return $QQQ->orderBy($request->sorting_column, $sort_type);
                })
                ->with("personel");

            if ($request->has('disabled_pagination')) {
                $stores = $stores->get();
            } else {
                $stores = $stores->paginate($request->limit ? $request->limit : 5);
            }

            return $this->response('00', 'Duplication store', $stores);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display duplication number', $th->getMessage());
        }
    }

    public function dupicationNoTelpCoreFarmer(Request $request, $id)
    {
        try {
            $store = StoreTemp::with("store")->findOrFail($id);

            $store_id = $store->store_id;
            // $store_telephone = $store->store ? $store->store->telephone : null;
            $personel_id = $store->personel_id;

            $telp_core_farmers = CoreFarmerTemp::where("store_temp_id", $id)->pluck("telephone")->toArray();

            $corefarmer = CoreFarmer::selectRaw("core_farmers.*,
                st.personel_id")->where("store_id", "!=", $store_id)->whereIn('core_farmers.telephone', $telp_core_farmers)
                ->leftJoin("stores as st", "core_farmers.store_id", "st.id")
                // ->whereHas("personel")
                ->where("st.personel_id", $personel_id)
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("direction")) {
                        $sort_type = $request->direction;
                    }
                    return $QQQ->orderBy($request->sorting_column, $sort_type);
                })
                ->with("store.personel");

            if ($request->has('disabled_pagination')) {
                $corefarmers = $corefarmer->get();
            } else {
                $corefarmers = $corefarmer->paginate($request->limit ? $request->limit : 15);
            }

            return $this->response('00', 'Duplication core farmer store', $corefarmers);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display duplication number', $th->getMessage());
        }
    }
}

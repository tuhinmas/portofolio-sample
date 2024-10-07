<?php

namespace Modules\KiosDealer\Http\Controllers;

use Carbon\Carbon;
use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use App\Models\ExportRequests;
use App\Traits\ResponseHandler;
use Ladumor\OneSignal\OneSignal;
use Orion\Http\Requests\Request;
use App\Traits\SuperVisorCheckV2;
use Modules\Event\Entities\Event;
use App\Traits\GmapsLinkGenerator;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Region;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ActivityPlan\Entities\ActivityPlan;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\Contest\Entities\ContestParticipant;
use Modules\KiosDealer\Entities\SubDealerExport;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\KiosDealer\Entities\SubDealerDataHistory;
use Modules\KiosDealer\Entities\SubDealerFileHistory;
use Modules\KiosDealer\Http\Requests\SubDealerRequest;
use Modules\KiosDealer\Transformers\SubDealerResource;
use Modules\KiosDealer\Entities\SubDealerChangeHistory;
use Modules\KiosDealer\Entities\SubDealerAddressHistory;
use Modules\KiosDealer\Repositories\SubDealerRepository;
use Modules\KiosDealer\Notifications\SubDealerSubmission;
use Modules\KiosDealer\Events\SubDealerNotifAcceptedEvent;
use Modules\KiosDealer\Notifications\SubDealerTempNotification;
use Modules\KiosDealer\Transformers\SubDealerCollectionResource;
use Modules\KiosDealer\Events\SubDealerRegisteredAsDealerInContestEvent;

class SubDealerController extends Controller
{
    use DisableAuthorization;
    use GmapsLinkGenerator;
    use SuperVisorCheckV2;
    use ResponseHandler;
    use ChildrenList;
    use MarketingArea;

    protected $model = SubDealer::class;
    protected $request = SubDealerRequest::class;
    protected $resource = SubDealerResource::class;
    protected $collectionResource = SubDealerCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            'adressDetail.marketingAreaDistrict.subRegion.Region',
            'adressDetail.province',
            'adressDetail.city',
            'adressDetail.district',
            'grading',
            'personel',
            'personel.position',
            'entity',
            'handover',
            'subDealerFile',
            'subDealerAddress',
            'statusFee',
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
            'dealerTemp',
            'statusFee',
            'salesOrder',
            'regionSubDealer',
            'subDealerAddress',
            'salesOrderDealer',
            'subRegionSubDealer',
            'subRegionSubDealer.subRegion',
            'regionSubDealer.subRegion.region',
            'subRegionSubDealerDeepRelation',
            'subRegionSubDealerDeepRelation.region',
            'activeContractContest',
            "subRegion"
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
            'marketing',
            'whereName',
            'filterAll',
            'applicator',
            'supervisor',
            'distributorByArea',
            'region',
            'subRegion',
            'district',
            'acceptedOnly',
            'personelBranch',
            'byDateBetween',
            'hasDealerTemp',
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
            'owner',
            'personel_id',
            'distributor_id',
            'sub_dealer_id',
            'status',
            'grading_id',
            'handover_status',
            'telephone',
            'owner_ktp',
            'dealer_id',
            'created_at',
            'updated_at',
            "personel.id",
            "personel.name",
            'address',
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
            'distributor_id',
            'sub_dealer_id',
            'status',
            'grading_id',
            'handover_status',
            'telephone',
            'owner_ktp',
            'dealer_id',
            'created_at',
            'updated_at',
            'personel.name',
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
        $query->where(function ($q) {
            $q->where('status', '!=', 'transfered')->orWhereNull('dealer_id');
        });

        if ($request->has('non_area_marketing') || $request->non_area_marketing == true) {
            $query->doesntHave('addressSubDealer.marketingAreaDistrict');
        }

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
    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        ini_set('max_execution_time', 500);

        $validator = Validator::make($request->all(), [
            "sub_dealer_or_sub_dealer_be_dealer" => "array",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        if ($request->disabled_pagination) {
            return $query
                ->when($request->sort_by_transaction_count, function ($QQQ) use ($request) {
                    return $QQQ
                        ->withCount("indirectSalesTotalAmountBasedQuarter")
                        ->orderBy("indirect_sales_total_amount_based_quarter_count", $request->direction ? $request->direction : "asc");
                })

                /* sort by last transaction */
                ->when($request->sort_by_last_transaction, function ($QQQ) use ($request) {
                    return $QQQ
                        ->when($request->sort_by_last_transaction, function ($QQQ) use ($request) {
                            return $QQQ
                                ->withAggregate("lastOrderSubDealer", "date")
                                ->orderBy("last_order_sub_dealer_date", $request->direction ? $request->direction : "desc");
                        });
                })
                ->get();
        }

        $is_mm = DB::table('personels as p')
            ->join("positions as po", "p.position_id", "po.id")
            ->whereIn("po.name", position_mm())
            ->where("p.id", auth()->user()->personel_id)
            ->where("p.status", "1")
            ->first();

        // $sub_dealer_or_sub_dealer_be_dealer = [];
        $data = $query
            ->with('haveContestRunning')
            ->when($is_mm, function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        $QQQ->where("personel_id", auth()->user()->personel_id)->orWhereNull("personel_id");
                    });
            })

            ->when($request->sub_dealer_or_sub_dealer_be_dealer, function ($query) use ($request) {
                if (in_array("1", $request->sub_dealer_or_sub_dealer_be_dealer) && !in_array("2", $request->sub_dealer_or_sub_dealer_be_dealer)) {
                    return $query->whereNull("dealer_id");
                };
                if (in_array("2", $request->sub_dealer_or_sub_dealer_be_dealer) && !in_array("1", $request->sub_dealer_or_sub_dealer_be_dealer)) {
                    return $query->whereNotNull("dealer_id");
                };
                if (in_array("1", $request->sub_dealer_or_sub_dealer_be_dealer) && in_array("2", $request->sub_dealer_or_sub_dealer_be_dealer)) {
                    return $query;
                };
            })
            ->when($request->sort_by_transaction_count, function ($QQQ) use ($request) {
                return $QQQ
                    ->withCount("indirectSalesTotalAmountBasedQuarter")
                    ->orderBy("indirect_sales_total_amount_based_quarter_count", $request->direction ? $request->direction : "asc");
            })
            ->withAggregate("subDealerTemp", "id")

            /* sort by last transaction */
            ->when($request->sort_by_last_transaction, function ($QQQ) use ($request) {
                return $QQQ
                    ->withAggregate("lastOrderSubDealer", "date")
                    ->orderBy("last_order_sub_dealer_date", $request->direction ? $request->direction : "desc");
            });

        if ($request->sorting_column == 'sort_last_order') {
            $data = $data->get()
                ->map(function ($q) {
                    $q['on_contest'] = $q->haveContestRunning ? true : false;
                    return $q;
                });

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $pageLimit = $request->limit > 0 ? $request->limit : 15;

            if ($request->order_type == 'desc') {
                $sub_dealer = collect($data)->sortByDesc('last_order_indirect_sales')->all();
            } else {
                $sub_dealer = collect($data)->sortBy('last_order_indirect_sales')->all();
            }

            // slice the current page items
            $currentItems = collect($sub_dealer)->slice($pageLimit * ($currentPage - 1), $pageLimit)->values();

            // you may not need the $path here but might be helpful..
            $path = LengthAwarePaginator::resolveCurrentPath();

            // Build the new paginator
            $sub_dealer = new LengthAwarePaginator($currentItems, count($sub_dealer), $pageLimit, $currentPage, ['path' => $path]);

            return $sub_dealer;
        } else {
            return $data->paginate($request->limit ? $request->limit : 10)->through(function ($subDealer) {
                $subDealer->on_contest = $subDealer->haveContestRunning ? true : false;
                return $subDealer;
            });
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
        $sub_dealer_id = $this->subDealerIdGeneartor();
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first()->id;

        /* default grading */
        $grading_id = DB::table('gradings')->where("default", true)->first();

        if ($request->latitude && $request->longitude) {
            $attributes["gmaps_link"] = $this->generateGmapsLinkFromLatitude($attributes["latitude"], $attributes["longitude"]);
        }

        $entity->fill($attributes);
        $entity->status_fee = $status_fee;
        $entity->sub_dealer_id = $sub_dealer_id;
        $entity->grading_id = $grading_id ? $grading_id->id : null;
        $entity->save();

        $export_request_check = DB::table('export_requests')->where("type", "subdealer")->where("status", "requested")->first();

        if (!$export_request_check) {

            ExportRequests::Create([
                "type" => "subdealer",
                "status" => "requested",
            ]);
        }
    }

    public function afterStore(Request $request, $model)
    {
        $this->notif($model);
    }

    private function notif($subDealer)
    {
        $users = User::with(['userDevices'])
            ->withTrashed()
            ->where("personel_id", $subDealer->personel_id)
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "pengajuan data $subDealer->name telah dikonfirmasi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Sub Dealer Baru",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealer->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/sub-dealer-detail/" . $subDealer->id,
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        foreach (($users ?? []) as $user) {
            $member =  User::withTrashed()->where("id", $user->id)->first();
            $detail = [
                'notification_marketing_group_id' => 6,
                'personel_id' => $subDealer->personel_id,
                'notified_feature' => "sub_dealer",
                'notification_text' => $textNotif,
                'mobile_link' => $fields["data"]["mobile_link"],
                'desktop_link' => $fields["data"]["desktop_link"],
                'data_id' => $subDealer->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => $subDealer->status,
                'notifiable_id' => $subDealer->personel_id,
            ];

            $member->notify(new SubDealerTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "6";
            $notification->notified_feature = "sub_dealer";
            $notification->notifiable_id = $user->id;
            $notification->personel_id = $subDealer->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields["data"]["mobile_link"];
            $notification->desktop_link = $fields["data"]["desktop_link"];
            $notification->as_marketing = true;
            $notification->status = $subDealer->status;
            $notification->data_id = $subDealer->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);
    }

    /**
     * generate dealer_id
     *
     * @return void
     */
    public function subDealerIdGeneartor()
    {
        try {
            $sub_dealer = SubDealer::query()
                ->orderBy('sub_dealer_id', 'desc')
                ->first();
            $sub_dealer_id = $sub_dealer->sub_dealer_id;
            $new_sub_dealer_id = (int) $sub_dealer_id + 1;
            return $new_sub_dealer_id;
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to generate sub_dealer_id', $th->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $sub_dealer = SubDealer::findOrFail($id);
            $errors = [];

            if ($request->status == 'accepted' && $sub_dealer->status == "submission of changes" && ($sub_dealer->subDealerTemp->sub_dealer_id == $sub_dealer->id)) {
                $sub_dealer_change_history = SubDealerChangeHistory::where("sub_dealer_temp_id", $sub_dealer->subDealerTemp->id)->first();

                $sub_dealer_change_history->approved_at = Carbon::now();
                $sub_dealer_change_history->approved_by = auth()->user()->personel_id;


                if ($sub_dealer_change_history->save()) {
                    $sub_dealer_data_history = new SubDealerDataHistory();
                    $sub_dealer_data_history->personel_id = $sub_dealer->personel_id;
                    $sub_dealer_data_history->sub_dealer_change_history_id = $sub_dealer_change_history->id;
                    $sub_dealer_data_history->sub_dealer_id = $sub_dealer->id;
                    $sub_dealer_data_history->name = $sub_dealer->name;
                    $sub_dealer_data_history->entity_id = $sub_dealer->entity_id;
                    $sub_dealer_data_history->prefix = $sub_dealer->prefix;
                    $sub_dealer_data_history->sufix = $sub_dealer->sufix;
                    $sub_dealer_data_history->address = $sub_dealer->address;
                    $sub_dealer_data_history->email = $sub_dealer->email;
                    $sub_dealer_data_history->telephone = $sub_dealer->telephone;
                    $sub_dealer_data_history->gmaps_link = $sub_dealer->gmaps_link;
                    $sub_dealer_data_history->owner = $sub_dealer->owner;
                    $sub_dealer_data_history->owner_address = $sub_dealer->owner_address;
                    $sub_dealer_data_history->owner_ktp = $sub_dealer->owner_ktp;
                    $sub_dealer_data_history->owner_npwp = $sub_dealer->owner_npwp;
                    $sub_dealer_data_history->owner_telephone = $sub_dealer->owner_telephone;
                    $sub_dealer_data_history->save();

                    // dd(collect($dealer->dealer_file)->count());
                    if (collect($sub_dealer->subDealerFile)->count() > 0) {
                        foreach ($sub_dealer->subDealerFile as $data) {
                            $data_file_history = new SubDealerFileHistory();
                            $data_file_history->sub_dealer_data_history_id = $sub_dealer_data_history->id;
                            $data_file_history->sub_dealer_id = $data->dealer_id;
                            $data_file_history->file_type = $data->file_type;
                            $data_file_history->data = $data->data;
                            $data_file_history->save();
                        }
                    }

                    if (collect($sub_dealer->adressDetail)->count() > 0) {
                        foreach ($sub_dealer->adressDetail as $data) {
                            $data_address_history = new SubDealerAddressHistory();
                            $data_address_history->type = $data->type;
                            $data_address_history->sub_dealer_data_history_id = $sub_dealer_data_history->id;
                            $data_address_history->parent_id = $data->parent_id;
                            $data_address_history->province_id = $data->province_id;
                            $data_address_history->city_id = $data->city_id;
                            $data_address_history->district_id = $data->district_id;
                            $data_address_history->save();
                        }
                    }
                }
            }
            // dd("cee");

            /* pending at the moment */

            // if (!$sub_dealer->latitude && !$request->latitude) {
            //     $errors["latitude"] = [
            //         "validation.required",
            //     ];
            // }

            // if (!$sub_dealer->longitude && !$request->longitude) {
            //     $errors["longitude"] = [
            //         "validation.required",
            //     ];
            // }

            // if (collect($errors)->count()) {
            //     return $this->response("04", "invalid data send", $errors, 422);
            // }

            if ($request->latitude && $request->longitude) {
                $request->merge([
                    "gmaps_link" => $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude),
                ]);
            }

            $sub_dealer->fill($request->all());
            $sub_dealer->save();

            $personel_id_auth = auth()->user()->personel_id;

            $personel_support = Personel::whereNull("deleted_at")->whereHas('position', function ($qqq) {
                return $qqq->whereIn('name', [
                    'administrator',
                    'Support Bagian Distributor',
                    'Support Bagian Kegiatan',
                    'Support Distributor',
                    'Support Kegiatan',
                    'Support Supervisor',
                    'Marketing Support',
                ]);
            })->pluck('id')->toArray();

            $personel_detail = Personel::where('id', $personel_id_auth)->with([
                "areaMarketing" => function ($Q) {
                    return $Q->with([
                        "subRegionWithRegion" => function ($Q) {
                            return $Q->with([
                                "region",
                            ]);
                        },
                    ]);
                },
            ])->first();

            $Users = User::whereNull("deleted_at")->whereIn('personel_id', $personel_support)->pluck('id')->toArray();
            $notif = $personel_detail->areaMarketing ? $personel_detail->areaMarketing->subRegionWithRegion : "-";

            $details = [
                'title_notif' => $request->status == 'submission of changes' ? 'Pengajuan Perubahan Sub Dealer ' : 'Pengajuan Sub Dealer Baru',
                'marketing_name' => $personel_detail->name,
                'area' => $notif,
                'id_data' => $id,
                'kode_notif' => 'pengajuan-perubahan-subdealer',
            ];

            if ($request->status == 'submission of changes' || $request->status == 'filed') {
                if (auth()->user()->hasAnyRole(
                    'marketing staff',
                    'Regional Marketing (RM)',
                    'Regional Marketing Coordinator (RMC)',
                    'Marketing District Manager (MDM)',
                    'Assistant MDM',
                    'Marketing Manager (MM)'
                )) {
                    foreach ($Users as $key => $value) {
                        $member = User::find($value);
                        $member->notify(new SubDealerSubmission($details));
                    }
                }
            }

            if ($request->status == "accepted") {
                SubDealerNotifAcceptedEvent::dispatch($sub_dealer);
            }

            DB::commit();
            return $this->response("00", "success status update", $sub_dealer);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->response("01", "failed to update sub dealer status", $th->getMessage());
        }
    }

    public function blockSubDealer(Request $request, $id)
    {
        try {

            $validate = Validator::make($request->all(), [
                "blocked_by" => "required|max:255",
            ]);

            if ($validate->fails()) {
                return $this->response("04", "invalid data send", $validate->errors());
            }

            $dealer = SubDealer::findOrFail($id);
            if (is_null($dealer->blocked_at)) {
                $dealer->blocked_by = $request->blocked_by;
                $dealer->blocked_at = Carbon::now();
                $dealer->save();
            } else {
                $dealer->blocked_by = null;
                $dealer->blocked_at = null;
                $dealer->save();
            }

            return $this->response('00', 'sub dealer blocked updated', $dealer);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to update sub dealer blocked updated', $th->getMessage());
        }
    }


    public function beforeClosedSubDealer(Request $request, $id)
    {

        $sub_dealer = new $this->model;
        $subDealer = $sub_dealer::with("dealerTemp", "subDealerTemp")->findOrFail($id);

        // sub dealer cannot closed if sub dealer join contest
        $contest_participant = ContestParticipant::query()
            ->where("redeem_status", 1)
            ->whereHas("contest", function ($QQQ) {
                return $QQQ
                    ->where("period_date_start", "<=", now()->format("Y-m-d"))
                    ->where("period_date_end", ">=", now()->format("Y-m-d"));
            })
            ->where("participant_status", 4)
            ->where("participation_status", "!=", 4)->where("sub_dealer_id", $id)->get()->count();

        if ($contest_participant > 0) {
            $response = $this->response("04", "invalid data send", [
                "type" => "contest",
                "message" => "Sub Dealer is taking part in a contest",

            ], 422);
            throw new HttpResponseException($response);
        }

        // sub dealer temp submision , delaer temp ada dan statusnya minimal filled
        // dealer temp ada draft bisa ditutup draftnya dihapus, 
        if (optional($subDealer->subDealerTemp)->status == "submission of changes") {
            $response = $this->response("04", "invalid data send", [
                "type" => "sub dealer submission of change",
                "message" => "Sub Dealer is currently submission of change request",

            ], 422);
            throw new HttpResponseException($response);
        }

        if (in_array(optional($subDealer->dealerTemp)->status, ['filed', 'wait approval','revised','revised change'])) {
            $response = $this->response("04", "invalid data send", [
                "type" => "sub dealer tranfered",
                "message" => "Sub Dealer tranfered",

            ], 422);
            throw new HttpResponseException($response);
        }


        $event = Event::where("sub_dealer_id", $id)->whereNull("dealer_id")->whereIn("status", ["2", "3", "4", "14", "15", "16"])->get()->count();

        if ($event > 0) {
            $response = $this->response("04", "invalid data send", [
                "type" => "event",
                "message" => "Sub Dealer in prosess event",
            ], 422);
            throw new HttpResponseException($response);
        }

        $activityPlan = ActivityPlan::where("sub_dealer_id", $id)->whereNull("dealer_id")->where("status", "filed")->first();
        if ($activityPlan) {
            $response = $this->response("04", "invalid data send", [
                "type" => "activity plan",
                "message" => "Sub Dealer in filed activity plan",
            ], 422);
            throw new HttpResponseException($response);
        }

        // check indirect 
        $salesOrderIndirect = SalesOrderV2::select("id", "status", "store_id")->where("store_id", $id)->where("status", "submited")->first();
        if ($salesOrderIndirect) {
            $response = $this->response("04", "invalid data send", [
                "type" => "sales order submited",
                "message" => "Sub Dealer in status sumbited",
            ], 422);
            throw new HttpResponseException($response);
        }
    }

    public function closedSubDealer(Request $request, $id)
    {
        $sub_dealer = new $this->model;
        $subdealer = $sub_dealer::with("subDealerTemp")->findOrFail($id);

        $validate = Validator::make($request->all(), [
            "closed_by" => "required|max:255",
        ]);

        if ($validate->fails()) {
            return $this->response("04", "invalid data send", $validate->errors());
        }

        $this->beforeClosedSubDealer($request, $id);

        // dd($subdealer);
        if (is_null($subdealer->closed_at) && is_null($subdealer->closed_by)) {
            $subdealer->closed_by = $request->closed_by;
            $subdealer->closed_at = Carbon::now();
            $subdealer->save();

            DealerTemp::where("sub_dealer_id", $subdealer->id)
            ->where("status", "draft")
            ->get()
            ->each(fn($dealer)=>$dealer->delete());

            $subdealer->subDealerTemp->where("sub_dealer_id", $subdealer->id)
            ->where("status", "draft")
            ->get()
            ->each(fn($dealer)=>$dealer->delete());

        } else {
            $response = $this->response("04", "invalid data send", [
                "message" => [
                    "sub dealer already has closed",
                ],
            ], 422);
            throw new HttpResponseException($response);
        }

        return $this->response('00', 'sub dealer closed updated', $subdealer);
    }

    /**
     * all sub dealers
     */
    public function allSubDealers(Request $request)
    {
        try {
            if ($request->has("personel_id")) {
                $request->except("scope_supervisor");
            }

            if ($request->has("scope_supervisor")) {
                $request->except("personel_id");
            }

            $MarketingAreaDistrict = MarketingAreaDistrict::when($request->has("applicator_id"), function ($query) use ($request) {
                return $query->where("applicator_id", $request->applicator_id);
            })->get()->map(function ($data) {
                return $data->id;
            });

            $all_sub_dealers = SubDealer::orderBy("name")

                /* filter by personel id / marketing */
                ->when($request->has("personel_id"), function ($Q) use ($request) {
                    $is_mm = DB::table('personels as p')
                        ->join("positions as po", "p.position_id", "po.id")
                        ->whereIn("po.name", position_mm())
                        ->where("p.id", $request->personel_id)
                        ->where("p.status", "1")
                        ->first();

                    return $Q
                        ->where(function ($QQQ) use ($request, $is_mm) {
                            return $QQQ
                                ->where("personel_id", $request->personel_id)
                                ->when($is_mm, function ($QQQ) {
                                    return $QQQ->orWhereNull("personel_id");
                                });
                        });
                })

                /* filter by supervisor */
                ->when($request->scope_supervisor, function ($Q) use ($request) {
                    return $Q->supervisor();
                })

                /* filter by region */
                ->when($request->has("region_id"), function ($QQQ) use ($request) {
                    return $QQQ->region($request->region_id);
                })

                ->when($request->is_blocked == true, function ($query) {
                    return $query->whereNull("blocked_at");
                })

                ->when($request->is_not_closed == true, function ($query) {
                    return $query->whereNull("closed_at");
                })

                /* filter accepted only */
                ->when($request->scope_accepted_only, function ($QQQ) {
                    return $QQQ->acceptedOnly();
                })

                ->when($request->sub_dealer_only, function ($QQQ) {
                    return $QQQ->whereNull("dealer_id");
                })

                /* filter by name */
                ->when($request->name, function ($QQQ) use ($request) {
                    return $QQQ->where("name", "like", "%" . $request->name . "%");
                })

                /* filter by name. or owner or sub dealer id */
                ->when($request->by_name_or_owner_or_cust_id, function ($QQQ) use ($request) {
                    return $QQQ->byNameOrOwnerOrSubDealerId($request->by_name_or_owner_or_cust_id);
                })
                ->when($request->has("applicator_id"), function ($query) use ($MarketingAreaDistrict, $request) {
                    $MarketingApplicator = Personel::findOrFail($request->applicator_id)->supervisor_id;
                    return $query->whereHas("areaDistrictSubDealer", function ($Q) use ($MarketingAreaDistrict) {
                        return $Q->whereIn("marketing_area_districts.id", $MarketingAreaDistrict);
                    })->where("personel_id", $MarketingApplicator);
                })

                /* filter sub dealer become dealer */
                ->when($request->exclude_sub_dealer_registered_as_dealer, function ($QQQ) {
                    $sub_dealer_registered_as_dealer = DB::table('sub_dealers')
                        ->whereNull("deleted_at")
                        ->where("status", "transfered")
                        ->whereNotNull("dealer_id")
                        ->get()
                        ->pluck("id")
                        ->toArray();

                    return $QQQ
                        ->whereNotIn("id", $sub_dealer_registered_as_dealer);
                })

                /* filter sub dealer currnetly applying to become dealer */
                ->when($request->exclude_sub_dealer_applying_as_dealer, function ($QQQ) {
                    return $QQQ
                        ->whereNull("dealer_id")
                        ->whereNotIn("status", ["transfered"]);
                })
                ->orderBy((($request->has('sorting_column')) ? $request->sorting_column : 'name'), (($request->has('order_type')) ? $request->order_type : 'asc'))
                ->get();

            return $this->response("00", "all sub dealers", $all_sub_dealers);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get all sub dealers", $th->getMessage());
        }
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildShowFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildShowFetchQuery($request, $requestedRelations);

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
    public function runShowFetchQuery(Request $request, Builder $query, $key): Model
    {
        $data = $query->findOrFail($key);
        $data->on_contest = $data->haveContestRunning ? true : false;
        return $data;
    }

    public function indirectSaleHitory(Request $request)
    {
        try {
            $dealer_sub_dealer = SubDealer::query()

                /* filter by personel_id */
                ->when($request->has("personel_id"), function ($QQQ) use ($request) {
                    $dealer = Dealer::query()
                        ->where("personel_id", $request->personel_id)
                        ->select("id", "prefix", "name", "sufix", "owner", "dealer_id as store_id", "telephone", "personel_id");

                    return $QQQ->where("personel_id", $request->personel_id)
                        ->union($dealer)
                        ->select("id", "prefix", "name", "sufix", "owner", "sub_dealer_id as store_id", "telephone", "personel_id");
                })

                /* filter by name */
                ->when($request->has("name"), function ($QQQ) use ($request) {
                    $dealer = Dealer::query()
                        ->where("name", "like", "%" . $request->name . "%")
                        ->select("id", "prefix", "name", "sufix", "owner", "dealer_id as store_id", "telephone", "personel_id");

                    return $QQQ->where("name", "like", "%" . $request->name . "%")
                        ->union($dealer)
                        ->select("id", "prefix", "name", "sufix", "owner", "sub_dealer_id as store_id", "telephone", "personel_id");
                })

                /* filter by personel_id */
                ->when(!$request->has("personel_id"), function ($QQQ) use ($request) {
                    $dealer = Dealer::query()
                        ->select("id", "prefix", "name", "sufix", "owner", "dealer_id as store_id", "telephone", "personel_id");
                    return $QQQ
                        ->union($dealer)
                        ->select("id", "prefix", "name", "sufix", "owner", "sub_dealer_id as store_id", "telephone", "personel_id");
                })

                /* filter by name */
                ->when(!$request->has("name"), function ($QQQ) use ($request) {
                    $dealer = Dealer::query()
                        ->select("id", "prefix", "name", "sufix", "owner", "dealer_id", "telephone", "personel_id");

                    return $QQQ
                        ->union($dealer)
                        ->select("id", "prefix", "name", "sufix", "owner", "sub_dealer_id", "telephone", "personel_id");
                })

                ->paginate($request->limit ? $request->limit : 15);
            return $this->response("00", "success, get indirect sales history", $dealer_sub_dealer);
        } catch (\Throwable $th) {
            return $this->response("01", "failed, get indirect sales history", $th->getMessage());
        }
    }

    /**
     * export dealer
     *
     * @return void
     */
    public function export(Request $request)
    {
        ini_set('max_execution_time', 500); //3 minutes
        $datenow = Carbon::now()->format('d-m-Y');

        $district_id = $this->districtListByAreaId($request->region_id);
        /* get dealer by dstrict address */
        $sub_dealer_id = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->whereIn("district_id", $district_id)
            ->where("type", "sub_dealer")
            ->get()
            ->pluck("parent_id")
            ->toArray();
        $data = SubDealerExport::query()->when($request->has("region_id"), function ($QQQ) use ($district_id) {
            return $QQQ->whereHas('addressDealer', function ($qqq) use ($district_id) {
                return $qqq->whereIn('district_id', $district_id);
            });
        })->whereIn('id', $sub_dealer_id)->whereNull("deleted_at")->get()
            ->map(function ($item, $k) {
                return (object) [
                    'sys_id_subdealer' => $item['id'],
                    'cust_id' => $item['sub_dealer_id'],
                    'subdealer_prefix' => $item['prefix'],
                    'subdealer_name' => $item['name'],
                    'subdealer_suffix' => $item['sufix'],

                    'subdealer_marketing_id' => $item['personel_id'],
                    'subdealer_marketing' => $item['personel_marketing_name'],
                    'sub_dealer_type' => $item['entity_level_name'],

                    'sub_dealer_address' => $item['address'],
                    'sub_dealer_province_id' => $item['province_id'],
                    'sub_dealer_city_id' => $item['city_id'],
                    'sub_dealer_district_id' => $item['district_id'],

                    'sub_dealer_province' => $item['province_name'],
                    'sub_dealer_city' => $item['city_name'],
                    'sub_dealer_district' => $item['district_name'],
                    'sub_dealer_telp' => $item['telephone'],
                    'sub_dealer_email' => $item['email'],
                    'sub_dealer_gmaps' => $item['gmaps_link'],

                    'owner_name' => $item['owner'],
                    'owner_address' => $item['owner_address'],
                    'owner_province_id' => $item['owner_province_id'],
                    'owner_city_id' => $item['owner_city_id'],
                    'owner_district_id' => $item['owner_district_id'],
                    'owner_province' => $item['owner_province_name'],
                    'owner_city_name' => $item['owner_city_name'],
                    'owner_district_name' => $item['owner_district_name'],

                    'owner_ktp' => $item['owner_ktp'],
                    'owner_npwp' => $item['owner_npwp'],
                    'owner_telp' => $item['owner_telephone'],

                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                    'deleted_at' => $item['deleted_at'],

                    'gps' => $item['gmaps_link'],
                    'note' => $item['note'],
                    'region' => $item['region_dealer'],
                    'sub_region' => $item['sub_region_dealer'],

                ];
            });

        return $this->response("00", "success", $data);
        // $data = (new SubDealerByRegionExport($request->region_id))->store('subdealers_'.$datenow.'.xlsx', 's3');
        // $data = (new SubDealerByRegionExport($request->region_id))->download('subdealers_'.$datenow.'.xlsx');
        // if ($data) {
        //     return $data;
        // return response()->json([
        //     "status" => "ok",
        // ]);
        // }
    }

    public function exportv2(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes

        $data = SubDealer::query()->with(
            'personel',
            'addressSubDealer.province',
            'addressSubDealer.city',
            'addressSubDealer.district',
            'addressSubDealer.marketingAreaDistrict.subRegion.personel',
            'addressSubDealer.marketingAreaDistrict.subRegion.region.personel'
        )
            ->when($request->has('name'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->name . '%')
                        ->orWhere('owner', 'like', '%' . $request->name . '%')
                        ->orWhere('sub_dealer_id', $request->name);
                });
            })->when($request->has('personnel_id'), function ($q) use ($request) {
                $q->where('sub_dealer_id', $request->personnel_id);
            })->when($request->has('personnel_id'), function ($q) use ($request) {
                $q->where('sub_dealer_id', $request->personnel_id);
            })->when($request->has('non_area_marketing') || $request->non_area_marketing == true, function ($q) use ($request) {
                $q->doesntHave('addressSubDealer.marketingAreaDistrict');
            })
            ->get()->map(function ($q) {
                $address = $q->addressSubDealer ?? [];
                $groupRmc = !empty($address->marketingAreaDistrict->subRegion->personel) ? $address->marketingAreaDistrict->subRegion->personel->name : '-';
                $groupMdm = !empty($address->marketingAreaDistrict->subRegion->region) ? $address->marketingAreaDistrict->subRegion->region->personel->name : '-';
                return [
                    "sub_dealer_id" => $q->id,
                    "cust_id" => "CUST-SUB-" . $q->sub_dealer_id,
                    "toko" => $q->prefix . ' ' . $q->name . ' ' . $q->sufix,
                    "marketing" => optional($q->personel)->name,
                    "owner" => $q->owner,
                    "telephone" => $q->telephone,
                    "sub_dealer_address" => $q->address,
                    "status" => $q->status,
                    "propinsi" => !empty($address) ? $address->province->name : '',
                    "kota_kabupaten" => !empty($address) ? $address->city->name : '',
                    "kecamatan" => !empty($address) ? $address->district->name : '',
                    "group_rmc" => $groupRmc,
                    "group_mdm" => $groupMdm,
                    "owner_ktp" => $q->owner_ktp,
                    "owner_npwp" => $q->owner_npwp,
                    "owner_address" => $q->owner_address,
                    "owner_telephone" => '0' . $q->owner_telephone,
                    "id" => $q->id,
                ];
            });

        return $this->response("00", "success", $data);
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
        return SubDealer::findOrFail($key);
    }

    public function performUpdate(Request $request, Model $entity, array $attributes): void
    {
        if (array_key_exists("latitude", $attributes) && array_key_exists("longitude", $attributes)) {
            $attributes["gmaps_link"] = $this->generateGmapsLinkFromLatitude($attributes["latitude"], $attributes["longitude"]);
        } else {
            // $attributes["gmaps_link"] = null;
        }

        $entity->fill($attributes);
        $entity->save();

        $export_request_check = DB::table('export_requests')->where("type", "subdealer")->where("status", "requested")->first();
        if (!$export_request_check) {
            ExportRequests::Create([
                "type" => "subdealer",
                "status" => "requested",
            ]);
        }
    }

    public function beforeUpdate(Request $request, $model)
    {
        if ($request->has("dealer_id")) {
            Dealer::findOrFail($request->dealer_id);
        }

        if ($request->status == 'accepted' && $model->status == "submission of changes" && ($model->subDealerTemp->sub_dealer_id == $model->id)) {
            $sub_dealer_change_history = SubDealerChangeHistory::where("sub_dealer_temp_id", $model->subDealerTemp->id)->first();

            if ($sub_dealer_change_history) {

                $sub_dealer_change_history->approved_at = Carbon::now();
                $sub_dealer_change_history->approved_by = auth()->user()->personel_id;
            } else {
                $sub_dealer_change_history = new SubDealerChangeHistory();

                $sub_dealer_change_history->sub_dealer_id = $model->id;
                $sub_dealer_change_history->sub_dealer_temp_id = $model->subDealerTemp->id;
                $sub_dealer_change_history->submited_at = $model->subDealerTemp->submited_at;
                $sub_dealer_change_history->submited_by = $model->subDealerTemp->submited_by;
            }


            if ($sub_dealer_change_history->save()) {
                $sub_dealer_data_history = new SubDealerDataHistory();
                $sub_dealer_data_history->personel_id = $model->personel_id;
                $sub_dealer_data_history->sub_dealer_change_history_id = $sub_dealer_change_history->id;
                $sub_dealer_data_history->sub_dealer_id = $model->id;
                $sub_dealer_data_history->name = $model->name;
                $sub_dealer_data_history->entity_id = $model->entity_id;
                $sub_dealer_data_history->prefix = $model->prefix;
                $sub_dealer_data_history->sufix = $model->sufix;
                $sub_dealer_data_history->address = $model->address;
                $sub_dealer_data_history->email = $model->email;
                $sub_dealer_data_history->telephone = $model->telephone;
                $sub_dealer_data_history->gmaps_link = $model->gmaps_link;
                $sub_dealer_data_history->owner = $model->owner;
                $sub_dealer_data_history->owner_address = $model->owner_address;
                $sub_dealer_data_history->owner_ktp = $model->owner_ktp;
                $sub_dealer_data_history->owner_npwp = $model->owner_npwp;
                $sub_dealer_data_history->owner_telephone = $model->owner_telephone;
                $sub_dealer_data_history->save();

                // dd(collect($dealer->dealer_file)->count());
                if (collect($model->subDealerFile)->count() > 0) {
                    foreach ($model->subDealerFile as $data) {
                        $data_file_history = new SubDealerFileHistory();
                        $data_file_history->sub_dealer_data_history_id = $sub_dealer_data_history->id;
                        $data_file_history->sub_dealer_id = $data->dealer_id;
                        $data_file_history->file_type = $data->file_type;
                        $data_file_history->data = $data->data;
                        $data_file_history->save();
                    }
                }

                if (collect($model->adressDetail)->count() > 0) {
                    foreach ($model->adressDetail as $data) {
                        $data_address_history = new SubDealerAddressHistory();
                        $data_address_history->type = $data->type;
                        $data_address_history->sub_dealer_data_history_id = $sub_dealer_data_history->id;
                        $data_address_history->parent_id = $data->parent_id;
                        $data_address_history->province_id = $data->province_id;
                        $data_address_history->city_id = $data->city_id;
                        $data_address_history->district_id = $data->district_id;
                        $data_address_history->save();
                    }
                }
            }
        }
    }

    public function afterUpdate(Request $request, $model)
    {
        /**
         * event sub dealer become dealer
         */
        if ($model->status == "transfered" && $model->dealer_id) {
            $sub_dealer_become_dealer = SubDealerRegisteredAsDealerInContestEvent::dispatch($model);
        }

        if ($model->status == "accepted") {
            SubDealerNotifAcceptedEvent::dispatch($model);
        }

        if ($model->dealer_id != null) {
            $model->delete();
        }
    }

    public function checkRequestOfChange(Request $request, $subDealerId)
    {
        try {
            $subDealerRepository = new SubDealerRepository();
            $response = $subDealerRepository->checkRequestOfChange($subDealerId);
            return $this->response("00", "success", $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to generate sub_dealer_id', $th->getMessage());
        }
    }
}

<?php

namespace Modules\Personel\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\ChildrenList;
use Illuminate\Http\Request;
use App\Traits\MarketingArea;
use App\Models\ExportRequests;
use App\Exports\PersonelExport;
use App\Traits\ResponseHandler;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Modules\DataAcuan\Entities\Region;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Support\Facades\Storage;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Religion;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\PersonelBank;
use Modules\Personel\Jobs\SyncFeePersonnel;
use Illuminate\Contracts\Support\Renderable;
use Modules\Organisation\Entities\Organisation;
use Modules\Personel\Import\PersonelBankImport;
use Modules\Personel\Events\PersonelActiveEvent;
use Modules\Personel\Events\PersonelFreezeEvent;
use Modules\Personel\Events\PersoneJoinDateEvent;
use Modules\ExportRequests\Entities\ExportRequest;
use Modules\Personel\Events\PersonelInactiveEvent;
use Modules\Personel\Import\PersonelAddressImport;
use Modules\Personel\Import\PersonelContactImport;
use Modules\Personel\Jobs\PersonelAsApplicatorJob;
use Modules\Personel\Http\Requests\RegisterRequest;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\Personel\Entities\PersonelStatusHistory;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Personel\Repositories\PersonelRepository;
use Modules\Personel\Http\Requests\PersonelUpdateRequest;
use Modules\MarketingStatusChangeLog\Entities\MarketingStatusChangeLog;
use Modules\DataAcuan\Actions\MarketingArea\RevokeApplicatorFromAreaAction;
use Modules\Personel\Actions\Marketing\MarketingDistrictSupervisorChangeAction;
use Modules\Personel\Actions\Marketing\ApplicatorDistrictSupervisorChangeAction;
use Modules\Personel\Actions\Marketing\MarketingSubRegionSupervisorChangeAction;

class PersonelController extends Controller
{
    use SuperVisorCheckV2;
    use ResponseHandler;
    use MarketingArea;
    use ChildrenList;

    public function __construct(
        PersonelBank $personel_bank,
        Organisation $organisation,
        Personel $personel,
        Position $position,
        Religion $religion,
        DealerV2 $dealerV2,
        Store $store,
        StoreTemp $store_temp
    ) {
        $this->personel_bank = $personel_bank;
        $this->organisation = $organisation;
        $this->personel = $personel;
        $this->position = $position;
        $this->religion = $religion;
        $this->dealerV2 = $dealerV2;
        $this->store = $store;
        $this->store_temp = $store_temp;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        if ($request->marketing_for == "marketing_for_sub_region") {
            $validation = Validator::make($request->all(), [
                "region_id" => "required",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "invalid data send", $validation->errors());
            }
        }

        if ($request->marketing_for == "marketing_for_district") {
            $validation = Validator::make($request->all(), [
                "sub_region_id" => "required",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "invalid data send", $validation->errors());
            }
        }

        if ($request->has("personel_branch")) {
            unset($request["scope_supervisor"]);
        }

        try {
            $now = Carbon::now()->format('Y-m-d');

            $personel = $this->personel->query()
                ->with('address', 'bank', 'contact', 'position', 'supervisor', 'organisation', 'citizenship', 'bankPersonel', 'religion', 'marketingStatusChangeLog', 'changePersonel', 'user', 'user.permissions', 'salesOrder', 'identityCard')
                ->where('name', 'like', '%' . $request->name . '%')
                ->where(function ($QQQ) use ($now) {
                    return $QQQ
                        ->whereDate('resign_date', '>=', $now)
                        ->orWhereNull('resign_date');
                })

                /* filter by supervisor */
                ->when($request->has("scope_supervisor"), function ($Q) {
                    return $Q->supervisor();
                })

                /* list marketing in level and supervisor for region */
                ->when($request->marketing_for == "marketing_for_region", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_region", null);
                })

                /* list marketing in level and supervisor for sub region */
                ->when($request->marketing_for == "marketing_for_sub_region", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_sub_region", $request->region_id);
                })

                /* list marketing in level and supervisor */
                ->when($request->marketing_for == "marketing_for_district", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_district", $request->sub_region_id);
                })

                /* scope list target marketing */
                ->when($request->list_for_target === true, function ($QQQ) use ($request) {
                    return $QQQ->listOnTarget();
                })

                ->when($request->has("non-user"), function ($Q) {
                    return $Q->whereDoesntHave('user');
                })

                ->when(!$request->has("non-user") && !$request->by_non_marketing, function ($QQQ) {
                    if (auth()->user()->hasAnyRole(
                        "Marketing Manager (MM)",
                        "Marketing Support",
                        "Operational Manager",
                        "admin",
                        'Support Bagian Distributor',
                        'Support Distributor',
                        'Support Bagian Kegiatan',
                        'Support Kegiatan',
                        'Support Supervisor',
                        'Distribution Channel (DC)',
                        'User Jember'
                    )) {
                        return $QQQ;
                    } else {
                        return $QQQ->whereHas('user');
                    }
                })

                /* filter by nik */
                ->when($request->has("nik"), function ($QQQ) use ($request) {
                    return $QQQ->where("nik", $request->nik);
                })

                /* filter by supervisor */
                ->when($request->has("scope_supervisor"), function ($QQQ) use ($request) {
                    return $QQQ->supervisor();
                })

                ->when($request->has('personel_id'), function ($QQQ) use ($request) {
                    // cek jabatan saat ini
                    $personel_detail = Personel::findOrFail($request->personel_id);
                    $personel_position = Position::findOrFail($personel_detail->position_id);
                    if ($personel_position->name == "Regional Marketing (RM)") {
                        $supervisor_position = Position::where("name", "Regional Marketing Coordinator (RMC)")->first();
                        return $QQQ->where("position_id", $supervisor_position->id);
                    } else if ($personel_position->name == "Regional Marketing Coordinator (RMC)") {
                        $supervisor_position = Position::where("name", "Assistant MDM")->first();
                        return $QQQ->where("position_id", $supervisor_position->id);
                    } else if ($personel_position->name == "Assistant MDM") {
                        $supervisor_position = Position::where("name", "Marketing District Manager (MDM)")->first();
                        return $QQQ->where("position_id", $supervisor_position->id);
                    } else {
                        $supervisor_position = Position::where("name", "Marketing Manager (MM)")->first();
                        return $QQQ->where("position_id", $supervisor_position->id);
                    }
                })

                /* filter by status */
                ->when($request->has("status"), function ($QQQ) use ($request) {
                    return $QQQ->whereIn("status", $request->status);
                })

                /* filter sales order by personel branch */
                ->when($request->personel_branch, function ($QQQ) {
                    return $QQQ->PersonelBranch();
                })

                /* filter personel non marketing */
                ->when($request->by_non_marketing, function ($QQQ) {
                    return $QQQ->whereHas("position", function ($QQQ) {
                        return $QQQ->whereNotIn("name", marketing_positions());
                    });
                })
                ->when($request->limit > 0, function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                });

            if ($request->has("division_id")) {
                $personel = $personel->whereRelation("position", "division_id", "=", $request->division_id);
            }
            $personel = $personel->get()->map(function ($q) {
                $q->can_change = ($q->status == 3 && $q->personel_id_new != null) ? false : true;
                return $q;
            });
            return $this->response('00', 'Personel index with detail', $personel);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display personel index', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('personel::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(RegisterRequest $request)
    {
        try {
            $blood_group = null;
            if ($request->has("rhesus")) {
                $blood_group = $request->blood_group . ',' . $request->rhesus;
            } else {
                $blood_group = $request->blood_group;
            }

            $this->position->findOrFail($request->position_id);
            $this->religion->findOrFail($request->religion_id);
            $this->organisation->findOrFail($request->organisation_id);

            $personel = $this->personel->firstOrCreate([
                'name' => $request->name,
                'born_date' => $request->born_date,
            ], [
                'born_place' => $request->born_place,
                'supervisor_id' => $request->supervisor_id,
                'position_id' => $request->position_id,
                'religion_id' => $request->religion_id,
                'gender' => $request->gender,
                'citizenship' => $request->citizenship,
                'organisation_id' => $request->organisation_id,
                'identity_card_type' => $request->identity_card_type,
                'identity_number' => $request->identity_number,
                'npwp' => $request->npwp,
                'blood_group' => $blood_group,
                'photo' => $request->photo,
                'join_date' => $request->join_date,
                'resign_date' => $request->resign_date,
            ]);



            if ($personel->position->name == "Aplikator") {
                MarketingAreaDistrict::where("personel_id", $personel->supervisor_id)->whereNull("applicator_id")->update([
                    "applicator_id" => $personel->id
                ]);
            }


            $export_request_check = DB::table('export_requests')
                ->where("type", "marketing_area_district")
                ->where("status", "requested")
                ->first();

            $type = "marketing_area_district";
            if (!$export_request_check) {
                ExportRequests::Create([
                    "type" => $type,
                    "status" => "requested",
                    "created_at" => now(),
                ]);
            }

            if ($personel->wasRecentlyCreated) {
                PersonelAsApplicatorJob::dispatch($personel);
            }

            $personelStatusHistory = new PersonelStatusHistory();
            $personelStatusHistory->personel_id = $personel->id;
            $personelStatusHistory->start_date = $request->join_date;
            $personelStatusHistory->end_date = null;
            $personelStatusHistory->status = "1";
            $personelStatusHistory->save();



            return $this->response('00', 'Personel saved', $personel);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to save personel', $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Request $request, $id)
    {
        if ($request->has('check_fee')) {
            $check_fee = $request->check_fee;
        } else {
            $check_fee = false;
        }
        try {
            $personel = $this->personel->findOrFail($id);
            MarketingFee::firstOrCreate([
                "personel_id" => $personel->id,
                "year" => now()->format("Y"),
                "quarter" => now()->quarter,
            ], [
                "fee_reguler_total" => 0,
                "fee_reguler_settle" => 0,
                "fee_target_total" => 0,
                "fee_target_settle" => 0,
            ]);

            PointMarketing::firstOrCreate([
                "personel_id" => $personel->id,
                "year" => now()->format("Y"),
            ], [
                "marketing_point_total" => 0,
                "marketing_point_active" => 0,
                "marketing_point_adjustment" => 0,
                "marketing_point_redeemable" => 0,
            ]);

            $personel = $this->personel->query()
                ->with([
                    "areaAplicator.subRegionWithRegion",
                    'point',
                    'address',
                    'contact',
                    'position',
                    'supervisor',
                    'organisation',
                    'citizenship',
                    'changePersonel',
                    'currentQuarterMarketingFee',
                    'personelHasBank' => function ($Q) {
                        return $Q->with("bank");
                    },
                    'religion',
                    'identityCard',
                    'district' => function ($Q) {
                        return $Q->with([
                            "district",
                            "city",
                            "province",
                            "subRegionWithRegion" => function ($Q) {
                                return $Q->with([
                                    "region",
                                ]);
                            },
                        ]);
                    },
                    'subRegion',
                    'region' => function ($Q) {
                        return $Q->with([
                            "provinceRegion",
                        ]);
                    },
                    'bankPersonel',
                    'marketingSalesActive' => function ($q) {
                        return $q->where('status', 'confirmed')
                            ->with(['sales_order_detail' => function ($q) {
                                return $q->where('marketing_point', '>', 0)->orWhere('marketing_fee', '>', 0)->orWhere('marketing_fee_reguler', '>', 0)->select('id', 'sales_order_id', 'marketing_point', 'marketing_fee', 'marketing_fee_reguler', 'unit_price', 'quantity');
                            }])
                            ->whereHas('sales_order_detail');
                    },
                    'marketingSalesActiveWithSettle' => function ($q) {
                        return $q->where('status', 'confirmed')
                            ->with(['sales_order_detail' => function ($q) {
                                return $q->where('marketing_point', '>', 0)->orWhere('marketing_fee', '>', 0)->orWhere('marketing_fee_reguler', '>', 0)->select('id', 'sales_order_id', 'marketing_point', 'marketing_fee', 'marketing_fee_reguler', 'unit_price', 'quantity');
                            }])
                            ->whereHas('sales_order_detail')
                            ->whereHas('invoice', function ($q) {
                                return $q->where('status', '!=', 'settle');
                            });
                    },
                    'user',
                ])
                // ->withAggregate('personelStatusHistory', 'start_date')
                ->withAggregate(['personelStatusHistory as personel_status_history_start_date'], 'max(start_date)')
                ->where('id', $id)
                ->first();

            $group_rmc = $this->groupRmc($id);
            $group_mdm = $this->groupMdm($id);
            $personel->group_rmc = $group_rmc;
            $personel->group_mdm = $group_mdm;
            $positionArray = [];
            if (in_array($personel->position->name, ['Regional Marketing (RM)', "Regional Marketing Coordinator (RMC)", "Marketing District Manager (MDM)", "Marketing Manager (MM)"])) {
                array_push($positionArray, "Regional Marketing (RM)");
            }
            if (in_array($personel->position->name, ["Regional Marketing Coordinator (RMC)", "Marketing District Manager (MDM)", "Marketing Manager (MM)"])) {
                array_push($positionArray, "Regional Marketing Coordinator (RMC)");
            }
            if (in_array($personel->position->name, ["Marketing District Manager (MDM)", "Marketing Manager (MM)"])) {
                array_push($positionArray, "Marketing District Manager (MDM)");
            }
            if (in_array($personel->position->name, ["Marketing Manager (MM)"])) {
                array_push($positionArray, "Marketing Manager (MM)");
            }

            if ($request->supervisor_available) {
                $supervisor_position = Position::marketing()->whereNotIn('name', $positionArray)->pluck('id')->toArray();
                $personel->position_available = Position::marketing()->orderBy('name')->select('id', 'name')->get();
                $personel->supervisor_available = Personel::whereIn('position_id', $supervisor_position)
                    ->with(['position' => function ($q) {
                        return $q->select('id', 'name');
                    }])->where('status', 1)->select('id', 'name', 'position_id', 'status')->orderBy('name')->get()->toArray();
            }

            $personel->fee_marketing = [
                "fee_total" => $personel->currentQuarterMarketingFee ? $personel->currentQuarterMarketingFee->fee_reguler_total + $personel->currentQuarterMarketingFee->fee_target_total : 0,
                "fee_active" => $personel->currentQuarterMarketingFee ? $personel->currentQuarterMarketingFee->fee_reguler_settle + $personel->currentQuarterMarketingFee->fee_target_settle : 0,
                "fee_active_pending" => $personel->currentQuarterMarketingFee ? $personel->currentQuarterMarketingFee->fee_reguler_settle_pending + $personel->currentQuarterMarketingFee->fee_target_settle_pending : 0,
            ];

            return $this->response('00', 'Personel detail', $personel);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display personel detail', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('personel::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(PersonelUpdateRequest $request, $id)
    {
        ini_set('max_execution_time', 1500);
        DB::beginTransaction();
        try {
            $personel = $this->personel->findOrFail($id);
            $personel = $this->personel->where("id", $id)
                ->with([
                    "user",
                ])
                ->first();

            $previous_join_date = $personel->join_date;
            $previous_status = $personel->status;

            /**
             * marketing position
             */
            $marketing_position = $this->position->query()
                ->whereIn("name", marketing_positions())
                ->get()
                ->pluck("id")
                ->toArray();

            /**
             * insert to Marketing Status Change Log
             */
            if ($request->has("status")) {
                if (!$personel->user && in_array($personel->position_id, $marketing_position)) {
                    if ($request->has("status")) {
                        unset($request["status"]);
                    }
                } else {
                    if (intval($personel->status) !== $request->status) {
                        $marketing_status_change_log = new MarketingStatusChangeLog();
                        $marketing_status_change_log->status = $request->status; // after status
                        // $marketing_status_change_log->after_change = $personel->status;
                        $marketing_status_change_log->before_change = $personel->status;
                        $marketing_status_change_log->modified_by = Auth::user()?->personel_id;
                        $marketing_status_change_log->personel_id = $personel->id;
                        $marketing_status_change_log->save();

                        /**
                         * log marketing freeze
                         */
                        $log_freeze = PersonelFreezeEvent::dispatch($personel, $request);

                        $date_end_for_last_data = PersonelStatusHistory::where("personel_id", $personel->id)->orderByDesc('start_date', 'desc')->first();

                        if ($date_end_for_last_data) {
                            $date_end_for_last_data->end_date = Carbon::parse($request->start_date)->subDay();
                            $date_end_for_last_data->save();
                        }

                        $personelStatusHistory = new PersonelStatusHistory();
                        $personelStatusHistory->personel_id = $personel->id;
                        $personelStatusHistory->start_date = Carbon::now()->format("Y-m-d H:i:s");
                        $personelStatusHistory->status = $request->status;
                        $personelStatusHistory->save();
                    }
                }

                switch ($request->status) {
                    case '3':
                        $personel->resign_date = date('Y-m-d', strtotime($request->resign_date)) ?? null;
                        break;

                    default:
                        break;
                }
            }
            /**
             * test code
             */
            $new_request = $request->all();
            unset($new_request["blood_group"]);
            unset($new_request["is_new"]);
            unset($new_request["rhesus"]);
            foreach ($new_request as $key => $value) {
                $personel[$key] = $value;
            }

            if ($request->has("position_id")) {
                // cari user berdasarkan personel_id
                $user = User::where('personel_id', $id)->first();
                $jabatan = Position::findOrFail($request->position_id);
                if (in_array($request->position_id, $marketing_position)) {
                    (new RevokeApplicatorFromAreaAction)($personel);
                }
                $user?->syncRoles($jabatan->name);
            }

            $blood_group = $request->blood_group;
            if ($request->has("rhesus")) {
                $blood_group = $request->blood_group . ',' . $request->rhesus;
            }
            $personel->blood_group = $blood_group ?? $personel->blood_group;

            /* marketing district supervisor change rule */
            if ($personel->supervisor_id) {
                if ($personel->isDirty('supervisor_id')) {
                    (new ApplicatorDistrictSupervisorChangeAction)($personel);
                    (new MarketingDistrictSupervisorChangeAction)($personel);
                    (new MarketingSubRegionSupervisorChangeAction)($personel);
                }
            }

            $personel->save();

            if ($personel->status == 3) {
                $status_updated = $this->personelHasArea($id);

                /**
                 * if marketing was inactive, all dealers and sub dealers, fee point
                 * area marketing, will handed over to his supervisor,
                 */
                $inactive_event = PersonelInactiveEvent::dispatch($personel, $previous_status);

                if ($status_updated) {
                    $user = User::where('personel_id', $id)->first();
                    if ($user) {
                        $user->delete();
                    }
                    DB::commit();
                    return $this->response('00', 'Personel was set to inactive and all his marketing area will taken over by his supervisor', $personel);
                } else {
                    DB::commit();
                    return $this->response('00', 'Personel updated', $personel);
                }
            } else if ($personel->status == 1) {
                PersonelActiveEvent::dispatch($personel, $request->is_new);
                if ($request->is_new == 1) {
                    $user = User::where('personel_id', $id)->first();
                    if ($user) {
                        $user->delete();
                    }
                }
            }

            // $personelStatusHistory = new PersonelStatusHistory();
            // $personelStatusHistory->start_date = $request->join_date;
            // $personelStatusHistory->end_date = null;
            // $personelStatusHistory->status = $personel->status;
            // $personelStatusHistory->save();

            /**
             * marketing join date update
             */
            if ($request->has("join_date") && $previous_join_date != $request->join_date) {
                $personel->fill($request->only("join_date"));
                $personel_update = PersoneJoinDateEvent::dispatch($previous_join_date, $personel);
            }

            $personel->unsetRelation("user");
            $personel->save();
            DB::commit();
            return $this->response('00', 'Personel updated', $personel);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->responseAsJson('01', 'failed to update personel', [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $personel = $this->personel->findOrFail($id);
            $personel->delete();
            return $this->response('00', 'Personel deleted', $personel);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to delete personel', $th->getMessage());
        }
    }

    public function allPersonel(Request $request)
    {
        if ($request->marketing_for == "marketing_for_sub_region") {
            $validation = Validator::make($request->all(), [
                "region_id" => "required",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "invalid data send", $validation->errors());
            }
        }

        if ($request->has("personel_branch")) {
            unset($request["scope_supervisor"]);
            unset($request["scope_position"]);
        }

        if ($request->marketing_for == "marketing_for_district") {
            $validation = Validator::make($request->all(), [
                "sub_region_id" => "required",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "invalid data send", $validation->errors());
            }
        }

        $position_name = [
            "Marketing Manager (MM)",
            "Marketing District Manager (MDM)",
            "Assistant MDM",
            "Regional Marketing Coordinator (RMC)",
            "Regional Marketing (RM)",
        ];

        try {
            $personel = $this->personel->query()
                ->with('address', 'bank', 'contact', 'position', 'supervisor', 'organisation', 'citizenship', 'bankPersonel', 'religion', 'changePersonel', 'salesOrder', 'identityCard', "user")
                ->where('personels.name', 'like', '%' . $request->name . '%')
                ->orderBy('name')

                /* filter by personel position */
                ->when($request->has("personel_position"), function ($q) use ($request, $position_name) {
                    if ($request->personel_position == "marketing") {
                        return $q
                            ->whereHas("position", function ($QQQ) use ($position_name) {
                                return $QQQ->whereIn("name", $position_name);
                            });
                    }
                })

                /* filter by supervisor */
                ->when($request->has("scope_supervisor"), function ($Q) {
                    return $Q->supervisor();
                })

                /* filter by personel position */
                ->when($request->has("scope_position"), function ($QQQ) use ($request) {
                    return $QQQ->listBasePosition($request->scope_position);
                })

                /* list marketing in level and supervisor for region */
                ->when($request->marketing_for == "marketing_for_region", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_region", null);
                })

                /* list marketing in level and supervisor for sub region */
                ->when($request->marketing_for == "marketing_for_sub_region", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_sub_region", $request->region_id);
                })

                /* list marketing in level and supervisor */
                ->when($request->marketing_for == "marketing_for_district", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_district", $request->sub_region_id);
                })

                /* scope list target marketing */
                ->when($request->list_for_target === true, function ($QQQ) use ($request) {
                    return $QQQ->listOnTarget();
                })

                ->when($request->has("non-user"), function ($QQQ) {
                    return $QQQ->whereDoesntHave('user');
                })

                ->when($request->has("driver"), function ($QQQ) {
                    return $QQQ->driver();
                })

                ->when(!$request->has("non-user") && !$request->by_non_marketing, function ($QQQ) {
                    if (auth()->user()->hasAnyRole(
                        "Marketing Manager (MM)",
                        "Marketing Support",
                        "Operational Manager",
                        "admin",
                        'Support Bagian Distributor',
                        'Support Distributor',
                        'Support Bagian Kegiatan',
                        'Support Kegiatan',
                        'Support Supervisor',
                        'Distribution Channel (DC)',
                        'User Jember'
                    )) {
                        return $QQQ;
                    } else {
                        return $QQQ->whereHas('user');
                    }
                })

                /* filter by nik */
                ->when($request->has("nik"), function ($QQQ) use ($request) {
                    return $QQQ->where("nik", $request->nik);
                })

                /* filter by supervisor */
                ->when($request->has("scope_supervisor"), function ($QQQ) use ($request) {
                    return $QQQ->supervisor();
                })

                ->orderBy("position_id")

                /* filter sales order by personel branch */
                ->when($request->personel_branch, function ($QQQ) {
                    return $QQQ->PersonelBranch();
                })

                /* filter by status */
                ->when($request->has("status"), function ($QQQ) use ($request) {
                    return $QQQ->whereIn("status", $request->status);
                })

                /* filter personel non marketing */
                ->when($request->by_non_marketing, function ($QQQ) {
                    return $QQQ->whereHas("position", function ($QQQ) {
                        return $QQQ->whereNotIn("name", marketing_positions());
                    });
                })
                ->paginate($request->limit ? $request->limit : 15);

            return $this->response('00', 'Personel index', $personel);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display personel index', $th);
        }
    }

    public function allPersonelV2(Request $request)
    {
        try {
            $personel = $this->personel->query()
                ->select("id", "name", "position_id", "citizenship", "organisation_id", "status", "join_date")
                ->with('address', 'contact', 'position', 'organisation', 'identityCard', "user")
                ->withAggregate("address", "detail_address")
                ->where('name', 'like', '%' . $request->name . '%')
                ->when($request->has("sorting_column"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("order_type")) {
                        $sort_type = $request->order_type;
                    }
                    if ($request->sorting_column == 'personel_name') {
                        return $QQQ->orderBy("name", $sort_type);
                    } else if ($request->sorting_column == 'organisation_name') {
                        return $QQQ->withAggregate("organisation", "name")->orderBy("organisation_name", $sort_type);
                    } else if ($request->sorting_column == 'position_name') {
                        return $QQQ->withAggregate("position", "name")->orderBy("position_name", $sort_type);
                    } else if ($request->sorting_column == 'address') {
                        return $QQQ->withAggregate("address", "detail_address")->orderBy("address_detail_address", $sort_type);
                    } else if ($request->sorting_column == 'contact') {
                        return $QQQ->withAggregate("contact", "data")->orderBy("contact_data", $sort_type);
                    } else if ($request->sorting_column == 'status') {
                        return $QQQ->orderBy("status", $sort_type);
                    } else {
                        return $QQQ->orderBy('name');
                    }
                })->orderBy('name');

            if ($request->has("disabled_pagination")) {
                $personel = $personel->get();
            } else {
                $personel = $personel->paginate($request->limit ? $request->limit : 15);
            }

            return $this->response('00', 'Personel index', $personel);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display personel index', $th);
        }
    }

    public function personelListBaseOnOthers()
    {
        $personel = auth()->user()->personel_id;
        try {
            if (empty($personel) || auth()->user()->hasRole('Marketing Support')) {
                $request = new Request;
                return $this->index($request);
            } else {
                $personels_id = $this->getChildren($personel);
                $personels = $this->personel->query()
                    ->with('address', 'bank', 'contact', 'position', 'supervisor', 'organisation', 'citizenship', 'bankPersonel', 'religion')
                    ->whereIn('id', $personels_id)
                    ->orderBy('name')
                    ->get();
                return $this->response('00', 'Personel index', $personels);
            }
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display personel index', $th->getMessage());
        }
    }

    public function getChildren($personel_id)
    {
        $personels_id = [$personel_id];
        $personel = $this->personel->with(["children" => function ($query) {
            return $query->with(["children" => function ($query) {
                return $query->with(["children" => function ($query) {
                    return $query->with(["children" => function ($query) {
                        return $query->with(["children" => function ($query) {
                            return $query->with(["children"]);
                        }]);
                    }]);
                }]);
            }]);
        }])->find($personel_id);

        foreach ($personel->children as $level1) { //mdm
            $personels_id[] = $level1->id;
            if ($level1->children != []) {
                foreach ($level1->children as $level2) { //assistant mdm
                    $personels_id[] = $level2->id;
                    if ($level2->children != []) {
                        foreach ($level2->children as $level3) { //rmc
                            $personels_id[] = $level3->id;
                            if ($level3->children != []) {
                                foreach ($level3->children as $level4) { //rm
                                    $personels_id[] = $level4->id;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $personels_id;
    }

    /**
     * response
     *
     * @param [type] $code
     * @param [type] $message
     * @param [type] $data
     * @return void
     */
    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }

    public function hasChild($model, $request)
    {
        if ($request->has('hasChild')) {
            $childQuery = $request->hasChild;
            return $model->has($request->hasChild)->withTrashed(false);
        }
        return $model;
    }

    public function PersonelIndexMinimalis(Request $request)
    {
        if ($request->marketing_for == "marketing_for_sub_region") {
            $validation = Validator::make($request->all(), [
                "region_id" => "required",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "invalid data send", $validation->errors());
            }
        }

        if ($request->marketing_for == "marketing_for_district") {
            $validation = Validator::make($request->all(), [
                "sub_region_id" => "required",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "invalid data send", $validation->errors());
            }
        }

        if ($request->has("personel_branch")) {
            unset($request["scope_supervisor"]);
        }
        try {

            $auth_personel = Auth()->user()->personel_id;

            $personels = $this->personel->query()
                ->leftJoin("positions as p", "p.id", "=", "personels.position_id")
                ->leftJoin("divisions as d", "d.id", "=", "p.division_id")
                ->leftJoin("personels as sp", "sp.id", "=", "personels.supervisor_id")
                ->select("personels.id as personel_id", "personels.name as personel_name", "p.id as position_id", "p.name as position_name", "d.id as division_id", "d.name as division_name", "sp.id as supervisor_id", "sp.name as supervisor_name", "personels.status")
                ->when($request->has("division"), function ($q) use ($request) {
                    return $q->where("d.name", $request->division);
                })

                ->when($request->has("name"), function ($q) use ($request) {
                    return $q->where("personels.name", 'like', '%' . $request->name . '%');
                })
                ->when($request->has("byApplicatorMarketing"), function ($q) use ($request, $auth_personel) {
                    return $q->where("personels.supervisor_id", $auth_personel)->where("p.name", "Aplikator");
                })
                ->when($request->has("position"), function ($q) use ($request) {
                    return $q->where("p.name", 'like', '%' . $request->position . '%');
                })

                /* list marketing in level and supervisor for region */
                ->when($request->marketing_for == "marketing_for_region", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_region", null);
                })

                /* list marketing in level and supervisor for sub region */
                ->when($request->marketing_for == "marketing_for_sub_region", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_sub_region", $request->region_id);
                })

                /* list marketing in level and supervisor */
                ->when($request->marketing_for == "marketing_for_district", function ($QQQ) use ($request) {
                    return $QQQ->supervisorInverse("marketing_for_district", $request->sub_region_id);
                })

                /* scope list target marketing */
                ->when($request->list_for_target === true, function ($QQQ) use ($request) {
                    return $QQQ->listOnTarget();
                })
                /* filter by nik */
                ->when($request->has("nik"), function ($QQQ) use ($request) {
                    return $QQQ->where("nik", $request->nik);
                })

                /* filter by supervisor */
                ->when($request->has("scope_supervisor"), function ($QQQ) use ($request) {
                    return $QQQ->supervisor();
                })

                /* filter sales order by personel branch */
                ->when($request->personel_branch, function ($QQQ) {
                    return $QQQ->PersonelBranch();
                })

                /* filter by status */
                ->when($request->has("status"), function ($QQQ) use ($request) {
                    return $QQQ->whereIn("personels.status", $request->status);
                })

                /* filter marketing only */
                ->when($request->is_marketing, function ($QQQ) {
                    return $QQQ->whereHas("position", function ($QQQ) {
                        return $QQQ->whereIn("name", marketing_positions());
                    });
                })
                ->orderBy("personels.name")
                ->get();
            return $this->response("00", "personel list minimalis data", $personels);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get personel list", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ]);
        }
    }

    /**
     * export personel
     *
     * @return void
     */
    public function export()
    {
        $data = Excel::store(new PersonelExport, 'personel.xlsx', 's3');
        if ($data) {
            return response()->json([
                "status" => "ok",
            ]);
        }
    }

    /**
     * export personel
     *
     * @return void
     */
    public function exportPersonel()
    {

        try {
            $datenow = Carbon::now()->format('d-m-Y H:I');

            $document = 'public/export/personel-' . Str::slug($datenow) . '.xlsx';
            $data = (new PersonelExport())->store($document, 's3');

            // return
            if ($data) {
                // return (new SalesOrderIndirectExport())->download('list_indirect_'.$datenow.'.xlsx');
                $s3 = Storage::disk('s3')->getAdapter()->getClient();
                $cek = $s3->doesObjectExist(env('AWS_BUCKET'), $document);

                if ($s3->doesObjectExist(env('AWS_BUCKET'), $document)) {
                    $url = $s3->getObjectUrl(env('AWS_BUCKET'), $document);

                    $export_personel = new ExportRequest();
                    $export_personel->type = "personel";
                    $export_personel->link = $url;
                    $export_personel->status = 'ready';
                    $export_personel->save();
                    // $this->info("Check Command Success " . $url);
                }

                $export_request_check = ExportRequest::where("type", "personel")->where("status", "ready")->orderBy('created_at', 'desc')->first();
                if ($export_request_check) {
                    return $this->response("00", "success to get export personel", $export_request_check);
                }
            }
        } catch (\Throwable $th) {
            return $this->response('01', 'failed', $th->getMessage());
        }
    }

    public function supervisorChild()
    {
        try {
            $personel_id = $this->getPersonel();
            return $this->response("00", "success to get supervisor child list", $personel_id);
        } catch (\Throwable $th) {
            return $this->response("00", "success to get supervisor child list", $th->getMessage());
        }
    }

    public function groupRmc($personel_id)
    {
        $district = MarketingAreaDistrict::query()
            ->with([
                "subRegionWithRegion" => function ($Q) {
                    return $Q;
                },
            ])
            ->where("personel_id", $personel_id)
            ->orderBy("district_id")
            ->first();

        $sub_region = SubRegion::query()
            ->where("personel_id", $personel_id)
            ->orderBy("created_at", "desc")
            ->first();

        if ($district) {
            return $district->subRegionWithRegion;
        } else {
            return $sub_region;
        }
    }

    public function groupMdm($personel_id)
    {
        $district = MarketingAreaDistrict::query()
            ->with([
                "subRegionWithRegion" => function ($Q) {
                    return $Q->with([
                        "region" => function ($Q) {
                            return $Q;
                        },
                    ]);
                },
            ])
            ->where("personel_id", $personel_id)
            ->orderBy("district_id")
            ->first();

        $sub_region = SubRegion::query()
            ->with([
                "region" => function ($Q) {
                    return $Q;
                },
            ])
            ->where("personel_id", $personel_id)
            ->orderBy("created_at", "desc")
            ->first();

        $region = Region::query()
            ->where("personel_id", $personel_id)
            ->orderBy("created_at", "desc")
            ->first();

        if ($district) {
            return $district->subRegionWithRegion->region;
        } else if ($sub_region) {
            return $sub_region->region;
        } elseif ($region) {
            return $region;
        }
    }

    public function areaCheck($personel_id)
    {
        $area_updated = $this->personelHasArea($personel_id);
    }

    public function addPersonelAsUser(Request $request)
    {

        $list = [];
        foreach ($request->all() as $key => $row) {

            $response = User::create($row);
            array_push($list, $response);
        }

        return $list;
    }

    public function personelChildCountNotif(Request $request)
    {
        try {
            $personel_id = $request->personel_id ?: auth()->user()->personel_id;

            $personels_id = $this->getChildren($personel_id);

            $data = $this->personel->select("id", "name", "supervisor_id")
                ->whereIn("status", ["1", "2"])
                ->with(["user" => function ($query) {
                    return $query->withCount([
                        "notification",
                    ]);
                }])
                ->whereIn("id", $personels_id)->get();
            return $this->response("00", "get marketing child success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed marketing child success", $th->getMessage());
        }
    }

    public function personelChildSupervisorCountNotif(Request $request)
    {
        try {
            $personel_id = $request->personel_id ?: auth()->user()->personel_id;

            $personels_id = $this->getChildren($personel_id);

            // sales order perlu persetujuan
            $this->personel->findOrFail($personel_id);
            $personels_id = $this->getChildren($personel_id);
            $personels_id = collect($personels_id)->reject(function ($id) use ($personel_id) {
                return $id == $personel_id;
            })
                ->toArray();

            $cek = ["total_notification_supervisor" => 0];
            $data = $this->personel->select("id", "name", "supervisor_id")
                ->with(["user" => function ($query) use ($personels_id) {
                    return $query
                        ->withCount(["notificationSupervisor"]);
                }])
                ->whereHas("user", function ($QQQ) {
                    return $QQQ->withTrashed();
                })
                ->whereIn("id", $personels_id)
                ->get();

            foreach ($data as $personel) {
                $personel->total_notification_supervisor = $personel->user->notification_supervisor_count;
            }
            return $this->response("00", "get marketing child success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed marketing child success", $th->getMessage());
        }
    }

    public function personelChildAplicatorCountNotif(Request $request)
    {
        try {
            $personel_id = $request->personel_id ?: auth()->user()->personel_id;

            // sales order perlu persetujuan
            $this->personel->findOrFail($personel_id);
            $personels_id = $this->getChildrenAplikator($personel_id);
            $personels_id = collect($personels_id)->reject(function ($id) use ($personel_id) {
                return $id == $personel_id;
            })
                ->toArray();

            $cek = ["total_notification_supervisor" => 0];
            $data = $this->personel->select("id", "name", "supervisor_id")
                ->with(["user" => function ($query) use ($personels_id) {
                    return $query
                        ->with(["notificationSupervisor"]);
                }])
                ->whereHas("user", function ($QQQ) {
                    return $QQQ->withTrashed();
                })
                ->whereIn("id", $personels_id)
                ->get();

            foreach ($data as $personel) {
                $personel->total_notification_applicator = $personel->user->notification_supervisor_count;
            }
            return $this->response("00", "get marketing child success", $data);
        } catch (\Throwable $th) {
            return $this->response("01", "failed marketing child success", $th->getMessage());
        }
    }

    public function personelTotalChildCountNotif(Request $request)
    {
        try {

            $personel_id = $request->personel_id ?: auth()->user()->personel_id;
            $this->personel->findOrFail($personel_id);
            $personels_id = $this->getChildren($personel_id);
            $personels_id = collect($personels_id)->reject(function ($id) use ($personel_id) {
                return $id == $personel_id;
            })
                ->toArray();

            $cek = ["total_notification_supervisor" => 0];
            $data = $this->personel->select("id", "name", "supervisor_id")
                ->with(["user" => function ($query) {
                    return $query->withCount(["notificationSupervisor"]);
                }])
                ->whereHas("user", function ($QQQ) {
                    return $QQQ->withTrashed();
                })
                ->whereIn("id", $personels_id)->get();

            foreach ($data as $key => $value) {
                if ($value->user) {
                    $cek["total_notification_supervisor"] += $value->user->notification_supervisor_count;
                }
            }

            return $this->response("00", "get marketing child success", $cek);
        } catch (\Throwable $th) {
            return $this->response("01", "failed marketing child success", $th->getMessage());
        }
    }

    public function marketingDupliationNoTelp($id, Request $request)
    {
        try {
            ini_set('max_execution_time', 1500); //3 minutes
            $validation = Validator::make($request->all(), [
                "telephone" => "required",
            ]);

            if ($validation->fails()) {
                return $this->response("04", "personel_id is required", $validation->errors());
            }

            // $data = [];
            $store_temp = $this->store_temp->select('id', 'name', 'telephone', 'address', 'store_id')
                ->where("personel_id", $id)
                ->where("telephone", $request->telephone)
                ->whereIn("status", ['accepted', 'submission of changes', 'transfered'])
                ->first();

            $store_id = $store_temp ? $store_temp->store_id : null;

            $store = $this->store
                ->select('id', 'name', 'telephone', 'address')
                ->when(!empty($store_id), function ($query) use ($store_id) {
                    return $query->where('id', "!=", $store_id);
                })
                ->where("personel_id", $id)
                ->where("telephone", $request->telephone)
                ->whereIn("status", ['accepted', 'submission of changes', 'transfered'])
                ->paginate($request->limit ? $request->limit : 15);

            // foreach($store as $key => $value){
            //     $count = count($value);
            //     if($count > 1){
            //         $data[$key] = $value;
            //     }
            // }
            //  $data_store = collect($data)->flatten();

            return $this->response("00", "get marketing has duplipaction notelp success", $store);
        } catch (\Throwable $th) {
            return $this->response("01", "failed get marketing has duplipaction notelp", $th->getMessage());
        }
        // $this->personel->whereHas("dealerHasMany",function($query) use ($request){
        //     $query->where("telephone", ">", 0);
        // });
    }

    public function syncFeePersonnel(Request $request)
    {

        $rules = [
            'year' => 'required',
            'personel_id' => 'required|exists:personels,id',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $error = $validator->messages();
            return $this->response("01", "failed Sync marketing Fee", $error);
        }

        try {
            $year = $request->year;
            $personelId = $request->personel_id;
            SyncFeePersonnel::dispatchSync($year, $personelId);
            return $this->response("00", "sync Success", []);
        } catch (\Throwable $th) {
            return $this->response("01", "failed Sync marketing Fee", $th->getMessage());
        }
    }

    public function importAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        if (!in_array($request->file->getClientOriginalExtension(), ["xlsx", "xlsm", "xlsb", "xls"])) {
            return $this->response("00", "success", "you insert invalid excel/file extension", 422);
        }

        try {
            ini_set('max_execution_time', 300);
            $import = new PersonelAddressImport;
            Excel::import($import, $request->file);
            $response = $import->getData();
            return $this->response("00", "success", $response);
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function importBank(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        if (!in_array($request->file->getClientOriginalExtension(), ["xlsx", "xlsm", "xlsb", "xls"])) {
            return $this->response("00", "success", "you insert invalid excel/file extension", 422);
        }

        try {
            ini_set('max_execution_time', 300);
            $import = new PersonelBankImport;
            Excel::import($import, $request->file);
            $response = $import->getData();
            return $this->response("00", "success", $response);
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function importContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => "required",
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors(), 422);
        }

        if (!in_array($request->file->getClientOriginalExtension(), ["xlsx", "xlsm", "xlsb", "xls"])) {
            return $this->response("00", "success", "you insert invalid excel/file extension", 422);
        }

        try {
            ini_set('max_execution_time', 300);
            $import = new PersonelContactImport;
            Excel::import($import, $request->file);
            $response = $import->getData();
            return $this->response("00", "success", $response);
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function personelCheckDisable($personelId)
    {
        try {
            $personel = new PersonelRepository();
            $response = $personel->checkPersonelDisable($personelId);
            return $this->response("00", "success", $response);
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function fetchDataSimple(Request $request)
    {
        try {
            $personel = new PersonelRepository();
            $response = $personel->fetchPersonnelSimple($request->all());
            return $this->response('00', 'Personel index with detail', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display personel index', $th->getMessage());
        }
    }

    public function storeCoverage(Request $request, $personelId)
    {
        if (!Personel::find($personelId)) {
            return $this->response("04", "invalid data send", 'Cant Find personel', 422);
        }

        try {
            $personel = new PersonelRepository();
            $response = $personel->storeCoverage($request->all(), $personelId);
            return $this->response('00', 'store coverage data', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display store coverage', $th->getMessage());
        }
    }

    public function storeCoverageFilter(Request $request, $personelId)
    {
        if (!Personel::find($personelId)) {
            return $this->response("04", "invalid data send", 'Cant Find personel', 422);
        }

        try {
            $personel = new PersonelRepository();
            $response = $personel->storeCoverageFilter($request->all(), $personelId);
            return $this->response('00', 'store coverage data', $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display store coverage', $th->getMessage());
        }
    }

    public function checkMarketingHasApplicator(Request $request, $id)
    {
        $personalHasApllicator = Personel::with(["personelUnder" => function ($query) {
            return $query->whereHas("position", function ($query) {
                return $query->where("name", "Aplikator");
            });
        }])->findOrFail($id);

        if (count($personalHasApllicator->personelUnder) > 0) {
            return $this->response('00', 'Marketing tersebut memiliki Applikator!', [
                "have_applicator" => true,
                "data" => $personalHasApllicator
            ]);
        }

        return $this->response('00', 'Marketing tersebut tidak memiliki Applikator!', [
            "have_applicator" => false,
            "data" => []
        ]);
    }
}

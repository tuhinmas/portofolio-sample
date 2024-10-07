<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Exports\ThreeFarmerExport;
use App\Models\ExportRequests;
use App\Traits\ChangeMarketingStore;
use App\Traits\GmapsLinkGenerator;
use App\Traits\MarketingArea;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\ExportRequests\Entities\ExportRequest;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreExport;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Http\Requests\StoreRequest;
use Modules\KiosDealer\Notifications\StoreTempNotification;
use Modules\KiosDealer\Repositories\StoreRepository;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\SelfReferenceTrait;

class StoreController extends Controller
{
    use SelfReferenceTrait, ChangeMarketingStore, ChangeMarketingStore, MarketingArea;
    use GmapsLinkGenerator;

    public function __construct(Store $store, StoreTemp $store_temp, Personel $personel)
    {
        $this->store = $store;
        $this->personel = $personel;
        $this->store_temp = $store_temp;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $stores = null;
            $personel_id = $request->personel_id;
            $personel_area = [];

            if ($request->has("personel_id")) {
                $personel_area = $this->districtListMarketing($personel_id);
            }

            if ($request->has("scope_sub_region")) {
                if ($request->has("scope_region")) {
                    unset($request["scope_region"]);
                }
            }

            $MarketingAreaDistrict = MarketingAreaDistrict::when($request->has("applicator_id"), function ($query) use ($request) {
                return $query->where("applicator_id", $request->applicator_id);
            })->get()->map(function ($data) {
                return $data->district_id;
            });

            $stores = $this->store->query()
                ->with('personel', 'agencyLevel', 'core_farmer', "province", "city", "district")
                ->withCount("core_farmer")
                ->when($request->has('non_area_marketing') || $request->non_area_marketing == true, function ($q) {
                    $q->doesntHave('district.marketingAreaDistrict');
                })

            /* filter by status */
                ->when($request->has("status"), function ($q) use ($request) {
                    return $q->whereIn('status', $request->status);
                })

                ->when($request->personel_branch, function ($QQQ) {
                    return $QQQ->PersonelBranch();
                })

            /* filter by personel */
                ->when($request->has("personel_id"), function ($q) use ($personel_id, $personel_area, $request) {
                    if (auth()->user()->hasAnyRole(
                        'administrator',
                        'super-admin',
                        'Marketing Support',
                        'Marketing Manager (MM)',
                        'Operational Manager',
                        'Support Bagian Distributor',
                        'Support Bagian Kegiatan',
                        'Support Distributor',
                        'Support Kegiatan',
                        'Support Supervisor',
                        'Direktur Utama'
                    )) {
                        return $q->where("personel_id", $personel_id);
                    } else {
                        return $q
                            ->where("personel_id", $personel_id);
                        // ->whereIn("district_id", $personel_area);
                    }
                })

                ->when($request->has("telephone"), function ($Q) use ($request) {
                    return $Q->where('telephone', 'like', '%' . $request->telephone . '%');
                })

            /* filter supervisor */
                ->when($request->has("scope_supervisor"), function ($Q) use ($request) {
                    return $Q->supervisor();
                })
            /* filter region */
                ->when($request->has("scope_region"), function ($Q) use ($request) {
                    return $Q->region($request->scope_region);
                })

            /* filter sub region */
                ->when($request->has("scope_sub_region"), function ($Q) use ($request) {
                    return $Q->region($request->scope_sub_region);
                })

            /* filter district */
                ->when($request->has("scope_district"), function ($Q) use ($request) {
                    return $Q->district($request->scope_district);
                })

                ->where("name", "like", "%" . $request->name . "%")

            /* position */
                ->when($request->has("position"), function ($Q) use ($request) {
                    return $Q->whereHas("personel.position", function ($Q) use ($request) {
                        return $Q->where('name', 'like', '%' . $request->position . '%');
                    });
                })

                ->when($request->has("status_personel"), function ($Q) use ($request) {
                    return $Q->whereHas("personel", function ($Q) use ($request) {
                        return $Q->whereIn('status', $request->status_personel);
                    });
                })

            /* sort by name */
                ->when($request->has("sort_by"), function ($QQQ) use ($request) {
                    $sort_type = "asc";
                    if ($request->has("sort_type")) {
                        $sort_type = $request->sort_type;
                    }
                    if ($request->sort_by == 'marketing_name') {
                        return $QQQ->orderBy(Personel::select('name')->whereColumn('personels.id', 'stores.personel_id'), $request->sort_type);
                    } else {
                        return $QQQ->orderBy($request->sort_by, $sort_type);
                    }
                })
                ->when($request->has("applicator_id"), function ($query) use ($request, $MarketingAreaDistrict) {
                    $MarketingApplicator = Personel::findOrFail($request->applicator_id)->supervisor_id;
                    return $query->whereIn("district_id", $MarketingAreaDistrict)
                        ->where("personel_id", $MarketingApplicator);
                })
                ->where('name', 'like', '%' . $request->name . '%')
                ->orderBy('updated_at', 'desc')
                ->paginate($request->limit ? $request->limit : 15)->through(function ($stores) {
                $stores->is_editable = true;
                $stores->is_transferable = $stores->is_transferable;
                return $stores;
            });

            return $this->response('00', 'Store index', $stores);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to display stores index', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('kiosdealer::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(StoreRequest $request)
    {
        try {
            /**
             * pending code
             *
             * $personel_id = $this->updateMarketing($request->kecamatan);
             */
            $personel_id = null;
            if ($request->has("personel_id")) {
                $personel_id = $request->personel_id;
            }
            $store = $this->store->create([
                'telephone' => $request->telephone,
                'second_telephone' => $request->second_telephone,
                'gmaps_link' => $request->gmaps_link,
                'name' => $request->name,
                "owner_name" => $request->owner_name,
                'address' => $request->address,
                'district_id' => $request->kecamatan,
                'city_id' => $request->kabupaten,
                'province_id' => $request->provinsi,
                'personel_id' => $personel_id,
                'status' => $request->status,
                'status_color' => $request->status_color,
                'phone_number_reference' => $request->phone_number_reference,

            ]);

            $export_request_check = DB::table('export_requests')->where("type", "kios")->where("status", "requested")->first();

            if (!$export_request_check) {
                ExportRequests::Create([
                    "type" => "kios",
                    "status" => "requested",
                ]);
            }

            $this->notif($store);

            return $this->response('00', 'store saved', $store);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed to save store', [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
            ]);
        }
    }

    private function notif($storeTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $storeTemp->personel_id)
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $storeTemp->personel?->name ?? null;

        $textNotif = "Pengajuan kios $storeTemp->name telah disetujui oleh support";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Kios",
                "menu" => "Kios",
                "data_id" => $storeTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/kios-detail-data/" . $storeTemp->id,
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
            $member = User::withTrashed()->where("id", $user->id)->first();
            $detail = [
                'notification_marketing_group_id' => 8,
                'personel_id' => $storeTemp->personel_id,
                'notified_feature' => "kios",
                'notification_text' => $textNotif,
                'mobile_link' => $fields["data"]["mobile_link"],
                'desktop_link' => $fields["data"]["desktop_link"],
                'data_id' => $storeTemp->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => "accepted",
                'notifiable_id' => $storeTemp->personel_id,
            ];

            $member->notify(new StoreTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "8";
            $notification->notified_feature = "sub_dealer";
            $notification->notifiable_id = $user->id;
            $notification->personel_id = $storeTemp->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields["data"]["mobile_link"];
            $notification->desktop_link = $fields["data"]["desktop_link"];
            $notification->as_marketing = true;
            $notification->status = $storeTemp->status;
            $notification->data_id = $storeTemp->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $store = $this->store->findOrFail($id);
            $store = $this->store->query()
                ->where('id', $id)
                ->with('personel', 'agencyLevel', 'core_farmer', "province", "city", "district", "telephoneReference")
                ->withCount("core_farmer")
                ->first();

            return $this->response('00', 'store detail', $store);
        } catch (\Throwable $th) {
            return $this->response('01', 'Failed to display store detail', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $store = $this->store->find($id);
        $address_detail = explode(', ', $store->address);
        $array_length = count($address_detail) - 1;
        $jalan = null;
        for ($i = 0; $i <= $array_length - 3; $i++) {
            $jalan .= $address_detail[$i] . ', ';
        }

        return response()->json([
            'response_code' => "00",
            'response_message' => 'address edit',
            'data' => $store,
            'jalan' => $jalan,
            'kecamatan' => $address_detail[$array_length - 2],
            'kabupaten' => $address_detail[$array_length - 1],
            'provinsi' => $address_detail[$array_length],
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        /**
         * pending code
         *
         * $personel_id = $this->updateMarketing($request->kecamatan);
         */

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'telephone' => 'digits_between:6,15',
            'kecamatan' => "required",
            'kabupaten' => "required",
            'provinsi' => "required",
            "latitude" => "max:255",
            "longitude" => "max:255",
            'telephone' => [
                'digits_between:6,15',
                // new UpdateNoTelpKiosRule($id, $request->telephone, $request->personel_id)
            ],
            "status" => [
                "max:255",
                /* pending */
                // new StoreFixInUpdateLatitudeValidationRule($id, $request->latitude, $request->longitude),
            ],

        ]);

        if ($validator->fails()) {
            return $this->response('04', 'invalida data send', $validator->errors(), 422);
        }

        $store = $this->store->findOrFail($id);
        if ($request->dealer_id) {
            Dealer::findOrFail($request->dealer_id);
        }
        $personel_id = $store->personel_id;
        try {
            $request->merge([
                // "gmaps_link" => null
            ]);

            if ($request->has("kecamatan")) {
                $request->merge([
                    "district_id" => $request->kecamatan,
                ]);
            }

            if ($request->has("kabupaten")) {
                $request->merge([
                    "city_id" => $request->kabupaten,
                ]);
            }

            if ($request->has("provinsi")) {
                $request->merge([
                    "province_id" => $request->provinsi,
                ]);
            }

            if ($request->latitude && $request->longitude) {
                $request->merge([
                    "gmaps_link" => $this->generateGmapsLinkFromLatitude($request->latitude, $request->longitude),
                ]);
            }

            $store->fill($request->except([
                "kecamatan",
                "kabupaten",
                "provinsi",
                "personel_id",
            ]));

            $store->save();

            $export_request_check = DB::table('export_requests')->where("type", "kios")->where("status", "requested")->first();

            if (!$export_request_check) {
                ExportRequests::Create([
                    "type" => "kios",
                    "status" => "requested",
                ]);
            }

            return $this->response('00', 'store updated', $store);
        } catch (\Throwable $th) {
            return $this->response('01', 'Failed to update store', $th->getMessage());
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
            $store = $this->store->findOrFail($id);
            $store->delete();
            return $this->response('00', 'store deleted', $store);
        } catch (\Throwable $th) {
            return $this->response('01', 'Failed to delete store', $th->getMessage());
        }
    }

    public function getChildren($personel_id)
    {
        $personels_id = [$personel_id];
        $personel = $this->personel->find($personel_id);

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

    public function checkBawahan($request, $personel_id)
    {
        $stores = null;
        $personel = $this->personel->find($personel_id);
        if ($personel->children == []) {
            $stores = $this->store->query()
                ->with('personel', 'agencyLevel', 'core_farmer', "province", "city", "district")
                ->withCOunt("core_farmer")
                ->where('personel_id', $personel_id)
                ->when($request->has("status"), function ($q) use ($request) {
                    return $q->whereIn('status', $request->status);
                })
                ->where('name', 'like', '%' . $request->name . '%')
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString();
        } else {
            $personels_id = $this->getChildren($personel_id);
            $stores = $this->store->query()
                ->with('personel', 'agencyLevel', 'core_farmer', "province", "city", "district")
                ->withCount("core_farmer")
                ->whereIn('personel_id', $personels_id)
                ->when($request->has("status"), function ($q) use ($request) {
                    return $q->whereIn('status', $request->status);
                })
                ->when($request->has("personel_id"), function ($q) use ($request) {
                    return $q->where("personel_id", $request->personel_id);
                })
                ->where('name', 'like', '%' . $request->name . '%')
                ->orderBy('name')
                ->paginate(30);
        }
        return $stores;
    }

    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }

    public function getAllStores()
    {
        try {
            $personel_id = auth()->user()->personel_id;
            if (auth()->user()->personel_id == null) {
                $stores = $this->store->query()
                    ->where("status", "accepted")
                    ->orderBy('name')
                    ->get();
            } else {
                $personels_id = $this->getChildren($personel_id);
                $stores = $this->store->query()
                    ->whereIn('personel_id', $personels_id)
                    ->where("status", "accepted")
                    ->orderBy('name')
                    ->get();
            }
            return $this->response('00', 'all stores', $stores);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed display all stores', $th);
        }
    }

    /**
     * check status list
     *
     * @param [type] $request
     * @return void
     */
    public function status($request)
    {
        $status = $request->status;
        return $status;
    }

    /**
     * check existing data
     *
     * @param [type] $telephone
     * @return void
     */
    public function checkExistingData(Request $request)
    {
        try {
            $store = $this->store->query()
                ->with('personel', 'agencyLevel', 'core_farmer', "province", "city", "district")
                ->where("telephone", $request->telephone)
                ->first();
            if ($store) {
                return $this->response('02', 'data exist', $store);
            }
            return $this->response('00', 'telephone not exist', "telephone has not ben used");
        } catch (\Throwable $th) {
            return $this->response('01', 'Failed to display store detail', $th->getMessage());
        }
    }

    /**
     * check existing data
     *
     * @param [type] $telephone
     * @return void
     */
    public function checkExistingStoreOnDealer($telephone)
    {
        $store = $this->store->where("telephone", $telephone)->first();
        return $store;
    }

    public function updateDealerId(Request $request, $id)
    {
        try {
            $store = $this->store->findOrFail($id);
            if ($request->has("dealer_id")) {
                $store->dealer_id = $request->dealer_id;
            }
            if ($request->has("sub_dealer_id")) {
                $store->sub_dealer_id = $request->sub_dealer_id;
            }
            if ($request->has("status")) {
                $store->status = $request->status;
                $store->status_color = $request->status_color;
            }
            $store->save();
            return $this->response("00", "store dealer id updated", $store);
        } catch (\Throwable $th) {
            return $this->response("01", "failed to update dealer_id on store", $th->getMessage());
        }
    }

    public function export(Request $request)
    {
        ini_set('max_execution_time', 1500); //3 minutes

        $datenow = Carbon::now()->format('d-m-Y');

        $district_list_on_region = $this->districtListByAreaId($request->region_id);
        // return $QQQ->whereIn("district_id", $district_list_on_region);

        $data = StoreExport::query()->whereNull("deleted_at")
            ->when($request->has("scope_region"), function ($Q) use ($request) {
                return $Q->region($request->scope_region);
            })->get()->map(function ($item, $k) {
            return (object) [
                "sys_id_kios" => $item['id'],
                "kios_name" => $item['name'],
                "kios_owner" => $item['owner_name'],
                "kios_marketing_id" => $item['personel_id'],
                "kios_marketing" => $item['personel_marketing_name'],
                "kios_telp" => $item['telephone'],
                "kios_address" => $item['address'],
                "kios_province_id" => $item['province_id'],
                "kios_city_id" => $item['city_id'],
                "kios_district_id" => $item['district_id'],
                "kios_province" => $item['province_name'],
                "kios_city" => $item['city_name'],
                "kios_district" => $item['district_name'],
                "kios_gmaps" => $item['gmaps_link'],
                // "sys_id_petani" => $item['id'],
                "petani_name" => $item['core_farmers'],
                "petani_address" => $item['id'],
                "petani_telp" => $item['id'],
            ];
        });

        return $this->response("00", "success", $data);
    }

    public function exportThreeFarmer()
    {
        try {
            $datenow = Carbon::now()->format('d-m-Y H:I');
            $document = 'public/export/personel-dan-jumlah-kios' . Str::slug($datenow) . '.xlsx';
            $data = (new ThreeFarmerExport())->store($document, 's3');

            if ($data) {
                $s3 = Storage::disk('s3')->getAdapter()->getClient();
                $cek = $s3->doesObjectExist(env('AWS_BUCKET'), $document);

                if ($s3->doesObjectExist(env('AWS_BUCKET'), $document)) {
                    $url = $s3->getObjectUrl(env('AWS_BUCKET'), $document);

                    $export_personel = new ExportRequest();
                    $export_personel->type = "store_core_farmer";
                    $export_personel->link = $url;
                    $export_personel->status = 'ready';
                    $export_personel->save();
                    // $this->info("Check Command Success " . $url);
                }

                $export_request_check = ExportRequest::where("type", "store_core_farmer")->where("status", "ready")->orderBy('created_at', 'desc')->first();
                if ($export_request_check) {
                    return $this->response("00", "success to get export store_core_farmer", $export_request_check);
                }
            }
        } catch (\Throwable $th) {
            return $this->response('01', 'failed', $th->getMessage());
        }
    }

    public function checkRequestOfChange(Request $request, $storeId)
    {
        try {
            $storeRepository = new StoreRepository();
            $response = $storeRepository->checkRequestOfChange($storeId);
            return $this->response("00", "kios", $response);
        } catch (\Throwable $th) {
            return $this->response('01', 'failed', $th->getMessage());
        }
    }
}

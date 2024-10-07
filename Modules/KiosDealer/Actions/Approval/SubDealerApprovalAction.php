<?php

namespace Modules\KiosDealer\Actions\Approval;

use App\Models\ExportRequests;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealerV2\Events\DeliveryAddressEvent;
use Modules\KiosDealer\Actions\Address\SyncAddressWithAreaAction;
use Modules\KiosDealer\Actions\Address\SyncAddressWithMarketingAction;

class SubDealerApprovalAction
{
    public function __construct(
        protected ExportRequests $export_request,
        protected SubDealer $dealer,
    ) {}

    /**
     * Approval dealer
     * 1. update sub dealer data if from submission of changes
     * 2. address update
     * 3. files update
     * 4. delivery address update
     * 5. data change history update
     * 6. delete submission
     *
     * @param SubDealerTemp $sub_dealer_temp
     * @return void
     */
    public function __invoke(SubDealerTemp $sub_dealer_temp, $user = null)
    {
        $this->export_request->create([
            "type" => "sub_dealer",
            "status" => "requested",
        ]);

        $sub_dealer_fix = null;
        switch (true) {

            /* submission of changes */
            case $sub_dealer_temp->sub_dealer_id:
                $sub_dealer_temp->loadMissing("subDealerFix");
                $sub_dealer_fix = $sub_dealer_temp->subDealerFix;

                /* step 1: make history first */
                self::submissionHistory($sub_dealer_temp);
                self::historyHandler($sub_dealer_temp, $user);

                /* step 2: update sub dealer */
                $sub_dealer_fix->fill(collect($sub_dealer_temp)
                        ->except([
                            "id",
                            "store_id",
                            "dealer_id",
                            "created_at",
                            "updated_at",
                            "grading_id",
                            "change_note",
                            "submited_by",
                            "submited_at",
                            "sub_dealer_id",
                        ])
                        ->only(column_lists($sub_dealer_fix))
                        ->toArray());
                $sub_dealer_fix->status = "accepted";
                $sub_dealer_fix->status_color = "000000";
                $sub_dealer_fix->save();

                /* step 3: handle address */
                self::addressHandler($sub_dealer_temp, $sub_dealer_fix);

                /* step 4: handle files */
                self::filesHandler($sub_dealer_temp, $sub_dealer_fix);
                break;

            /* new sub dealer */
            default:

                /* submission hidtory */
                self::submissionHistory($sub_dealer_temp);

                /* create dealer */
                $sub_dealer_fix = self::createNewDealer($sub_dealer_temp);

                /* handle address */
                self::addressHandler($sub_dealer_temp, $sub_dealer_fix);

                /* handle files */
                self::filesHandler($sub_dealer_temp, $sub_dealer_fix);

                switch (true) {

                    /* sub dealer transfer from store */
                    case $sub_dealer_temp->store_id:
                        $sub_dealer_temp->loadMissing("storeFix");
                        $sub_dealer_temp->storeFix->sub_dealer_id = $sub_dealer_fix->id;
                        $sub_dealer_temp->storeFix->status = "transfered";
                        $sub_dealer_temp->storeFix->save();
                        break;

                    /* original dealer */
                    default:
                        break;

                }
                break;
        }

        /* delete dealer submission */
        $sub_dealer_temp->delete();
        return $sub_dealer_fix;
    }

    public static function createNewDealer($sub_dealer_temp): SubDealer
    {
        /* default agnecy level */
        $agency_level_id = DB::table('agency_levels')->where('name', 'R3')->first();

        /* default grading (putih)*/
        $grading_id = DB::table('gradings')->where("default", true)->first();

        /* default status fee */
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();
        
        $data = collect($sub_dealer_temp)
            ->except([
                "id",
                "created_at",
                "updated_at",
                "change_note",
                "store_id",
                "submited_by",
                "submited_at",
                "handover_status"
            ])
            ->only(column_lists($sub_dealer_temp))
            ->toArray();
        $data["status"] = "accepted";
        $data["status_color"] = "000000";
        $data["agency_level_id"] = $agency_level_id->id;
        $data["grading_id"] = $grading_id->id;
        $data["status_fee"] = $status_fee->id;
        $data["sub_dealer_id"] = self::dealerIdGeneartor();

        return SubDealer::create($data);
    }

    public static function submissionHistory($sub_dealer_temp, $user = null)
    {
        $sub_dealer_temp->loadMissing("subDealerChangeHistory");
        $sub_dealer_fix = $sub_dealer_temp->subDealerFix;

        /* change history first, before all action*/
        if ($sub_dealer_temp->subDealerChangeHistory) {
            $sub_dealer_temp->subDealerChangeHistory->approved_at = now();
            $sub_dealer_temp->subDealerChangeHistory->approved_by = auth()->check() ? auth()->user()->personel_id : $user?->personel_id;
            $sub_dealer_temp->subDealerChangeHistory->save();
        }
    }

    public static function historyHandler($sub_dealer_temp, $user = null)
    {
        $sub_dealer_temp->loadMissing("subDealerChangeHistory");
        $sub_dealer_fix = $sub_dealer_temp->subDealerFix;
        $relation = $sub_dealer_temp->subDealerChangeHistory->subDealerDataHistory()->getRelated();

        /* change history first, before all action*/
        if ($sub_dealer_temp->subDealerChangeHistory) {

            /* dealer data history */
            $sub_dealer_history_data = collect($sub_dealer_temp->subDealerFix)
                ->only(column_lists($relation))
                ->except([
                    "id",
                    "created_at",
                    "updated_at",
                    "deleted_at",
                ])
                ->map(function ($value, $attribute) use ($sub_dealer_fix) {
                    if ($attribute == "sub_dealer_id") {
                        $value = $sub_dealer_fix->id;
                    }

                    if ($attribute == "personel_id") {
                        $value = auth()->check() ? auth()->user()->personel_id : $user?->personel_id;
                    }

                    return $value;
                })
                ->toArray();

            $sub_dealer_history_data["sub_dealer_change_history_id"] = $sub_dealer_temp->subDealerChangeHistory->id;
            $data_history = $sub_dealer_temp->subDealerChangeHistory->subDealerDataHistory()->create($sub_dealer_history_data);

            $sub_dealer_fix->loadMissing(["addressDetail", "subDealerFile"]);
            $addresses = $sub_dealer_fix
                ->addressDetail
                ->map(function ($address) use ($sub_dealer_fix, $data_history) {
                    $address->parent_id = $sub_dealer_fix->id;
                    $address->sub_dealer_data_history_id = $data_history->id;
                    return collect($address)->except([
                        "id",
                        "city",
                        "area_id",
                        "province",
                        "district",
                        "region_id",
                        "created_at",
                        "updated_at",
                        "sub_region_id",
                    ]);
                })
                ->toArray();

            $files = $sub_dealer_fix
                ->subDealerFile
                ->map(function ($address) use ($sub_dealer_fix, $data_history) {
                    $address->sub_dealer_id = $sub_dealer_fix->id;
                    $address->sub_dealer_data_history_id = $data_history->id;
                    return collect($address)->except(["id", "created_at", "updated_at", "file_url", "dealer_id"]);
                })
                ->toArray();
                
            $sub_dealer_temp->subDealerChangeHistory->subDealerDataHistory->subDealerAddress()->createMany($addresses);
            $sub_dealer_temp->subDealerChangeHistory->subDealerDataHistory->subDealerFileHistory()->createMany($files);
        }
    }

    public static function addressHandler($sub_dealer_temp, $sub_dealer_fix)
    {
        $sub_dealer_temp->loadMissing("addressDetail");
        $addresses = $sub_dealer_temp
            ->addressDetail
            ->transform(function ($address) use ($sub_dealer_fix) {
                $address->parent_id = $sub_dealer_fix->id;
                return collect($address)->except(["id", "created_at", "updated_at"]);
            })
            ->toArray();
        
        $sub_dealer_fix->addressDetail->each(fn($address) => $address->delete());
        $sub_dealer_fix->addressDetail()->createMany($addresses);
        $sub_dealer_fix->refresh();

        $sub_dealer_fix->load("addressDetail");
        $sub_dealer_fix
            ->addressDetail
            ->each(fn($address) => (new SyncAddressWithAreaAction)($address))
            ->each(fn($address) => $address->type == "sub_dealer" ? (new SyncAddressWithMarketingAction)($sub_dealer_fix, $address->district_id) : null);
    }

    public static function filesHandler($sub_dealer_temp, $sub_dealer_fix)
    {
        $sub_dealer_temp->loadMissing("subDealerFile");
        $files = $sub_dealer_temp
            ->subDealerFile
            ->transform(function ($address) use ($sub_dealer_fix) {
                $address->dealer_id = $sub_dealer_fix->id;
                return collect($address)->except(["id", "created_at", "updated_at", "file_url"]);
            })
            ->toArray();

        $sub_dealer_fix->subDealerFile->each(fn($file) => $file->delete());
        $sub_dealer_fix->subDealerFile()->createMany($files);
    }

    public static function dealerIdGeneartor()
    {
        $dealer = DB::table('sub_dealers')
            ->whereNull("deleted_at")
            ->orderBy('sub_dealer_id', 'desc')
            ->first();

        return (int) ($dealer?->sub_dealer_id + 1);
    }
}

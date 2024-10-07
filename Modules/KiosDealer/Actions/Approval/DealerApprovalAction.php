<?php

namespace Modules\KiosDealer\Actions\Approval;

use Carbon\Carbon;
use App\Models\ExportRequests;
use Ladumor\OneSignal\OneSignal;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\DealerFile;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\DealerGrading;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Modules\KiosDealerV2\Events\DeliveryAddressEvent;
use Modules\KiosDealer\Notifications\DealerTempNotification;
use Modules\KiosDealer\Actions\Address\SyncAddressWithAreaAction;
use Modules\KiosDealer\Actions\Address\SyncAddressWithMarketingAction;

class DealerApprovalAction
{
    public function __construct(
        protected ExportRequests $export_request,
        protected Dealer $dealer,
    ) {}

    /**
     * Approval dealer
     * 1. update dealer data if from submission of changes
     * 2. address update
     * 3. files update
     * 4. delivery address update
     * 5. data change history update
     * 6. delete submission
     *
     * @param DealerTemp $dealer_temp
     * @return void
     */
    public function __invoke(DealerTemp $dealer_temp, $user = null)
    {
        $this->export_request->create([
            "type" => "dealer",
            "status" => "requested",
        ]);

        $dealer_fix = null;
        switch (true) {

            /* submission of changes */
            case $dealer_temp->dealer_id:
                $dealer_fix = $dealer_temp->dealerFix;

                /* step 1: make history first */
                self::submissionHistory($dealer_temp);
                self::historyHandler($dealer_temp, $user);

                /* step 2: update dealer */
                $dealer_fix->fill(collect($dealer_temp)
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
                        ->only(column_lists($dealer_fix))
                        ->toArray());
                $dealer_fix->status = "accepted";
                $dealer_fix->status_color = "000000";
                $dealer_fix->save();

                /* step 3: handle address */
                self::addressHandler($dealer_temp, $dealer_fix);

                /* step 4: handle files */
                self::filesHandler($dealer_temp, $dealer_fix);

                /* step 5: delivery address update */
                DeliveryAddressEvent::dispatch($dealer_fix);
                break;

            /* new dealer */
            default:

                /* submission hidtory */
                self::submissionHistory($dealer_temp);

                /* create dealer */
                $dealer_fix = self::createNewDealer($dealer_temp);

                /* handle address */
                self::addressHandler($dealer_temp, $dealer_fix);

                /* handle files */
                self::filesHandler($dealer_temp, $dealer_fix);

                /* delivery address update */
                DeliveryAddressEvent::dispatch($dealer_fix);

                switch (true) {

                    /* dealer transfer from sub dealer */
                    case $dealer_temp->sub_dealer_id:
                        $dealer_temp->loadMissing("subDealerFix");
                        $dealer_temp->subDealerFix->dealer_id = $dealer_fix->id;
                        $dealer_temp->subDealerFix->status = "transfered";
                        $dealer_temp->subDealerFix->save();
                        $dealer_temp->subDealerFix->delete();
                        break;

                    /* dealer transfer from store */
                    case $dealer_temp->store_id:
                        $dealer_temp->loadMissing("storeFix");
                        $dealer_temp->storeFix->dealer_id = $dealer_fix->id;
                        $dealer_temp->storeFix->status = "transfered";
                        $dealer_temp->storeFix->save();
                        break;

                    /* original dealer */
                    default:
                        break;

                }
                break;
        }

        /* delete dealer submission */
        $dealer_temp->delete();
        $this->pushNotif($dealer_fix);
        return $dealer_fix;
    }

    public static function createNewDealer($dealer_temp): Dealer
    {
        /* default agnecy level */
        $agency_level_id = DB::table('agency_levels')->where('name', 'R3')->first();

        /* default grading (putih)*/
        $grading_id = DB::table('gradings')->where("default", true)->first();

        /* default status fee */
        $status_fee = DB::table('status_fee')->whereNull("deleted_at")->where("name", "R")->first();

        $data = collect($dealer_temp)
            ->except([
                "id",
                "created_at",
                "updated_at",
                "change_note",
                "store_id",
                "sub_dealer_id",
                "submited_by",
                "submited_at",
            ])
            ->only(column_lists($dealer_temp))
            ->toArray();
        $data["status"] = "accepted";
        $data["status_color"] = "000000";
        $data["agency_level_id"] = $agency_level_id->id;
        $data["grading_id"] = $grading_id->id;
        $data["status_fee"] = $status_fee->id;
        $data["dealer_id"] = self::dealerIdGeneartor();

        $dealer = Dealer::create($data);
        DealerGrading::create([
            "dealer_id" => $dealer->id,
            "grading_id" => $grading_id->id,
            "user_id" => auth()->check() ? auth()->id() : null,
            "custom_credit_limit" => 0
        ]);
        return $dealer;
    }

    public static function submissionHistory($dealer_temp, $user = null)
    {
        $dealer_temp->loadMissing("dealerChangeHistory");
        $dealer_fix = $dealer_temp->dealerFix;

        /* change history first, before all action*/
        if ($dealer_temp->dealerChangeHistory) {
            $dealer_temp->dealerChangeHistory->approved_at = now();
            $dealer_temp->dealerChangeHistory->approved_by = auth()->check() ? auth()->user()->personel_id : $user?->personel_id;
            $dealer_temp->dealerChangeHistory->save();
        }
    }

    public static function historyHandler($dealer_temp, $user = null)
    {
        $dealer_temp->loadMissing("dealerChangeHistory");
        $dealer_fix = $dealer_temp->dealerFix;

        /* change history first, before all action*/
        if ($dealer_temp->dealerChangeHistory) {

            /* dealer data history */
            $dealer_history_data = collect($dealer_temp->dealerFix)
                ->except([
                    "id",
                    "status",
                    "prefix_id",
                    "created_at",
                    "updated_at",
                    "grading_id",
                    "status_fee",
                    "status_color",
                    "last_grading",
                    "sales_counter",
                    "agency_level_id",
                    "request_grading",
                    "grading_block_id",
                    "is_block_grading",
                    "custom_credit_limit",
                    "suggested_grading_id",
                ])
                ->map(function ($value, $attribute) use ($dealer_fix) {
                    if ($attribute == "dealer_id") {
                        $value = $dealer_fix->id;
                    }

                    if ($attribute == "personel_id") {
                        $value = auth()->check() ? auth()->user()->personel_id : $user?->personel_id;
                    }

                    return $value;
                })
                ->toArray();

            $dealer_history_data["dealer_change_history_id"] = $dealer_temp->dealerChangeHistory->id;
            $data_history = $dealer_temp->dealerChangeHistory->dealerDataHistory()->create($dealer_history_data);

            $dealer_fix->loadMissing(["addressDetail", "dealerFile"]);
            $addresses = $dealer_fix
                ->addressDetail
                ->map(function ($address) use ($dealer_fix, $data_history) {
                    $address->parent_id = $dealer_fix->id;
                    $address->dealer_data_history_id = $data_history->id;
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

            $files = $dealer_fix
                ->dealerFile
                ->map(function ($address) use ($dealer_fix, $data_history) {
                    $address->dealer_id = $dealer_fix->id;
                    $address->dealer_data_history_id = $data_history->id;
                    return collect($address)->except(["id", "created_at", "updated_at", "file_url"]);
                })
                ->toArray();

            $dealer_temp->dealerChangeHistory->dealerDataHistory->dealerAddresses()->createMany($addresses);
            $dealer_temp->dealerChangeHistory->dealerDataHistory->dealerFileHistories()->createMany($files);
        }
    }

    public static function addressHandler($dealer_temp, $dealer_fix)
    {
        $dealer_temp->loadMissing("addressDetail");
        $addresses = $dealer_temp
            ->addressDetail
            ->transform(function ($address) use ($dealer_fix) {
                $address->parent_id = $dealer_fix->id;
                return collect($address)->except(["id", "created_at", "updated_at"]);
            })
            ->toArray();

        $dealer_fix->addressDetail->each(fn($address) => $address->delete());
        $dealer_fix->addressDetail()->createMany($addresses);
        $dealer_fix->refresh();
        $dealer_fix
            ->addressDetail
            ->each(fn($address) => (new SyncAddressWithAreaAction)($address))
            ->each(fn($address) => $address->type == "dealer" ? (new SyncAddressWithMarketingAction)($dealer_fix, $address->district_id) : null);
    }

    public static function filesHandler($dealer_temp, $dealer_fix)
    {
        $dealer_temp->loadMissing("dealerFile");
        $files = $dealer_temp
            ->dealerFile
            ->transform(function ($address) use ($dealer_fix) {
                $address->dealer_id = $dealer_fix->id;
                return collect($address)->except(["id", "created_at", "updated_at", "file_url"]);
            })
            ->toArray();

        $dealer_fix->dealerFile->each(fn($file) => $file->delete());
        $dealer_fix->dealerFile()->createMany($files);
    }

    public static function dealerIdGeneartor()
    {
        $dealer = DB::table('dealers')
            ->whereNull("deleted_at")
            ->orderBy('dealer_id', 'desc')
            ->first();

        return (int) ($dealer?->dealer_id + 1);
    }

    private function pushNotif($dealer)
    {
        $data = $dealer;
        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->submited_by)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "pengajuan data ".$data->name." disetujui";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DealerInfo",
                "desktop_link" => "/marketing-staff/detail-dealer/",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $data, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function notif($users, $data, $notificationText, $mobileLink, $desktopLink)
    {
        foreach (($users ?? []) as $user) {
            $member =  User::withTrashed()->where("id", $user->id)->first();
            $detail = [
                'notification_marketing_group_id' => 5,
                'personel_id' => $data->personel_id,
                'notified_feature' => "dealer",
                'notification_text' => $notificationText,
                'mobile_link' => $mobileLink,
                'desktop_link' => $desktopLink,
                'data_id' => $data->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => $data->status,
                'notifiable_id' => $data->personel_id,
            ];

            $member->notify(new DealerTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = 5;
            $notification->notified_feature = "dealer";
            $notification->notifiable_id = $data->id;
            $notification->personel_id = $data->personel_id;
            $notification->notification_text = $notificationText;
            $notification->mobile_link = $mobileLink;
            $notification->desktop_link = $desktopLink;
            $notification->as_marketing = true;
            $notification->status = $data->status;
            $notification->data_id = $data->id;
            $notification->save();
        }
    }


}

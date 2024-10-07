<?php

namespace Modules\KiosDealer\Listeners;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\DealerAddressHistory;
use Modules\KiosDealer\Entities\DealerChangeHistory;
use Modules\KiosDealer\Entities\DealerDataHistory;
use Modules\KiosDealer\Entities\DealerFileHistory;
use Modules\KiosDealer\Events\DealerChangeHistoryEvent;
use Modules\KiosDealer\Events\DealerFilledRejectedEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;

class DealerChangeHistoryListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */

    use ChildrenList;

    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(DealerChangeHistoryEvent $event)
    {
        /**
         * 
         * 
         */

        $dealer_change_history = DealerChangeHistory::where("dealer_temp_id", $event->dealer->dealerTemp->id)->first();

        if ($dealer_change_history) {
            $dealer_change_history->approved_at = Carbon::now();
            $dealer_change_history->approved_by = auth()->user()->personel_id;


            if ($dealer_change_history->save()) {
                $dealer_data_history = new DealerDataHistory();
                $dealer_data_history->personel_id = $event->dealer->personel_id;
                $dealer_data_history->dealer_change_history_id = $dealer_change_history->id;
                $dealer_data_history->dealer_id = $event->dealer->id;
                $dealer_data_history->name = $event->dealer->name;
                $dealer_data_history->entity_id = $event->dealer->entity_id;
                $dealer_data_history->prefix = $event->dealer->prefix;
                $dealer_data_history->sufix = $event->dealer->sufix;
                $dealer_data_history->address = $event->dealer->address;
                $dealer_data_history->email = $event->dealer->email;
                $dealer_data_history->telephone = $event->dealer->telephone;
                $dealer_data_history->gmaps_link = $event->dealer->gmaps_link;
                $dealer_data_history->owner = $event->dealer->owner;
                $dealer_data_history->owner_address = $event->dealer->owner_address;
                $dealer_data_history->owner_ktp = $event->dealer->owner_ktp;
                $dealer_data_history->owner_npwp = $event->dealer->owner_npwp;
                $dealer_data_history->owner_telephone = $event->dealer->owner_telephone;
                $dealer_data_history->bank_account_number = $event->dealer->bank_account_number;
                $dealer_data_history->bank_name = $event->dealer->bank_name;
                $dealer_data_history->owner_bank_account_number = $event->dealer->owner_bank_account_number;
                $dealer_data_history->owner_bank_account_name = $event->dealer->owner_bank_account_name;
                $dealer_data_history->owner_bank_name = $event->dealer->owner_bank_name;
                $dealer_data_history->owner_bank_id = $event->dealer->owner_bank_id;
                $dealer_data_history->save();

                // dd(collect($dealer->dealer_file)->count());
                if (collect($event->dealer->dealer_file)->count() > 0) {
                    foreach ($event->dealer->dealer_file as $data) {
                        $data_file_history = new DealerFileHistory();
                        $data_file_history->dealer_data_history_id = $dealer_data_history->id;
                        $data_file_history->dealer_id = $data->dealer_id;
                        $data_file_history->file_type = $data->file_type;
                        $data_file_history->data = $data->data;
                        $data_file_history->save();
                    }
                }

                if (collect($event->dealer->adressDetail)->count() > 0) {
                    foreach ($event->dealer->adressDetail as $data) {
                        $data_file_history = new DealerAddressHistory();
                        $data_file_history->type = $data->type;
                        $data_file_history->dealer_data_history_id = $dealer_data_history->id;
                        $data_file_history->parent_id = $data->parent_id;
                        $data_file_history->province_id = $data->province_id;
                        $data_file_history->city_id = $data->city_id;
                        $data_file_history->district_id = $data->district_id;
                        $data_file_history->save();
                    }
                }
            }
        }
    }
}

<?php

namespace Modules\DataAcuan\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\DataAcuan\Events\MarketingAreaOnChangeEvent;
use Modules\DataAcuan\Events\SalesOrderChangeMarketingEvent;

class SalesOrderTakeOverByNewMarketingListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MarketingAreaOnChangeEvent $event)
    {
        $list_agency_level = DB::table('agency_levels')
            ->whereIn('name', ['D1', 'D2'])
            ->pluck('id');

        $status_fee_L1 = DB::table('status_fee')
            ->whereNull("deleted_at")
            ->where("name", "L1")
            ->first();

        /**
         * handover all dealer on this district to new marketing
         */
        $dealers = DealerV2::query()
            ->whereHas("addressDetail", function ($QQQ) use ($event) {
                return $QQQ
                    ->where("type", "dealer")
                    ->where("district_id", $event->marketing_area_district->district_id);
            })
            ->whereNotIn('agency_level_id', $list_agency_level)
            ->get()
            ->each(function ($dealer) use ($event) {
                $dealer->personel_id = $event->marketing_area_district->personel_id;
                $dealer->save();
            });

        /**
         * handover all sub dealer on this district to new marketing
         */
        $sub_dealers = SubDealer::query()
            ->whereHas("addressDetail", function ($QQQ) use ($event) {
                return $QQQ
                    ->where("type", "sub_dealer")
                    ->where("district_id", $event->marketing_area_district->district_id);
            })
            ->get()
            ->each(function ($sub_dealer) use ($event) {
                $sub_dealer->personel_id = $event->marketing_area_district->personel_id;
                $sub_dealer->save();
            });

        return $dealers->pluck("id")->concat($sub_dealers->pluck("id"));
    }
}

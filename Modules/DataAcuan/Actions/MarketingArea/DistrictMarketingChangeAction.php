<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\User;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\ForeCast\Entities\ForeCast;
use Modules\ForeCast\Entities\ForecastHistory;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\LogFreeze;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;

class DistrictMarketingChangeAction
{

    public function __invoke(MarketingAreaDistrict $district, string $old_marketing_id, User $user)
    {
        /**
         * 1. district handled by old_marketing will tak over
         * 2. retailer handled by old marketing will take over
         *
         */
        if ($district->personel_id == $old_marketing_id) {
            return 0;
        }

        $dealer_sub_dealer = self::retailerTakeOver($district, $old_marketing_id);
        self::applicatorAreaRule($district);
        self::personelUpdateSupervisor($district);
        self::logFreezeCheck($district, $old_marketing_id);
        self::forecastTakeOver($dealer_sub_dealer, $district, $user);
        self::orderTakeOver($dealer_sub_dealer, $district, $user);
    }

    /**
     * retilaer in area tak eover by new marketing
     *
     * @param MarketingAreaDistrict $district
     * @param string $old_marketing_id
     * @return void
     */
    public static function retailerTakeOver(MarketingAreaDistrict $district, string $old_marketing_id)
    {
        $list_agency_level = DB::table('agency_levels')
            ->whereIn('name', ['D1', 'D2'])
            ->pluck('id')
            ->toArray();

        $status_fee_L1 = DB::table('status_fee')
            ->whereNull("deleted_at")
            ->where("name", "L1")
            ->first();

        /**
         * handover all dealer on this district to new marketing
         */
        $dealer = DealerV2::query()
            ->whereHas("addressDetail", function ($QQQ) use ($district) {
                return $QQQ
                    ->where("type", "dealer")
                    ->where("district_id", $district->district_id);
            })
            ->whereNotIn('agency_level_id', $list_agency_level)
            ->get()
            ->each(function ($dealer) use ($district, $status_fee_L1) {
                $dealer->personel_id = $district->personel_id;
                $dealer->status_fee = $status_fee_L1->id;
                $dealer->save();
            });

        /**
         * handover all sub dealer on this district to new marketing
         */
        $sub_dealer = SubDealer::query()
            ->whereHas("addressDetail", function ($QQQ) use ($district) {
                return $QQQ
                    ->where("type", "sub_dealer")
                    ->where("district_id", $district->district_id);
            })
            ->get()
            ->each(function ($sub_dealer) use ($district, $status_fee_L1) {
                $sub_dealer->personel_id = $district->personel_id;
                $sub_dealer->status_fee = $status_fee_L1->id;
                $sub_dealer->save();
            });

        return [
            "dealer" => $dealer->pluck("id")->toArray(),
            "sub_dealer" => $sub_dealer->pluck("id")->toArray(),
        ];
    }

    /**
     * cek log freeze of marketing,
     * if there exist update it
     */
    public static function logFreezeCheck(MarketingAreaDistrict $district, string $old_marketing_id)
    {
        $log_freeze = LogFreeze::query()
            ->where("personel_id", $district->personel_id)
            ->whereNull("id_subtitute_personel")
            ->whereNull("freeze_end")
            ->orderBy("created_at")
            ->first();

        if ($log_freeze) {
            $log_freeze->id_subtitute_personel = $old_marketing_id;
            $log_freeze->save();
        }
    }

    /**
     * applicator supervisor is according marketing
     * on this area, if marketing area change,
     * and not applicator supervisor, then
     * applicator will revoked from this
     * area
     */
    public static function applicatorAreaRule(MarketingAreaDistrict $district)
    {
        if (!$district->personel_id) {
            $district->applicator_id = null;
            $district->save();
        }

        if ($district->applicator_id) {
            $applicator = DB::table('personels')
                ->whereNull("deleted_at")
                ->where("id", $district->applicator_id)
                ->where("supervisor_id", $district->personel_id)
                ->first();

            /**
             * applicator supervisor is according marketing
             * on this area, if marketing area change,
             * and not applicator supervisor, then
             * applicator will revoked from this
             * area
             */
            if (!$applicator) {
                $district->applicator_id = null;
            }
        }
    }

    /**
     * supervisor update
     *
     * @param MarketingAreaDistrict $district
     * @param string $old_marketing_id
     * @return void
     */
    public function personelUpdateSupervisor(MarketingAreaDistrict $district)
    {
        $sub_region_marketing = DB::table('marketing_area_sub_regions')
            ->whereNull("deleted_at")
            ->where("id", $district->sub_region_id)
            ->first();

        $new_marketing = Personel::findOrFail($district->personel_id);

        /* update supervisor if personel on sub region is not same with personel on district */
        if ($new_marketing->id !== $sub_region_marketing->personel_id) {
            $new_marketing->supervisor_id = $sub_region_marketing->personel_id;
            $new_marketing->save();
        }
    }

    /**
     * forecast take over
     *
     * @param MarketingAreaDistrict $district
     * @param string $old_marketing_id
     * @return void
     */
    public static function forecastTakeOver(array $dealer_sub_dealer, MarketingAreaDistrict $district, User $user)
    {
        $findAllForecast = ForeCast::query()
            ->where(function ($QQQ) use ($dealer_sub_dealer) {
                return $QQQ
                    ->where(function ($QQQ) use ($dealer_sub_dealer) {
                        return $QQQ
                            ->where("dealer_category", "dealers")
                            ->whereIn("dealer_id", $dealer_sub_dealer["dealer"]);
                    })
                    ->orWhere(function ($QQQ) use ($dealer_sub_dealer) {
                        return $QQQ
                            ->where("dealer_category", "sub_dealers")
                            ->whereIn("dealer_id", $dealer_sub_dealer["sub_dealer"]);
                    });
            })
            ->whereNotNull('dealer_category')
            ->where('date', '>=', date('Y-m'))
            ->get()
            ->each(function ($forecast) use ($district, $user) {
                $forecast->personel_id = $district->personel_id;
                $forecast->save();

                ForecastHistory::create([
                    "dealer_category" => $forecast->dealer_category,
                    "dealer_id" => $forecast->dealer_id,
                    "change_by" => $user->personel_id,
                    "personel_id" => $district->personel_id,
                    "product_id" => $forecast->product_id,
                    "forecast_id" => $forecast->id,
                    "unit" => $forecast->unit,
                    "status" => $forecast->status,
                    "price" => $forecast->price,
                    "quantity" => $forecast->quantity,
                    "nominal" => $forecast->nominal,
                ]);

            });
    }

    /**
     * sales order unconfirm take over by new marketing
     *
     * @param array $dealer_sub_dealer
     * @param MarketingAreaDistrict $district
     * @return void
     */
    public static function orderTakeOver(array $dealer_sub_dealer, MarketingAreaDistrict $district)
    {
        SalesOrder::query()
            ->where(function ($QQQ) use ($dealer_sub_dealer) {
                return $QQQ
                    ->where(function ($QQQ) use ($dealer_sub_dealer) {
                        return $QQQ
                            ->where("model", "1")
                            ->whereIn("store_id", $dealer_sub_dealer["dealer"]);
                    })
                    ->orWhere(function ($QQQ) use ($dealer_sub_dealer) {
                        return $QQQ
                            ->where("model", "1")
                            ->whereIn("store_id", $dealer_sub_dealer["sub_dealer"]);
                    });
            })
            ->whereNotIn("status", ["confirmed", "returned", "pending"])
            ->get()
            ->each(function($order)use($district){
                $order->personel_id = $district->personel_id;
                $order->save();
            });
    }
}

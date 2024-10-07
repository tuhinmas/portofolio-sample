<?php

namespace Modules\Personel\Transformers;

use Carbon\Carbon;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Orion\Http\Resources\CollectionResource;

class FeeSharingUpdateMarketingResource extends CollectionResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $marketing_buyer_join_date_days = 0;

        return $this->collection
            ->map(function ($origin) use (&$marketing_buyer_join_date_days) {

                /**
             * if marketing as purchaser
             * exist
             */
                $personel_on_purchase = $this->collection->first()->personel_updated;
                if (!empty($personel_on_purchase)) {
                    $marketing_buyer_join_date_days = $personel_on_purchase->join_date ? Carbon::parse($personel_on_purchase->join_date)->diffInDays(Carbon::parse($origin->confirmed_at), false) : 0;
                }

                $spv_marketing_purchaser_in_order = null;
                if ($origin->salesOrder->personel_id == $origin->personel_updated->id) {
                    $spv_marketing_purchaser_in_order = $origin->personel_updated->id;
                } else {
                    $spv_marketing_purchaser_in_order = $origin->salesOrder->personel->supervisorInConfirmedOrder ? json_decode($origin->salesOrder->personel->supervisorInConfirmedOrder->properties)->attributes->supervisor_id : null;
                }

                $origin->spv_marketing_purchaser_in_order = $spv_marketing_purchaser_in_order;
                $origin->marketing_purchaser = $origin->salesOrder->personel_id;
                $origin->status_fee_name = $origin->statusFee->name;
                $origin->marketing_buyer_join_date_days = $marketing_buyer_join_date_days;

                $as_marketing = $origin->salesOrder->personel_id == $origin->personel_updated->id ? true : false;
                $origin->as_marketing = $as_marketing;

            /**
             * as marketing if join days less than 90 and status fee name is not R then this marketing
             * will not get fee for this origin, as spv if join days less then 90 no matter
             * what it's origin status fee name, then this marketing updated will not
             * get fee, important resign date must more then origin confirmed_at
             */
                $set_to_null = false;
                if (($marketing_buyer_join_date_days < 90 && $origin->status_fee_name != "R" && $as_marketing)
                    || (!empty($origin->personel_updated->resign_date) && $origin->personel_updated->resign_date <= Carbon::parse($origin->confirmed_at)->format("Y-m-d"))
                    || ($marketing_buyer_join_date_days < 90 && !$as_marketing)
                ) {
                    $set_to_null = true;
                }

            /**
             * if join days more than 90 or status fee name is R and act as marketing then this marketing
             * will get fee for this origin, important resign date must more then origin confirmed_at
             */
                $set_to_get_fee = false;
                if (($marketing_buyer_join_date_days >= 90 || ($origin->status_fee_name == "R" && $as_marketing))
                    && ($origin->personel_updated->resign_date > Carbon::parse($origin->confirmed_at)->format("Y-m-d") || empty($origin->personel_updated->resign_date))
                ) {
                    $set_to_get_fee = true;
                }

                $origin->set_to_null = $set_to_null;
                $origin->set_to_get_fee = $set_to_get_fee;

                $origin->unsetRelation("salesOrder");
                $origin->unsetRelation("statusFee");
                return $origin;
            })
            ->reject(function ($origin) use ($marketing_buyer_join_date_days) {
                return $origin->personel_id != $origin->spv_marketing_purchaser_in_order && $origin->personel_id != null;
            })
            ->map(function ($origin) {
                $personel_id = null;
                if ($origin->set_to_null) {
                    $origin->personel_id = null;
                } else {
                    $personel_id = $origin->personel_id;
                }

                if ($origin->set_to_get_fee) {
                    $origin->personel_id = $origin->personel_updated->id;
                    $personel_id = $origin->personel_updated->id;;
                }

                $update = FeeSharingSoOrigin::query()
                    ->where("id", $origin->id)
                    ->update([
                        "personel_id" => $personel_id,
                    ]);

                return collect($origin)->forget("personel_updated");
            });
    }
}

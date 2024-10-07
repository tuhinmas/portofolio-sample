<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;

class DealerTempUpdateAcceptedRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($telephone, $status)
    {
        $this->telephone = $telephone;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $dealers = Dealer::query()
            ->select(
                "id",
                "telephone",
                "dealer_id as store_id",
                "dealer_id as dealer_id",
                DB::raw("if(dealer_id, 'dealer', 'dealer') as store_type"),
                DB::raw("if(dealer_id, 'CUST-', 'CUST-SUB-') as prefix_store_id")
            )
            ->where("telephone", $this->telephone)
            ->whereIn("status", ['accepted'])->first();

        $sub_dealers = SubDealer::query()
            ->select(
                "id",
                "telephone",
                "sub_dealer_id as store_id",
                "sub_dealer_id as dealer_id",
                DB::raw("if(sub_dealer_id, 'sub_dealer', 'sub_dealer') as store_type"),
                DB::raw("if(sub_dealer_id, 'CUST-SUB-', 'CUST-') as prefix_store_id")
            )
            ->where("telephone", $this->telephone)
            ->whereIn("status", ['accepted'])->first();

        if ($this->status == "accepted") {
            if ($dealers) {
                return false;
            }
            if ($sub_dealers) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'No Telp must unique if dealer/subdealer will be confirmed';
    }
}

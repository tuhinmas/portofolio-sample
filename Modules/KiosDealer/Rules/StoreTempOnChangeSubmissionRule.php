<?php

namespace Modules\KiosDealer\Rules;

use Modules\KiosDealer\Entities\Store;
use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\SubDealerTemp;

class StoreTempOnChangeSubmissionRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        if ($value == null) {
            return true;
        }
        $store = Store::findOrFail($value);
        $store_temp = StoreTemp::where('store_id', $value)->whereNotIn('status', ['filed rejected','change rejected', 'draft'])->first();
        $dealer = DealerTemp::where('store_id', $value)->whereNotIn('status', ['filed rejected', 'change rejected'])->first();
        $subDealer = SubDealerTemp::where('store_id', $value)->whereNotIn('status', ['filed rejected', 'change rejected'])->first();

        if ($store->dealer_id != null || $store->sub_dealer_id != null || $dealer || $subDealer || $store_temp) {
            return false;
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
        return 'can not store, kios is on transfer submission';
    }
}

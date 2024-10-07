<?php

namespace Modules\KiosDealer\Rule;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;

class KiosToDealerSubDealerAcceptedRule implements Rule
{
    use ResponseHandler;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($storeId)
    {
        $this->store_id = $storeId;
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
        if ($this->store_id == null) {
            return false;
        }
        $store = Store::find($this->store_id);
        $store_temp = StoreTemp::where('store_id', $this->store_id)->whereNotIn('status', ['filed rejected','change rejected', 'draft'])->first();
        $dealer = DealerTemp::where('store_id', $this->store_id)->whereNotIn('status', ['filed rejected','change rejected'])->first();
        $subDealer = SubDealerTemp::where('store_id', $this->store_id)->whereNotIn('status', ['filed rejected','change rejected'])->first();

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
       return 'kios sudah pernah di jadikan dealer/Subdealer';
    }
}

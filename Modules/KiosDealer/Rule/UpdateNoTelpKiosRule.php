<?php

namespace Modules\KiosDealer\Rule;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\SubDealer;

class UpdateNoTelpKiosRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($id, $telephone, $personel_id)
    {
        $this->id = $id;
        $this->telephone = $telephone;
        $this->personel_id = $personel_id;
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
        $store_temp = StoreTemp::query()->select('id', 'name', 'telephone', 'address', 'store_id', 'personel_id')
            ->where("id", "!=", $this->id)
            ->where("personel_id", $this->personel_id)
            ->where("telephone", $value)
            ->whereIn("status", ['accepted', 'submission of changes', 'transfered'])
            ->first();

        $store_id = $store_temp ? $store_temp->store_id : null;


        $store = Store::query()
            ->select(
                "id",
                "telephone",
                "personel_id"
            )
            ->when(!empty($store_id), function ($query) use ($store_id) {
                return $query->where('id', "!=", $store_id);
            })
            ->where("id", "!=", $this->id)
            ->where("telephone", $value)
            ->where("personel_id", $this->personel_id)
            ->whereIn("status", ['accepted', 'submission of changes', 'transfered'])
            ->first();

        if ($store) {
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
        $store_temp = StoreTemp::query()->select('id', 'name', 'telephone', 'address', 'store_id')
            ->where("id", "!=", $this->id)
            ->where("personel_id", $this->personel_id)
            ->where("telephone", $this->telephone)
            ->whereIn("status", ['accepted', 'submission of changes', 'transfered'])
            ->first();

        $store_id = $store_temp ? $store_temp->store_id : null;


        $store = Store::query()
            ->select(
                "id",
                "name",
                "address",
                "telephone",
                "personel_id"
            )
            ->when(!empty($store_id), function ($query) use ($store_id) {
                return $query->where('id', "!=", $store_id);
            })
            ->where("id", "!=", $this->id)
            ->where("telephone", $this->telephone)
            ->where("personel_id", $this->personel_id)
            ->whereIn("status", ['accepted', 'submission of changes', 'transfered'])
            ->get();

        return $store->toArray();
    }
}

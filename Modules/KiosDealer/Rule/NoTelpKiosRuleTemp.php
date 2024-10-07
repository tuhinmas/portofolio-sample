<?php

namespace Modules\KiosDealer\Rule;

use App\Traits\ResponseHandler;
use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\SubDealer;

class NoTelpKiosRuleTemp implements Rule
{
    use ResponseHandler;

    // protected $store_temp_id;
    protected $store_fix;
    protected $personel_id;
    protected $store_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($personel_id, $store_id)
    {
        $this->personel_id = $personel_id;
        $this->store_id = $store_id;
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
        $this->store_fix = Store::query()
            ->with([
                "province",
                "city",
                "district"
            ])
            ->when(!empty($this->store_id), function ($query) {
                return $query->where('id', "!=", $this->store_id);
            })
            ->where("personel_id", $this->personel_id)
            ->where("telephone", $value)
            ->select('id', 'name', 'telephone', 'address', 'personel_id', 'province_id', 'city_id', 'district_id');


        if ($this->store_fix->first()) {
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
        return $this->store_fix->get()
            ->map(function ($kios) {
                $kios->province_name = Str::title($kios->province->name);
                $kios->city_name = Str::title($kios->city->name);
                $kios->district_name = Str::title($kios->district->name);
                $kios->unsetRelation("province");
                $kios->unsetRelation("city");
                $kios->unsetRelation("district");
                return $kios;
            })
            ->toArray();
    }
}

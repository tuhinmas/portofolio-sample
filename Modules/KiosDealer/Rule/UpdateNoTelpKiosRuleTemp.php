<?php

namespace Modules\KiosDealer\Rule;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\SubDealer;

class UpdateNoTelpKiosRuleTemp implements Rule
{
    protected $store_temp_id;
    protected $store_fix;

    public function __construct($store_temp_id, $store_id = null, $telephone = null, $personel_id = null)
    {
        $this->store_temp_id = $store_temp_id;
        $this->store_id = $store_id;
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
        $store_temp = StoreTemp::findOrFail($this->store_temp_id)->first();

        $store_id_fix = $store_temp->store_id; // null / isi
        $this->store_fix = Store::query()
            ->with([
                "province",
                "city",
                "district"
            ])
            ->when(!empty($store_id_fix), function ($query) use ($store_id_fix) {
                return $query->where('id', "!=", $store_id_fix);
            })
            ->where("personel_id", $store_temp->personel_id)
            ->where("telephone", $value)
            ->whereIn("status", ['filed', 'accepted', 'submission of changes', 'transfered'])
            ->select('id', 'name', 'telephone', 'address', 'personel_id', "province_id", 'city_id','district_id');
            
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
        ->map(function($kios){
            $kios->province_name = Str::title($kios->province->name);
            $kios->city_name = Str::title($kios->city->name);
            $kios->district_name = Str::title($kios->district->name);
            $kios->unsetRelation("province");
            $kios->unsetRelation("city");
            $kios->unsetRelation("district");
            return $kios;
        })
        ->toArray();

        // $store_id = $this->store_id ? $this->store_id : null;

        // $store_temp = Store::query()->select('id', 'name', 'telephone', 'address', 'personel_id')
        //     ->when(!empty($store_id), function ($query) use ($store_id) {
        //         return $query->where('id', "!=", $store_id);
        //     })
        //     ->withAggregate(["province as provinci_name" => function($query){
        //         $query->select(DB::raw("CONCAT(UPPER(SUBSTRING(name, 1, 1)), LOWER(SUBSTRING(name, 2)))"));
        //     }],null)
        //     ->withAggregate(["city as city_name" => function($query){
        //         $query->select(DB::raw("CONCAT(UPPER(SUBSTRING(name, 1, 1)), LOWER(SUBSTRING(name, 2)))"));
        //     }],null)
        //     ->withAggregate(["district as district_name" => function($query){
        //         $query->select(DB::raw("CONCAT(UPPER(SUBSTRING(name, 1, 1)), LOWER(SUBSTRING(name, 2)))"));
        //     }],null)
        //     ->where("personel_id", $this->personel_id)
        //     ->where("telephone", $this->telephone)
        //     ->whereIn("status", ['accepted', 'submission of changes', 'transfered'])
        //     ->get();

        // return $store_temp->toArray();
    }
}

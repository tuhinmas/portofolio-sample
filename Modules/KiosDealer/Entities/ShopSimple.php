<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use Illuminate\Support\Facades\DB;
use App\Traits\SupervisorRoleTrait;
use Modules\Address\Entities\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Address\Entities\Address;

class ShopSimple extends Model
{
    use HasFactory;
    use ChildrenList;
    use SupervisorRoleTrait;
    use MarketingArea;


    protected $guarded = [];
    public $incrementing = false;
    protected $table = "view_list_toko_simple4";
    
    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\ShopSimpleFactory::new ();
    }

    public function lastDealerTemp() {
        return $this->hasOne(DealerTemp::class,"dealer_id","store_id")->latest();
    }

    public function lastSubDealerTemp() {
        return $this->hasOne(SubDealerTemp::class,"sub_dealer_id","store_id")->latest();
    }

    public function lastLastDealerTemp() {
        return $this->hasOne(DealerTemp::class,"dealer_id","id")->latest();
    }

    public function lastLastSubDealerTemp() {
        return $this->hasOne(SubDealerTemp::class,"sub_dealer_id","id")->latest();
    }

    public function scopeSupervisor($QQQ, $personel_id)
    {
        if ($this->personelRoleCheckForAllData($personel_id)) {
            return $QQQ;
        }

        /* list as supervisor */
        $personel_id = $this->getChildren($personel_id);
        return $QQQ->whereIn("personel_id", $personel_id);
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    public function district(){
        return $this->hasOne(District::class, "id", "district_id");
    }

    public function adress_detail()
    {
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function scopeFilterAll($QQQ, $filter)
    {
        return $QQQ->where("toko_name", "like", "%" . $filter . "%")
            ->orWhere("toko_id", "like", "%" . $filter . "%")
            ->orWhere("owner", "like", "%" . $filter . "%")
            ->orWhere("marketing", "like", "%" . $filter . "%");
    }
}

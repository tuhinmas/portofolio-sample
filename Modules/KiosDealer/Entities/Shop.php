<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\ChildrenList;
use App\Traits\MarketingArea;
use App\Traits\SupervisorRoleTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\SubDealerTemp;

class Shop extends Model
{

    use MarketingArea;
    use ChildrenList;
    use SupervisorRoleTrait;

    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        "id" => "string",
    ];

    protected $table = "view_toko_transaksi";
    // protected $primaryKey = 'store_id';

    public function shopTransaksiThisYear()
    {
        return $this->hasOne(ShopListTransaksiThisYear::class, 'store_id', 'store_id');
    }

    public function scopeShopTypeGradingAgencyLevel($query, $model_type, $grade, $agency_level)
    {
        return $query
            ->when(count($model_type) == 0 && count($grade) == 0 && count($agency_level) == 0, function ($QQQ) {
                return $QQQ;
            })
            ->when(count($model_type) > 0, function ($QQQ) use ($model_type) {
                return $QQQ->whereIn("model", $model_type);
            })
            ->when(count($grade) > 0, function ($QQQ) use ($grade) {
                return $QQQ->whereIn("grading_id", $grade);
            })
            ->when(count($agency_level) > 0, function ($QQQ) use ($agency_level) {
                return $QQQ->whereIn("agency_level_id", $agency_level);
            });
    }

    public function scopeActiveShop($query, $is_active)
    {
        return $query
            ->when($is_active, function ($QQQ) {
                return $QQQ->where(function ($QQQ) {
                    return $QQQ
                        ->where(function ($QQQ) {
                            return $QQQ
                                ->whereIn("model", [0, 1])
                                ->whereHas("dealer");
                        })
                        ->orWhere(function ($QQQ) {
                            return $QQQ
                                ->whereIn("model", [2])
                                ->whereHas("subDealer");
                        });
                });
            })
            ->when(!$is_active, function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->whereIn("model", [0, 1])
                                    ->whereDoesntHave("dealer");
                            })
                            ->orWhere(function ($QQQ) {
                                return $QQQ
                                    ->whereIn("model", [2])
                                    ->whereDoesntHave("subDealer");
                            });
                    });
            });
    }

    public function scopeShopFilterNameDealeridOwner($query, $parameters)
    {
        return $query->where("toko_id", "like", "%" . $parameters . "%")
            ->where("toko_name", "like", "%" . $parameters . "%")
            ->where("owner", "like", "%" . $parameters . "%");

    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
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

    public function dealerTemp()
    {
        return $this->hasOne(DealerTemp::class, "dealer_id", "store_id")->whereIn("status", ["submission of changes", "wait approval"]);
    }

    public function subDealerTemp()
    {
        return $this->hasOne(SubDealerTemp::class, "sub_dealer_id", "store_id")->whereIn("status", ["submission of changes", "wait approval"]);
    }

    public function subDealer()
    {
        return $this->hasOne(SubDealer::class, "id", "store_id");
    }

    public function dealer()
    {
        return $this->hasOne(Dealer::class, "id", "store_id");
    }
}

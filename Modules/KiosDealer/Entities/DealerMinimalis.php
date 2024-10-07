<?php

namespace Modules\KiosDealer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\Grading;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class DealerMinimalis extends Model
{
    use SoftDeletes;
    protected $table = "dealers";

    

    public $incrementing = false;
    protected $casts = [
        "id" => "string",
    ];

    protected $guarded = [];

    public function grading()
    {
        return $this->hasOne(Grading::class, "id", "grading_id");
    }

    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class, 'store_id', 'id')->orderBy("date", "desc");
    }

    public function addressDetail()
    {
        return $this->hasMany(Address::class, "parent_id", "id")->with("district");
    }

        /*
     * scope list dealer d1/d2 or distributor
     */
    public function scopeDistributor($qqq)
    {
        $agency_levels = DB::table('agency_levels')
            ->where("name", "D1")
            ->orWhere("name", "D2")
            ->get()
            ->pluck("id")
            ->toArray();

        return $qqq
            ->where("is_distributor", true)
            ->orWhere(function ($qqq) use ($agency_levels) {
                return $qqq->whereIn("agency_level_id", $agency_levels);
            });

        /**
         * pending code
         *
         *     return $qqq
         *         ->where("is_distributor", true)
         *         ->orWhere(function ($qqq) use ($agency_levels) {
         *             return $qqq->whereIn("agency_level_id", $agency_levels)
         *                 ->whereHas("salesOrders", function ($qqq) use ($agency_levels) {
         *                     return $qqq->whereHas("sales_order_detail", function ($qqq) use ($agency_levels) {
         *                         return $qqq->whereIn("agency_level_id", $agency_levels);
         *                     });
         *                 });
         *         });
         * });
         */
    }


}
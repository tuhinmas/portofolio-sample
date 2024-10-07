<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportRequests extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;


    public $incrementing = false;
    protected $guarded = [];

    
    public function scopeLastDealerReady($QQQ)
    {
        // dd("as");
        return $QQQ->where("type", "dealer")->where("status", "ready")->orderBy("created_at", "desc")->limit(1);

    }

    public function scopeLastSalesOrderReady($QQQ)
    {
        // dd("as");
        return $QQQ->where("type", "all_sales")->where("status", "ready")->orderBy("created_at", "desc")->limit(1);

    }

    public function scopeLastSubDealerReady($QQQ)
    {
        return $QQQ->where("type", "subdealer")->where("status", "ready")->latest()->limit(1);
    }

    public function scopeLastKiosReady($QQQ)
    {
        return $QQQ->where("type", "kios")->where("status", "ready")->latest()->limit(1);
    }

    
    public function scopeLastDetailDirectSalesReady($QQQ)
    {
        return $QQQ->where("type", "sales_order_direct_detail")->where("status", "ready")->latest()->limit(1);
    }

    public function scopeLastDirectSalesReady($QQQ)
    {
        return $QQQ->where("type", "sales_order_direct")->where("status", "ready")->latest()->limit(1);
    }

    public function scopeLastIndirectSalesReady($QQQ)
    {
        return $QQQ->where("type", "sales_order_indirect")->where("status", "ready")->latest()->limit(1);
    }

    public function scopeLastDetailIndirectSalesReady($QQQ)
    {
        return $QQQ->where("type", "sales_order_indirect_detail")->where("status", "ready")->latest()->limit(1);
    }

    public function scopeLastMarketingAreaWithoutPlantReady($QQQ)
    {
        return $QQQ->where("type", "marketing_area_district")->where("status", "ready")->latest()->limit(1);
    }

    public function scopeLastShopReady($QQQ)
    {
        return $QQQ->where("type", "shop")->where("status", "ready")->latest()->limit(1);
    }
}

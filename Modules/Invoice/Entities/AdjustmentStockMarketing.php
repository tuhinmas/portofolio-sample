<?php

namespace Modules\Invoice\Entities;

use App\Traits\Uuids;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\Distributor\Entities\DistributorContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdjustmentStockMarketing extends Model
{
    use SuperVisorCheckV2;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $guarded = [
        "created_at",
        "updated_at"
    ];
    
    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\AdjustmentAtockMarketingFactory::new();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, "product_id", "id")->with('package');
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, "dealer_id", "id");
    }
    public function distributor()
    {
        return $this->belongsTo(Dealer::class, "dealer_id", "id");
    }

    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id")->with('position');
    }

    public function activeContract()
    {
        return $this->hasOne(DistributorContract::class, "id", "contract_id")
            ->where("contract_start", "<=", now()->format("Y-m-d"))
            ->where("contract_end", ">=", now()->format("Y-m-d"));
    }

    public function adjusmentStock()
    {
        return $this->hasMany(AdjustmentStock::class, "contract_id", "contract_id")
            ->orderBy("opname_date", "desc");
    }
}

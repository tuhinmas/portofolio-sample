<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Product;

class Price extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];
    protected $current_ppn = null;

    protected $appends = [
        "price_with_ppn",
        "het_with_ppn"
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\ProductPriceFactory::new ();
    }

    public function agency_level()
    {
        return $this->belongsTo(AgencyLevel::class, 'agency_level_id', 'id');
    }

    public function agencyLevel()
    {
        return $this->belongsTo(AgencyLevel::class, 'agency_level_id', 'id');
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class, 'price_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function getPriceWithPpnAttribute($value)
    {
        if ($this->current_ppn) {
            return $this->price + ($this->price * $this->current_ppn / 100);
        }

        $ppn = DB::table('ppn')
            ->whereNull("deleted_at")
            ->where("period_date", "<=", now()->format("Y-m-d"))
            ->orderBy("period_date", "desc")
            ->orderBy("created_at", "desc")
            ->first()
        ?->ppn;
        $this->current_ppn = $ppn ?? 10;
        return $this->price + ($this->price * $this->current_ppn / 100);
    }

    public function getHetWithPpnAttribute($value)
    {
        if ($this->current_ppn) {
            return $this->het + ($this->het * $this->current_ppn / 100);
        }

        $ppn = DB::table('ppn')
            ->whereNull("deleted_at")
            ->where("period_date", "<=", now()->format("Y-m-d"))
            ->orderBy("period_date", "desc")
            ->orderBy("created_at", "desc")
            ->first()
        ?->ppn;
        $this->current_ppn = $ppn ?? 10;
        return $this->het + ($this->het * $this->current_ppn / 100);
    }
}

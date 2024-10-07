<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Product;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fee extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $guarded = [];
    public $incrementing = false;

    protected $table = "fee_products";
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\FeeProductFactory::new ();
    }

    /**
     * activity logs set causer
     *
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    /**
     * activity logs
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function product()
    {
        return $this->hasMany(Product::class, 'id', 'product_id')->with("category");
    }

    public function productAs()
    {
        return $this->hasOne(Product::class, 'id', 'product_as');
    }

    public function productHasOne()
    {
        return $this->hasOne(Product::class, 'id', 'product_id')->orderBy("size", "asc")->with("category");
    }

    public function productReference()
    {
        return $this->hasOne(Product::class, 'id', 'product_id')->with("category");
    }

    public function scopeProductCategory($query, $category)
    {
        $category_id = DB::table('product_categories')->where("name", $category)->pluck("id")->first();
        $product_list = DB::table('products')->where("category", $category_id)->get()->pluck("id");
        return $query->whereIn("product_id", $product_list);
    }

    public function scopeByRegulerYearQuarterType($query, $year, $quarter)
    {
        return $query->where("year", $year)
            ->where("quartal", $quarter)
            ->where("type", "1");
    }

    public function parentRegulerByYearQuarter()
    {
        return $this->hasOne(static::class, "product_id", "product_as");
    }
}

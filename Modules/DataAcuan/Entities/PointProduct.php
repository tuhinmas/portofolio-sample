<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Product;

class PointProduct extends Model
{
    use HasFactory, Uuids, SoftDeletes;

    protected $guarded = [];
    protected $appends = [
        "quantity_to_package"
    ];

    protected $table = "point_products";

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PointProductFactory::new ();
    }

    public function product()
    {
        return $this->hasOne(Product::class, "id", "product_id")->with("package", "category");
    }

    public function getQuantityToPackageAttribute()
    {
        $package = $this->package_check($this->attributes["product_id"]);
        $quantity_display = $this->attributes["minimum_quantity"];
        if ($package) {
            $quantity_display = $this->attributes["minimum_quantity"] / $package->quantity_per_package;
        }
        return $quantity_display;
    }

    /**
     * check product package is active and packaging
     *
     * @param [type] $product_id
     * @return void
     */
    public function package_check($product_id)
    {

        $product = Product::find($product_id);
        $packages = DB::table('packages')
            ->where('product_id', $product_id)
            ->whereNull("deleted_at")
            ->where("isActive", "1")
            ->first();

        $data = null;
        if ($product) {
            $packaging = $product->unit;
            $quantity_per_package = 1;
            if ($packages) {
                $quantity_per_package = $packages->quantity_per_package;
                $packaging = $packages->packaging;
            }
    
            $data = (object) [
                'packaging' => $packaging,
                'quantity_per_package' => $quantity_per_package,
            ];
        }
        return $data;
    }
}

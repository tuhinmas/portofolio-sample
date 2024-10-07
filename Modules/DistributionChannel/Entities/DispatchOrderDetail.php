<?php

namespace Modules\DistributionChannel\Entities;

use App\Traits\Uuids;
use App\Traits\SuperVisorCheckV2;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;

class DispatchOrderDetail extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use SuperVisorCheckV2;

    protected $table = 'dispatch_order_detail';

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DistributionChannel\Database\factories\DispatchOrderDetailFactory::new();
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
        return $this->belongsTo(Product::class, "id_product", "id")->with("package")->withTrashed();
    }

    public function dispatchOrder()
    {
        return $this->belongsTo(DispatchOrder::class, "id_dispatch_order", "id");
    }

    public function salesOrderDetail()
    {
        $columnSod = Schema::getColumnListing('sales_order_details');
        $filteredColumns = array_filter($columnSod, function($column) {
            return $column !== 'id';
        });
    
        $selectSalesOrderDetail = [];
        foreach ($filteredColumns as $column) {
            $selectSalesOrderDetail[] = 'sod.' . $column;
        };

        return $this->hasOne(DispatchOrderDetail::class, 'id', 'id')
            ->select(
                'dispatch_order_detail.id as id',
                'p.packaging as package_packaging',
                'p.quantity_per_package as package_quantity_per_package',
                'p.unit as package_unit',
                'p.weight as package_weight',
                ...$selectSalesOrderDetail
            )
            ->join('sales_order_details as sod', function ($join) {
                $join->on('sod.product_id', '=', 'dispatch_order_detail.id_product')
                    ->whereNull('sod.deleted_at');
            })
            ->join('discpatch_order as do', function ($join) {
                $join->on('dispatch_order_detail.id_dispatch_order', '=', 'do.id')
                    ->whereNull('do.deleted_at');
            })
            ->join('invoices as inv', function ($join) {
                $join->on('do.invoice_id', '=', 'inv.id')
                    ->whereNull('inv.deleted_at');
            })
            ->leftJoin('packages as p', function ($join) {
                $join->on('sod.package_id', '=', 'p.id')
                    ->whereNull('p.deleted_at');
            })
            ->whereNull('dispatch_order_detail.deleted_at')
            ->whereColumn('sod.sales_order_id', 'inv.sales_order_id');
    }

}

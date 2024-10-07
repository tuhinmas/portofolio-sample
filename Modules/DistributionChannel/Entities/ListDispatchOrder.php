<?php

namespace Modules\DistributionChannel\Entities;

use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\Driver;
use Modules\Invoice\Entities\Invoice;

class ListDispatchOrder extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DistributionChannel\Database\factories\ListDispatchOrderFactory::new();
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, "id_invoice", "id")->with("salesOrder.dealerv2");
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, "id_driver", "id");
    }

    public function detail_dispatch_order()
    {
        return $this->hasMany(DispatchOrder::class, "id_list_dispatch_order", "id")->orderBy("date_sent", "desc");
    }

    public function scopeHasHystoryDispatch($query, $name)
    {
        if($name == 'yes'){
            return $query->whereHas("detail_dispatch_order");
        } else {
            return $query->whereDoesntHave('detail_dispatch_order');
        }
    }

}

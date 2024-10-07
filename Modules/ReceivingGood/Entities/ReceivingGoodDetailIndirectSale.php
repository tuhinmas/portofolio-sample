<?php

namespace Modules\ReceivingGood\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReceivingGoodDetailIndirectSale extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\ReceivingGoodDetailIndirectSaleFactory::new();
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

    public function receivingGoodIndirect()
    {
        return $this->belongsTo(ReceivingGoodIndirectSale::class, "receiving_good_id", "id");
    }
}

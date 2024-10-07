<?php

namespace Modules\ReceivingGood\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ReceivingGood\Entities\ReceivingGoodDetailIndirectSale;

class ReceivingGoodIndirectSale extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $casts = [
        "id" => "string"
    ];

    protected $guarded = [];
    
    protected $enumStatuses = [1, 2, 3, 4, 5];

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    protected static function newFactory()
    {
        return \Modules\ReceivingGood\Database\factories\ReceivingGoodIndirectSaleFactory::new();
    }

    public function receivingGoodDetailIndirect()
    {
        return $this->hasMany(ReceivingGoodDetailIndirectSale::class, "receiving_good_id", "id");
    }

    public function receivingGoodIndirectFile()
    {
        return $this->hasMany(ReceivingGoodIndirectFile::class, "receiving_good_id", "id");
    }
}

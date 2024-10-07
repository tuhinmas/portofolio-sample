<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Porter extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $table = "porters";

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PorterFactory::new ();
    }
    
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id")->with("position");
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");
    }
}

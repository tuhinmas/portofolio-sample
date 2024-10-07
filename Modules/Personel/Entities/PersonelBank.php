<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Modules\DataAcuan\Entities\Bank;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonelBank extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public $incrementing = false;
    protected $guarded = [];
    protected $table = 'bank_personels';
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\PersonelBankFactory::new();
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
    
    public function bank(){
        return $this->belongsTo(Bank::class,'bank_id','id');
    }
}

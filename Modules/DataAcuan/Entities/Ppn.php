<?php

namespace Modules\DataAcuan\Entities;

use App\Models\User;
use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ppn extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;
    use LogsActivity;


    protected $table = "ppn";
    protected $fillable = [
        "ppn",
        "code_account",
        "user_id",
        "period_date"
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id', 'id');
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
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PpnFactory::new();
    }
}

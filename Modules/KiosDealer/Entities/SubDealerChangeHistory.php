<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\KiosDealer\Entities\SubDealerDataHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubDealerChangeHistory extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use CascadeSoftDeletes;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    public $incrementing = false;

    protected $casts = [
        "id" => "string",
    ];

    protected $guarded = [];

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

    public function subDealer(){
        return $this->belongsTo(subDealer::class, 'sub_dealer_id', 'id');
    }

    public function subDealerTemp(){
        return $this->belongsTo(subDealerTemp::class, 'sub_dealer_temp_id', 'id');
    }

    public function submitedBy(){
        return $this->belongsTo(Personel::class, 'submited_by', 'id');
    }

    public function confirmedBy(){
        return $this->belongsTo(Personel::class, 'confirmed_by', 'id');
    }

    public function approvedBy(){
        return $this->belongsTo(Personel::class, 'approved_by', 'id');
    }

    public function subDealerDataHistory()
    {
        return $this->hasOne(SubDealerDataHistory::class, "sub_dealer_change_history_id", "id");
    }
}

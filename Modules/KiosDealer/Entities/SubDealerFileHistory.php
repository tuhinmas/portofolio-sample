<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubDealerFileHistory extends Model
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
        return $this->belongsTo(SubDealer::class, 'sub_dealer_id', 'id');
    }

    public function subDealerTemp(){
        return $this->belongsTo(SubDealerTemp::class, 'sub_dealer_temp_id', 'id');
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
}

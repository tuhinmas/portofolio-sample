<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubDealerDataHistory extends Model
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
        return $this->belongsTo(SubDealer::class, 'dealer_id', 'id');
    }

    public function subDealerAddress(){
        return $this->hasOne(SubDealerAddressHistory::class, 'sub_dealer_data_history_id', 'id');
    }

    public function subDealerAddresses(){
        return $this->hasMany(SubDealerAddressHistory::class, 'sub_dealer_data_history_id', 'id');
    }

    public function subDealerFileHistory(){
        return $this->hasOne(SubDealerFileHistory::class, 'sub_dealer_data_history_id', 'id');
    }

    public function subDealerFileHistories(){
        return $this->hasMany(SubDealerFileHistory::class, 'sub_dealer_data_history_id', 'id');
    }
}

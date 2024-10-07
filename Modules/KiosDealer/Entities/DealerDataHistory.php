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

class DealerDataHistory extends Model
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

    public function dealer(){
        return $this->belongsTo(Dealer::class, 'dealer_id', 'id');
    }

    public function dealerAddress(){
        return $this->hasOne(DealerAddressHistory::class, 'dealer_data_history_id', 'id');
    }

    public function dealerAddresses(){
        return $this->hasMany(DealerAddressHistory::class, 'dealer_data_history_id', 'id');
    }

    public function dealerFileHistory(){
        return $this->hasOne(DealerFileHistory::class, 'dealer_data_history_id', 'id');
    }

    public function dealerFileHistories(){
        return $this->hasMany(DealerFileHistory::class, 'dealer_data_history_id', 'id');
    }
}

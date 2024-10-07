<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\KiosDealer\Entities\DealerDataHistory;

class DealerChangeHistory extends Model
{
    use Uuids;
    use LogsActivity;
    use CascadeSoftDeletes;

    public $incrementing = false;

    protected $casts = [
        "id" => "string",
    ];

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerChangeHistoryFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class, 'dealer_id', 'id');
    }

    public function dealerTemp()
    {
        return $this->belongsTo(DealerTemp::class, 'dealer_temp_id', 'id');
    }

    public function submitedBy()
    {
        return $this->belongsTo(Personel::class, 'submited_by', 'id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(Personel::class, 'confirmed_by', 'id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Personel::class, 'approved_by', 'id');
    }

    public function dealerDataHistory()
    {
        return $this->belongsTo(DealerDataHistory::class, "id", "dealer_change_history_id");
    }

}

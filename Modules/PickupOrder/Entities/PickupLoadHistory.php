<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\Uuids;
use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PickupLoadHistory extends Model
{
    use HasFactory, Uuids, SoftDeletes, ActivityTrait, LogsActivity;

    protected $guarded = [];
    protected static function newFactory()
    {
        return \Modules\PickupOrder\Database\factories\PickupLoadHistoryFactory::new ();
    }

    public function pickupDispatchAble()
    {
        return $this->morphTo(__FUNCTION__, 'dispatch_type', 'dispatch_id');
    }

    public function createdBy()
    {
        return $this->hasOne(Personel::class, "id", "created_by");
    }
}

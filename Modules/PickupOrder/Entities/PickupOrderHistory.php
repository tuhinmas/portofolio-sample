<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\ActivityTrait;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\Driver;
use Modules\DataAcuan\Entities\Warehouse;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class PickupOrderHistory extends Model
{
    use HasFactory, Uuids, SoftDeletes, ActivityTrait, LogsActivity;

    protected $guarded = [];

}

<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LogPorter extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\LogPorterFactory::new();
    }
}

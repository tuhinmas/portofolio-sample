<?php

namespace Modules\Invoice\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvoiceBenefit extends Model
{
    use HasFactory, Uuids, SoftDeletes, LogsActivity;
    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\InvoiceBenefitFactory::new();
    }

    public function invoice(){
        return $this->be;
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
}

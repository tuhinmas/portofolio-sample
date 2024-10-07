<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DealerGradeSuggestion extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $guarded = [];
    protected $casts = [
        "id" => "string",
        "payment_methods" => "array"
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\DealerGradeSuggestionFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    /**
     * activity logs
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function grading(){
        return $this->hasOne(Grading::class, "id", "grading_id");
    }
    public function suggestedGrading(){
        return $this->hasOne(Grading::class, "id", "suggested_grading_id");
    }
}

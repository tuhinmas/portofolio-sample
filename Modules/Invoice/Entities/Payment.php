<?php

namespace Modules\Invoice\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory, SoftDeletes, Uuids, LogsActivity;

    protected $casts = [
        "id" => "string",
    ];
    protected $guarded = [];
    public $incrementing = false;

    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\PaymentFactory::new ();
    }

    protected static function booted()
    {
        parent::boot();
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, "invoice_id", "id")->withTrashed();
    }

    public function personel()
    {
        return $this->hasOne(User::class, "id", "user_id")->withTrashed();
    }

    public function reporter()
    {
        return $this->hasOneThrough(
            Personel::class,
            User::class,
            "id",
            "id",
            "user_id",
            "personel_id",
        );
    }

    public function invoicePayment()
    {
        return $this->belongsTo(Invoice::class, "invoice_id", "id");
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

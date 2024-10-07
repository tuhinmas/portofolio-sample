<?php

namespace Modules\Invoice\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Modules\KiosDealerV2\Entities\DealerV2;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Invoice\Entities\CreditMemoDetail;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditMemo extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use CascadeSoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = [
        "id" => "string",
    ];

    protected $enumStatuses = [
        "accepted",
        "canceled",
    ];

    protected $guarded = [
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    protected $cascadeDeletes = [
        "creditMemoDetail"
    ];
    protected $dates = ['deleted_at'];

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\CreditMemoFactory::new ();
    }

    public function creditMemoDetail()
    {
        return $this->hasMany(CreditMemoDetail::class, "credit_memo_id", "id");
    }

    public function origin()
    {
        return $this->hasOne(Invoice::class, "id", "origin_id");
    }

    public function destination()
    {
        return $this->hasOne(Invoice::class, "id", "destination_id");
    }

    public function dealer()
    {
        return $this->hasOne(DealerV2::class, "id", "dealer_id");
    }

    public function creditMemoHistories()
    {
        return $this->hasMany(CreditMemoHistory::class, "credit_memo_id", "id");
    }

    public function scopeValidCreditMemo($query)
    {
        return $query->where("status", "accepted");
    }
}

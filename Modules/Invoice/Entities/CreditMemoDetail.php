<?php

namespace Modules\Invoice\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\Invoice\Entities\CreditMemo;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditMemoDetail extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
        "deleted_at",
    ];

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
        return \Modules\Invoice\Database\factories\CreditMemoDetailFactory::new ();
    }

    public function product()
    {
        return $this->hasOne(Product::class, "id", "product_id");
    }

    public function creditMemo()
    {
        return $this->hasOne(CreditMemo::class, "id", "credit_memo_id");
    }
}

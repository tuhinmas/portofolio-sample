<?php

namespace Modules\ReceivingGood\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReceivingGoodIndirectFile extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $casts = [
        "id" => "string",
    ];

    protected $appends = [
        "link",
    ];

    protected $guarded = [];

    protected $enumAttachmentStatuses = ["confirm", "report"];

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    protected static function newFactory()
    {
        return \Modules\ReceivingGood\Database\factories\ReceivingGoodIndirectFileFactory::new();
    }

    public function receivingGoodIndirect()
    {
        return $this->hasMany(ReceivingGoodIndirectSale::class, "receiving_good_id", "id");
    }

    public function getLinkAttribute()
    {
        $path_to_file = "public/indirect/receiving-good/attachment/" . $this->attachment;

        return Storage::disk("s3")->url($path_to_file);
    }
}

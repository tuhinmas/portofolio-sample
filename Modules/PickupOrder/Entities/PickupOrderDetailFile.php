<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PickupOrderDetailFile extends Model
{
    use HasFactory, Uuids, SoftDeletes, ActivityTrait, LogsActivity;
    use Enums;

    protected $enumTypes = ["load", "unload"];
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\PickupOrder\Database\factories\PickupOrderDetailFileFactory::new ();
    }

    public function getAttachmentAttribute($value){
        return Storage::disk("s3")->url($value);
    }
}

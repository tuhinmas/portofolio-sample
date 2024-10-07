<?php

namespace Modules\PickupOrder\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PickupOrderFile extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;

    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    /**
     * STATUSES
     *
     * Armada (BAK)
     * Armada (Kepala)
     * Supir
     * SIM
     * STNK
     * KTP Supir
     * KIR
     */
    protected static function newFactory()
    {
        return \Modules\PickupOrder\Database\factories\PickupOrderFileFactory::new();
    }

    public function getAttachmentAttribute($value)
    {
        return Storage::disk("s3")->url($value);
    }
}

<?php

namespace Modules\PickupOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MobileWarehousingVersion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "version",
        "environment",
        "note",
        "link",
    ];

    protected $casts = [
        "note" => "json"
    ];

    protected static function newFactory()
    {
        return \Modules\PickupOrder\Database\factories\MobileWarehousingVersionFactory::new ();
    }
}

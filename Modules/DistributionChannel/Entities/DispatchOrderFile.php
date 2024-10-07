<?php

namespace Modules\DistributionChannel\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DispatchOrderFile extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use Enums;

    protected $guarded = [];
    protected $enumCaptions = [
        "Armada",
        "Armada (bak)",
        "Armada (kepala)",
        "Barang",
        "Supir",
        "supir",
        "KTP Supir",
        "KIR",
        "STNK",
        "SIM",
        "lain-lain"
    ];

    
    protected static function newFactory()
    {
        return \Modules\DistributionChannel\Database\factories\DispatchOrderFileFactory::new();
    }
}

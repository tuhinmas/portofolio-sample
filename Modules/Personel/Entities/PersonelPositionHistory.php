<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonelPositionHistory extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\PersonelPositionHistoryFactory::new();
    }
}

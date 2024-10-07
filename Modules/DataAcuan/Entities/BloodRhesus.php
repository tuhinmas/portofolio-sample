<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BloodRhesus extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;
    
    protected $guarded = [];    
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\BloodRhesusFactory::new();
    }
}

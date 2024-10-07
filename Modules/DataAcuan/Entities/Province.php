<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Province extends Province
{
    use HasFactory;
    use TimeSerilization;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\ProvinceFactory::new();
    }
}

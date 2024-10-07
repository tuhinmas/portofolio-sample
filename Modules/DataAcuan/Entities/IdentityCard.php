<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IdentityCard extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;

    protected $guarded = [];
    protected $table = "identity_cards";
    
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\IdentityCardFactory::new();
    }
}

<?php

namespace Modules\Personel\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IdentityCard extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;

    protected $fillable = [];
    protected $table = "identity_cards";
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\IdentityCardFactory::new();
    }
}

<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entity extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $fillable = [
        'name'
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\EntityFactory::new();
    }

    public function oraganisation(){
        return $this->hasMany(Organisation::Class,'id','entity_id');
    }
    
}

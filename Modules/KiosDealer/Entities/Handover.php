<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Handover extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    protected $fillable = [];
    protected $casts = [
        'id' => 'string'
    ];
    public $incrementing = false;

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\HandoverFactory::new();
    }

    public function dealer(){
        return $this->hasMany(Dealer::class, "handover_status", "id");
    }
}

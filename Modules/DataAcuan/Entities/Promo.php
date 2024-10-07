<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Personel\Entities\Personel;

class Promo extends Model
{
    use HasFactory, Uuids, SoftDeletes;

    protected $guarded = [];

    public function createdBy()
    {
        return $this->hasOne(Personel::class,'id','created_by');
    }
}

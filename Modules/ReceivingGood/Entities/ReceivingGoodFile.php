<?php

namespace Modules\ReceivingGood\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReceivingGoodFile extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;

    protected $guarded = [];
    public $incrementing = false;

    protected static function newFactory()
    {
        return \Modules\ReceivingGood\Database\factories\ReceivingGoodFileFactory::new();
    }

    public function receivingGood(){
        return $this->belongsTo(ReceivingGood::class, "receiving_good_id", "id");
    }
}

<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\AgencyLevel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeePosition extends Model
{
    use HasFactory, Uuids, SoftDeletes;

    protected $guarded = [
        "is_applicator",
        "is_mm",
        "fee_as_marketing"
    ];

    public $incrementing = false;
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\FeePositionFactory::new();
    }

    public function position(){
        return $this->hasOne(Position::class, "id", "position_id");
    }

    public function feeCashMinimumOrder(){
        return $this->hasOne(AgencyLevel::class, "id", "fee_cash_minimum_order");
    }
}

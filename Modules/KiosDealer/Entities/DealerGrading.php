<?php

namespace Modules\KiosDealer\Entities;

use App\Models\User;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DealerGrading extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    protected $guarded = [];
    public $incrementing = false;

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerGradingFactory::new();
    }

    public function dealer(){
        return $this->hasOne(Dealer::Class, 'id', 'dealer_id')->with('personel', 'agencyLevel', 'dealer_file', 'handover', 'grading', 'adress_detail', 'salesOrder');
    }

    public function grading(){
        return $this->belongsTo(Grading::class, "grading_id", "id");
    }

    public function personel(){
        return $this->belongsTo(User::class, "user_id", "id");
    }
}

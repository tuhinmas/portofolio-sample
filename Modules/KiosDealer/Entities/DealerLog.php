<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Authentication\Entities\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DealerLog extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    
    protected $guarded = [];
    public $incrementing = false;

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerLogFactory::new();
    }
    public function dealer(){
        return $this->hasOne(Dealer::class, 'dealer_id', 'id');
    }
    public function user(){
        return $this->hasOne(User::class, 'user_id', 'id');
    }
}

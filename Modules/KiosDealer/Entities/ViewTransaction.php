<?php

namespace Modules\KiosDealer\Entities;


use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealerV2\Entities\SubDealerV2;

class ViewTransaction extends Model
{

    public $incrementing =false;
    protected $guarded = [];
    protected $casts = [
        "id" => "string"
    ];

    protected $appends = [
        "model"
    ];

    protected $table = "view_toko_transaksi";

    public function getModelAttribute()
    {
        if($this->model==0){
            return "Distributor";
        }elseif($this->model==1){
            return "Dealer";
        }elseif($this->model==2){
            return "Sub Dealer";
        }else{
            return "-";
        }; 
    }

}

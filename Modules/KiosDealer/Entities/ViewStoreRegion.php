<?php

namespace Modules\KiosDealer\Entities;


use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealerV2\Entities\SubDealerV2;

class ViewStoreRegion extends Model
{

    public $incrementing =false;
    protected $guarded = [];
    protected $casts = [
        "id" => "string"
    ];

    protected $appends = [
        "toko_name"
    ];

    protected $table = "view_store_region";



}

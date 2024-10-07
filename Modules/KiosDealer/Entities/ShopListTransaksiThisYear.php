<?php

namespace Modules\KiosDealer\Entities;


use Illuminate\Database\Eloquent\Model;

class ShopListTransaksiThisYear extends Model
{

    public $incrementing =false;
    protected $guarded = [];
    protected $casts = [
        "id" => "string"
    ];

    protected $table = "view_list_transaksi_this_year";
}

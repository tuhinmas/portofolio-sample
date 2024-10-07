<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\DealerTemp;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class DealerFileTemp extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    
    protected $guarded = [];
    protected $table = "dealer_file_temps";
    protected $casts = [
        "id" => "string"
    ];

    protected $appends = [
        'file_url'
    ];


    public $incrementing = false;
    
    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerFileTempFactory::new();
    }

    public function dealer()
    {
        return $this->belongsTo(DealerTemp::class,'dealer_id','id');
    }

    public function getFileUrlAttribute()
    {
        if ($this->data != null) {
            return Storage::disk('s3')->url('public/dealer/'.$this->data);
        }

        return '';
    }
}

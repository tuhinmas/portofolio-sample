<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\BussinessSector;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BussinessSectorCategory extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $fillable = [
        'name'
    ];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\BussinessSectorCategoryFactory::new();
    }
    public function sector(){
        return $this->hasMany(BussinessSector::class,'category_id','id');
    }
}

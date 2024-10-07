<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\BussinessSector;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\BussinessSectorCategory;

class BussinessSector extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $fillable = [
        'name',
        'category_id',
    ];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\BussinessSectorFactory::new();
    }
    public function category(){
        return $this->belongsTo(BussinessSectorCategory::class,'category_id','id');
    }

    public function bussiness_organisation(){
        return $this->belongsToMany(Organisation::class,'bussiness_organisations','bussiness_sector_id','organisation_id')->with('category');
    }
}

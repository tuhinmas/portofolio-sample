<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Modules\DataAcuan\Entities\Plant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlantCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;
    use CascadeSoftDeletes;

    // protected $cascadeDeletes = [
    //     'plant',
    // ];

    // protected $dates = ['deleted_at'];

    protected $fillable = ["name"];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PlantCategoryFactory::new();
    }

    public function plant(){
        return $this->hasMany(Plant::class, 'plant_category_id', 'id');
    }
}

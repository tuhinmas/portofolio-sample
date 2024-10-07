<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\PlantCategory;
use Modules\PlantingCalendar\Entities\PlantingCalendar;

class Plant extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    
    protected $guarded = [];
    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PlantFactory::new ();
    }

    public function category()
    {
        return $this->belongsTo(PlantCategory::class, 'plant_category_id', 'id');
    }

    public function plantingCalendar()
    {
        return $this->hasMany(PlantingCalendar::class, "plant_id", "id");
    }
}

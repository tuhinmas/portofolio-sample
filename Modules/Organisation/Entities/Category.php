<?php

namespace Modules\Organisation\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Entities\Category;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];
    protected static function newFactory()
    {
        return \Modules\Organisation\Database\factories\CategoryFactory::new ();
    }

    public function organisation()
    {
        return $this->belongsToMany(Organisation::class,'category_organisations');
    }
}

<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganisationCategory extends Model
{
    use HasFactory;

    use SoftDeletes;
    protected $guarded = [];
    protected $table = "categories";
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\OrganisationCategoryFactory::new();
    }
}

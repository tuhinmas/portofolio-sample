<?php

namespace Modules\Organisation\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryOrganisation extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $guarded = [];
    public $timestamps = false;
    protected static function newFactory()
    {
        return \Modules\Organisation\Database\factories\CategoryOrganisationFactory::new();
    }
}

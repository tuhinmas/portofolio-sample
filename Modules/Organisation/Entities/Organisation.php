<?php

namespace Modules\Organisation\Entities;

use App\Traits\Uuids;
use App\Models\Address;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Entities\Entity;
use Modules\Organisation\Entities\Category;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\BussinessSector;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organisation extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Organisation\Database\factories\OrganisationFactory::new();
    }

    public function category(){
        return $this->belongsToMany(Category::class,'category_organisations');
    }

    public function bussiness_sector(){
        return $this->belongsToMany(BussinessSector::class,'bussiness_organisations','organisation_id','bussiness_sector_id')->with('category');
    }

    public function contact(){
        return $this->hasMany(Contact::class,'parent_id','id');
    }

    public function entity(){
        return $this->belongsTo(Entity::Class,'entity_id','id');
    }

    public function address(){
        return $this->hasMany(Address::class,'parent_id','id')->with('country', 'province', 'city', 'district');
    }

    public function holding(){
         return $this->belongsTo(Holding::class, 'holding_id','id');
    }
}

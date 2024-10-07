<?php

namespace Modules\Personel\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingHierarchy extends Model
{
    use HasFactory;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\MarketingHierarchyFactory::new();
    }

    public function marketing(){
        return $this->hasOne(Personel::class, "name", "marketing");
    }

    public function rmc(){
        return $this->hasOne(Personel::class, "name", "rmc");
    }
   
    public function astMdm(){
        return $this->hasOne(Personel::class, "name", "ast_mdm");
    }
   
    public function mdm(){
        return $this->hasOne(Personel::class, "name", "mdm");
    }
   
    public function mm(){
        return $this->hasOne(Personel::class, "name", "mm");
    }
   
    public function aplikator(){
        return $this->hasOne(Personel::class, "name", "aplikator");
    }
}

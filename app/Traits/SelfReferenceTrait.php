<?php

namespace App\Traits;

trait SelfReferenceTrait
{
    protected $parentColumn = 'supervisor_id';


    public function parent()
    {
        return $this->hasOne(static::class, "id", "supervisor_id")->with('parent');
    }

    public function supervisor()
    {
        return $this->hasOne(static::class, "id", "supervisor_id");
    }

    public function children()
    {
        return $this->hasMany(static::class, $this->parentColumn);
    }

    public function childrenAplikator()
    {
        return $this->hasMany(static::class, $this->parentColumn)->whereHas("position", function ($query) {
            $query->where("name", "Aplikator");
        });
    }


    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function root()
    {
        return $this->parent
            ? $this->parent->root()
            : $this;
    }
}

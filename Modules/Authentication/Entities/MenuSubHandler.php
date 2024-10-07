<?php

namespace Modules\Authentication\Entities;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Entities\MenuHandler;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuSubHandler extends Model
{
    use HasFactory;

    protected $guarded = [
        "created_at",
        "updated_at",
        "deleted_at"
    ];    
    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\SubMenuHandlerFactory::new();
    }

    public function menu(){
        return $this->belongsTo(MenuHandler::class, "menu_id");
    }

    
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}

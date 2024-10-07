<?php

namespace Modules\Authentication\Entities;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Entities\MenuSubHandler;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuHandler extends Model
{
    use HasFactory;

    protected $guarded = [
        "created_at",
        "updated_at",
        "deleted_at"
    ];
    
    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\MenuHandlerFactory::new();
    }

    public function subMenu(){
        return $this->hasmany(MenuSubHandler::class, "id", "menu_id");
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


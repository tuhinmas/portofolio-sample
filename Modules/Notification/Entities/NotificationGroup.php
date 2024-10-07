<?php

namespace Modules\Notification\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Notification\Entities\NotificationGroupDetail;

class NotificationGroup extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Notification\Database\factories\NotificationGroupFactory::new();
    }

    public function notificationGroupDetail(){
        return $this->hasMany(NotificationGroupDetail::class, "notification_group_id", "id");
    }
}

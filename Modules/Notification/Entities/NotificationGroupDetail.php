<?php

namespace Modules\Notification\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Notification\Entities\NotificationGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationGroupDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $guarded = [];
    protected $casts = [
        'options' => 'json',
    ];

    protected static function newFactory()
    {
        return \Modules\Notification\Database\factories\NotificationGroupDetailFactory::new ();
    }

    public function notificationGroup()
    {
        return $this->belongsTo(NotificationGroup::class, "notification_group_id", "id");
    }
}

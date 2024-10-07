<?php

namespace Modules\Notification\Entities;

use App\Traits\ChildrenList;
use App\Traits\TimeSerilization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Modules\Event\Entities\Event;

class NotificationMarketingGroup extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;
    use ChildrenList;

    public $incrementing = false;
    protected $guarded = [];
    protected $casts = [
        'id' => 'string',
    ];

    protected static function newFactory()
    {
        return \Modules\Notification\Database\factories\NotificationMarketingGroupFactory::new();
    }

    public function notification()
    {
        $child = $this->getChildrenAplikator(Auth::user()->personel_id);
        return $this->hasMany(Notification::class, "notification_marketing_group_id", "id")
            // ->where("notifiable_id", Auth::id())
            ->where(function ($query) use ($child) {
                return $query
                    ->where("notifiable_id", Auth::id())
                    ->orWhereIn("personel_id", $child)
                    ->where("personel_id", "!=", Auth::user()->personel_id)
                    ->where("notification_marketing_group_id", "3");
            })
            ->where("as_marketing", 1)
            ->consideredNotification();
    }

    public function notificationSupervisor()
    {
        return $this->hasMany(Notification::class, 'notification_marketing_group_id', 'id')
            ->consideredNotification()
            
            ->where("notifiable_id", auth()->id());
    }
}

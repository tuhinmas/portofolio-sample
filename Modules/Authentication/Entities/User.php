<?php

namespace Modules\Authentication\Entities;

use App\Models\UserDevice;
use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Authentication\Entities\Device;
use Modules\ForeCast\Entities\ForeCast;
use Modules\KiosDealer\Entities\Store;
use Modules\Notification\Entities\Notification;
use Modules\Personel\Entities\Personel;
use Modules\PlantingCalendar\Entities\PlantingCalendar;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use Uuids;
    use HasRoles;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guard_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'personel_id',
        'username',
    ];
    // protected $connection = 'mysql';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        "id" => "string",
        "password_change_at" => "datetime:Y-m-d H:i:s"
    ];
    // Rest omitted for brevity

    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\UserFactory::new();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function profile()
    {
        return $this->hasOne(Personel::class, 'id', 'personel_id')
            ->with([
                'position',
                'supervisor',
                'districtHasOne' => function ($Q) {
                    return $Q->with([
                        "district",
                        "city",
                        "province",
                        "subRegionWithRegion" => function ($Q) {
                            return $Q->with([
                                "region",
                            ]);
                        },
                    ]);
                },
                'subRegion',
                'region' => function ($Q) {
                    return $Q->with([
                        "provinceRegion",
                    ]);
                },
                'personelBranch.region',
            ]);
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, 'id', 'personel_id')->with('address', 'contact');
    }
   
    public function personelOnly()
    {
        return $this->hasOne(Personel::class, 'id', 'personel_id');
    }

    public function notification()
    {
        return $this->hasMany(Notification::class, 'notifiable_id', 'id')
            ->where(function ($query) {
                return $query->eventConditional();
            })
            ->whereNotNull('expired_at')
            ->where("expired_at", ">=", Carbon::now()->format("Y-m-d"))
            ->where("as_marketing", 1)
            ->where(function ($query) {
                return $query
                    ->where("read_at", ">=", Carbon::now()->format("Y-m-d H:i:s"))
                    ->orWhereNull("read_at");
            });
    }

    public function notificationSupervisor()
    {
        return $this->hasMany(Notification::class, 'personel_id', 'personel_id')
            ->consideredNotification()
            ->where(function ($query) {
                return $query->eventConditional();
            })
            ->where("notifiable_id", auth()->id());
    }

    public function notificationHasOne($data_id = null)
    {
        return $this->hasOne(Notification::class, 'notifiable_id', 'id')
            ->whereNull("read_at")
            ->orderBy("created_at", "desc");
    }

    public function isAdministartor()
    {
        $user = $this->hasRole('super-admin');
        if ($user) {
            return true;
        }
    }

    public function getIsActiveMarketingAttribute()
    {
        if ($this->hasRole(['Marketing District Manager (MDM)', 'Assistant MDM', 'Regional Marketing (RM)', 'Regional Marketing Coordinator (RMC)'])) {
            return env('IS_ACTIVE_MARKETING', false);
        }
        return false;
    }

    public function getRequirementStoreAttribute()
    {
        return [
            "Regional Marketing (RM)" => env("RM_STORE_REQUIRED", 0),
            "Regional Marketing Coordinator (RMC)" => env("RMC_STORE_REQUIRED", 0),
            "Marketing District Manager (MDM)" => env("MDM_STORE_REQUIRED", 0),
            "Assistant MDM" => env("ADM_STORE_REQUIRED", 0),
            "Aplikator" => env("APPLICATOR_STORE_REQUIRED", 0),
        ];
    }

    public function hasStore()
    {
        return $this->hasMany(Store::class, 'personel_id', 'personel_id');
    }

    public function planCalendar()
    {
        return $this->hasMany(PlantingCalendar::class, 'user_id', 'id');
    }

    public function foreCast()
    {
        return $this->hasMany(ForeCast::class, 'personel_id', 'personel_id');
    }

    public function lastLoginDevice()
    {
        return $this->hasOne(Device::class, "user_id", "id")
            ->orderBy("devices.created_at", "desc");
    }

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class, 'user_id', 'id');
    }
    
    public function userAccessHistory()
    {
        return $this->hasMany(UserAccessHistory::class, 'user_id', 'id');
    }
}

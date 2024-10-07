<?php

namespace App\Models;

use App\Traits\Uuids;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Modules\Personel\Entities\Personel;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Invoice\Entities\Invoice;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;
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
        'username'
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
    ];
     // Rest omitted for brevity

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
        return $this->hasOne(Personel::class,'user_id','id')->with('address','contact');
    }

    public function invoice(){
        return $this->hasMany(Invoice::class,'user_id','id');
    }
    
    public function personel()
    {
        return $this->hasOne(Personel::class, 'id', 'personel_id');
    }
}

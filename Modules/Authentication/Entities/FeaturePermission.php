<?php

namespace Modules\Authentication\Entities;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturePermission extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\FeaturePermissionFactory::new ();
    }

    public function permission()
    {
        return $this->hasOne(Permission::class, "id", "permission_id");
    }
}

<?php

namespace App\Models;

use App\Traits\Uuids;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as spatiePermission;

class Permission extends spatiePermission
{
    use HasFactory;
    use Uuids;
    use HasFactory;

    protected static function newFactory()
    {
        return PermissionFactory::new();
    }

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

}

<?php

namespace App\Models;

use App\Traits\Uuids;
use Spatie\Permission\Models\Role as spatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends spatieRole  
{
    use HasFactory;
    use Uuids;

    public $incrementing = false;
}

<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Http\Requests\Request;

class FeeFollowUp extends Model
{
    use HasFactory, Uuids, SoftDeletes;

    protected $guarded = [];
    protected $casts = [
        "id" => "string",
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\FeeFollowUpFactory::new ();
    }
}

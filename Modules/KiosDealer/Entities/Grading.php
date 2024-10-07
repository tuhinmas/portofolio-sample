<?php

namespace Modules\KiosDealer\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Grading extends Model
{
    use HasFactory;
    public $incrementing = false;

    protected $fillable = [];
    
    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\GradingFactory::new();
    }
}

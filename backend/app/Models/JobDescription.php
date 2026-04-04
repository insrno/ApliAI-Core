<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobDescription extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'company',
        'description',
    ];
}

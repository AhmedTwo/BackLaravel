<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employment_type extends Model
{
    protected $table = 'employment_type';

    protected $fillable = [
        'id',
        'name',
        'created_at',
        'updated_at',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $fillable = ['name', 'condition', 'threshold', 'action', 'is_active'];
}

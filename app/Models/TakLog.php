<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TakLog extends Model
{
    protected $fillable = [
        'device_id',
        'target',
        'cot_xml',
        'created_at',
    ];
}

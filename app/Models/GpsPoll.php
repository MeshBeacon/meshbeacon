<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GpsPoll extends Model
{
    protected $fillable = ['duck_id', 'enabled', 'next_run_at'];

    protected $casts = [
        'enabled'    => 'boolean',
        'next_run_at' => 'datetime',
    ];

    /**
     * Return all polls that are enabled and due to run.
     */
    public function scopeDue($query)
    {
        return $query->where('enabled', true)
                     ->where('next_run_at', '<=', Carbon::now());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentLog extends Model
{
    protected $fillable = [
        'cluster_data_id',
        'duck_id',
        'message_id',
        'retransmission_count',
        'status',
        'notes',
        'assigned_to',
        'assigned_at',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'retransmission_count' => 'integer',
        'assigned_at'          => 'datetime',
        'acknowledged_at'      => 'datetime',
        'resolved_at'          => 'datetime',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

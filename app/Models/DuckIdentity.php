<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TOFU (trust-on-first-use) record of a Duck's long-term X25519 static
 * public key, keyed by its DUID (`duck_id`). Distinct from ClusterData
 * (which stores individual messages) — one row per physical device.
 */
class DuckIdentity extends Model
{
    protected $fillable = ['duck_id', 'public_key', 'first_seen_at', 'last_uplink_counter'];

    protected $casts = [
        'first_seen_at' => 'datetime',
    ];
}

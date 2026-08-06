<?php

namespace App\Models;

use App\Enums\Urgency;
use Illuminate\Database\Eloquent\Model;

class ClusterData extends Model
{
    protected $fillable = ['duck_id', 'topic', 'message_id', 'payload', 'path', 'origin', 'destination', 'hops', 'duck_type', 'synced', 'synced_at'];

    protected $casts = [
        'synced'    => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Parse LAT/LNG from the payload and return a Google Maps URL, or null
     * if no coordinates are present.
     */
    /**
     * Extract the TEXT: value from a MSG payload, or return the raw payload.
     * e.g. "MSG,URGENCY:low,LAT:6.1,LNG:102.2,TEXT:Lalala" → "Lalala"
     */
    public function getDisplayTextAttribute(): ?string
    {
        if (!$this->payload) {
            return null;
        }

        if (preg_match('/TEXT:([^,\n]+)/i', $this->payload, $matches)) {
            return trim($matches[1]);
        }

        return $this->payload;
    }

    public function getMapUrlAttribute(): ?string
    {
        if (!$this->payload) {
            return null;
        }

        if (preg_match('/LAT:(-?\d+(?:\.\d+)?),LNG:(-?\d+(?:\.\d+)?)/', $this->payload, $matches)) {
            return 'https://www.google.com/maps?q=' . $matches[1] . ',' . $matches[2];
        }

        return null;
    }

    public function getMapEmbedUrlAttribute(): ?string
    {
        if (!$this->payload) {
            return null;
        }

        if (preg_match('/LAT:(-?\d+(?:\.\d+)?),LNG:(-?\d+(?:\.\d+)?)/', $this->payload, $matches)) {
            return 'https://maps.google.com/maps?q=' . $matches[1] . ',' . $matches[2] . '&z=15&output=embed';
        }

        return null;
    }

    /**
     * Returns true when the payload is an SOS triggered from a mobile phone
     * (i.e. contains SOS but NOT SRC:DEVICE).
     */
    public function getSosFromMobileAttribute(): bool
    {
        if (!$this->payload) {
            return false;
        }

        return (bool) preg_match('/\bSOS\b/i', $this->payload)
            && !preg_match('/\bSRC:DEVICE\b/i', $this->payload);
    }

    /**
     * Returns true when the payload is an SOS triggered by a hardware button press.
     * e.g. "SOS,SRC:DEVICE,..."
     */
    public function getSosFromDeviceAttribute(): bool
    {
        if (!$this->payload) {
            return false;
        }

        return (bool) preg_match('/\bSOS\b.*\bSRC:DEVICE\b/i', $this->payload);
    }

    /**
     * Returns true when the payload is a "Roger" confirmation sent by triple-clicking
     * the physical button on the device (MSG,SRC:DEVICE,TEXT:Roger).
     */
    public function getRogerFromDeviceAttribute(): bool
    {
        if (!$this->payload) {
            return false;
        }

        return (bool) preg_match('/\bSRC:DEVICE\b/i', $this->payload)
            && (bool) preg_match('/\bTEXT:Roger\b/i', $this->payload);
    }

    /**
     * Returns true when the payload contains LAT:none or LNG:none,
     * indicating the sender had no GPS fix.
     */
    public function getGpsUnavailableAttribute(): bool
    {
        if (!$this->payload) {
            return false;
        }

        return (bool) preg_match('/LAT:none|LNG:none/i', $this->payload);
    }

    /**
     * Returns true when the payload is a device SOS (SRC:DEVICE present) but
     * contains no GPS data at all — indicating old hardware with no GPS unit.
     * Distinct from gps_unavailable (LAT:none) which means the hardware tried
     * but couldn't get a satellite fix.
     */
    public function getGpsHardwareAbsentAttribute(): bool
    {
        if (!$this->payload) {
            return false;
        }

        return $this->sos_from_device
            && $this->map_url === null
            && !$this->gps_unavailable;
    }

    /**
     * Parse the URGENT:<int> field from the payload and return a Urgency enum.
     * e.g. "URGENT:0" → Urgency::Low, "URGENT:1" → Urgency::Medium, "URGENT:2" → Urgency::Critical
     */
    public function getUrgencyAttribute(): ?Urgency
    {
        if (!$this->payload) {
            return null;
        }

        if (preg_match('/URGENCY:(\d+)/i', $this->payload, $matches)) {
            return Urgency::tryFrom((int) $matches[1]);
        }

        return null;
    }

    /**
     * Returns true when the GPS coordinates in this record came from the companion
     * phone app rather than a hardware GPS module.
     *
     * Three cases:
     *  1. GPS-topic record: firmware received CDK:GPSREQ reply tagged SRC:PHONE.
     *  2. Soft SOS (no SRC:DEVICE): the phone sent the SOS frame — its own GPS is
     *     embedded, so the source is always the phone.
     *  3. Hardware SOS with phone GPS fallback: firmware adds GPS:PHONE when it
     *     used cached phone coordinates instead of the on-board GPS module.
     */
    public function getGpsFromPhoneAttribute(): bool
    {
        if (!$this->payload) return false;
        // GPS-topic record: explicit SRC:PHONE marker
        if (preg_match('/\bSRC:PHONE\b/i', $this->payload)) return true;
        // Hardware SOS phone-GPS fallback: explicit GPS:PHONE marker
        if (preg_match('/\bGPS:PHONE\b/i', $this->payload)) return true;
        // Soft SOS (phone-originated): GPS is always from the phone
        if ($this->sos_from_mobile && $this->map_url !== null) return true;
        return false;
    }

    /**
     * Returns true when the GPS payload reports no fix (FIX:0).
     */
    public function getGpsFixZeroAttribute(): bool
    {
        return (bool) preg_match('/\bFIX:0\b/i', $this->payload ?? '');
    }

    /**
     * Returns true when GPS is unavailable because no phone was connected
     * (firmware sent GPS,FIX:0,SRC:NONE,REASON:NO_PHONE or NO_RESPONSE).
     */
    public function getGpsNoPhoneAttribute(): bool
    {
        return (bool) preg_match('/\bREASON:NO_PHONE\b|\bREASON:NO_RESPONSE\b/i', $this->payload ?? '');
    }

    /**
     * Badge label for display — finer-grained than gps_source_label.
     * Returns "No Phone" when the device is healthy but has no phone attached,
     * keeping "No Fix" only for genuine signal-related failures.
     */
    public function getGpsBadgeLabelAttribute(): string
    {
        if ($this->gps_fix_zero && $this->gps_no_phone) return 'No Phone';
        if ($this->gps_fix_zero)   return 'No Fix';
        if ($this->gps_from_phone) return 'Phone';
        return 'Satellite';
    }

    /**
     * Number of visible satellites reported by the hardware GPS module.
     * Null when not present (phone GPS or no-fix payloads).
     */
    public function getGpsSatsAttribute(): ?int
    {
        if (preg_match('/SATS:(\d+)/i', $this->payload ?? '', $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Human-readable GPS source label for display.
     * Priority: no-fix check first, then phone, then satellite.
     */
    public function getGpsSourceLabelAttribute(): string
    {
        if ($this->gps_fix_zero)   return 'No Fix';
        if ($this->gps_from_phone) return 'Phone';
        return 'Satellite';
    }

    /**
     * Latitude string extracted from the payload, or null when absent.
     * Uses the same regex as map_url but returns the raw string.
     */
    public function getGpsLatAttribute(): ?string
    {
        if (preg_match('/LAT:(-?\d+(?:\.\d+)?)/', $this->payload ?? '', $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Longitude string extracted from the payload, or null when absent.
     */
    public function getGpsLngAttribute(): ?string
    {
        if (preg_match('/LNG:(-?\d+(?:\.\d+)?)/', $this->payload ?? '', $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Battery percentage reported by the device (BATT:<n> field), or null when absent.
     */
    public function getGpsBattAttribute(): ?int
    {
        if (preg_match('/BATT:(\d+)/i', $this->payload ?? '', $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Altitude in metres (ALT:<n> field), or null when absent.
     */
    public function getGpsAltAttribute(): ?float
    {
        if (preg_match('/ALT:(-?\d+(?:\.\d+)?)/i', $this->payload ?? '', $m)) {
            return (float) $m[1];
        }
        return null;
    }

    /**
     * Speed in km/h (SPD:<n> field), or null when absent.
     */
    public function getGpsSpdAttribute(): ?float
    {
        if (preg_match('/SPD:(-?\d+(?:\.\d+)?)/i', $this->payload ?? '', $m)) {
            return (float) $m[1];
        }
        return null;
    }

    /**
     * Course heading in degrees (HDG:<n> field), or null when absent.
     */
    public function getGpsHdgAttribute(): ?float
    {
        if (preg_match('/HDG:(-?\d+(?:\.\d+)?)/i', $this->payload ?? '', $m)) {
            return (float) $m[1];
        }
        return null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ---------------------------
// Obszar działania agenta: punkt na mapie + promień w km. Agent może mieć ich wiele.
// Nowe ogłoszenie "Zaginął" w obszarze = prośba o pomoc (App\Services\AgentNotifier).
// ---------------------------

class AgentArea extends Model
{
    protected $fillable = ['user_id', 'name', 'latitude', 'longitude', 'radius_km'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_km' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ** Odległość od środka obszaru po kole wielkim (haversine), km
    public function distanceTo(float $lat, float $lng): float
    {
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return 6371.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function contains(float $lat, float $lng): bool
    {
        return $this->distanceTo($lat, $lng) <= $this->radius_km;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ---------------------------
// Wysłana prośba o pomoc: agent + ogłoszenie (App\Services\AgentNotifier).
// Pilnuje, żeby ta sama para nie dostała maila drugi raz.
// ---------------------------

class AgentNotification extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'animal_id', 'distance_km'];
}

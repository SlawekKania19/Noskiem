<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Zlecenia "poszukaj podobnych i napisz do autora" - jedno na nowe zgłoszenie.
// Powstaje po potwierdzeniu e-maila (AnimalEditController::confirmEmail), realizuje je
// php artisan match:notify, gdy wszystkie zdjęcia zgłoszenia mają już wektory.
// Statusy: queued (czeka) | notified (wysłano mail) | no_matches (nic ponad próg) |
// no_photos | no_embedding | skipped (np. zgłoszenie odrzucone) | expired | failed
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_edit_id')->unique()->constrained('animal_edits')->cascadeOnDelete();
            $table->string('status', 20)->default('queued')->index();
            // Liczba nieudanych prób (błąd Pythona / wysyłki) - po limicie status "failed"
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            // Ile wyników poszło w mailu (0 = brak dopasowań)
            $table->unsignedSmallInteger('results_shown')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_requests');
    }
};

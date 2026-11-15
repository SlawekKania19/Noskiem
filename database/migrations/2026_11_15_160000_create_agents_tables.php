<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Agenci noskiem: konto (tabela users + flaga is_agent), obszary działania (punkt + promień),
// kliknięcia "Szukam" pod ogłoszeniami i dziennik wysłanych próśb o pomoc.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_agent')->default(false)->index();
            // Miejscowość agenta (z rejestracji)
            $table->foreignId('voivodeship_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            // Agent może wyłączyć maile, nie usuwając konta
            $table->boolean('agent_notify')->default(true);
            // Blokada przez admina (zakładka "Agenci")
            $table->timestamp('agent_blocked_at')->nullable();
        });

        Schema::create('agent_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Własna nazwa obszaru, np. "Dom", "Praca"
            $table->string('name', 60)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('radius_km');
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });

        // ** Agent kliknął "Szukam" pod ogłoszeniem
        Schema::create('agent_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'animal_id']);
        });

        // ** Wysłane prośby o pomoc - para agent + ogłoszenie najwyżej raz
        Schema::create('agent_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->decimal('distance_km', 6, 1)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'animal_id']);
        });

        // ** Znacznik "to ogłoszenie przeszło już przez agents:notify"
        Schema::table('animals', function (Blueprint $table) {
            $table->timestamp('agent_notified_at')->nullable()->index();
        });

        // ** Istniejące ogłoszenia oznaczamy jako obsłużone - o starych nie piszemy
        DB::table('animals')->update(['agent_notified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('agent_notified_at');
        });

        Schema::dropIfExists('agent_notifications');
        Schema::dropIfExists('agent_searches');
        Schema::dropIfExists('agent_areas');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voivodeship_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn(['is_agent', 'agent_notify', 'agent_blocked_at']);
        });
    }
};

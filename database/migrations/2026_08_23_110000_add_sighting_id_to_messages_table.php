<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Wiadomość może dotyczyć konkretnego zgłoszenia "też widziałem" pod ogłoszeniem
// (przycisk "Kontakt z autorem" w timeline), nie tylko samego ogłoszenia —
// wtedy trafia do autora zgłoszenia, a nie do autora ogłoszenia.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sighting_id')->nullable()->after('animal_id');
            $table->foreign('sighting_id')->references('id')->on('sightings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sighting_id']);
            $table->dropColumn('sighting_id');
        });
    }
};

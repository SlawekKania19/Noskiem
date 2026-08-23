<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Zdjęcia i logi moderacji mogą teraz należeć też do zgłoszenia "widziałem"
// (Sighting), tak samo jak wcześniej do Animal/AnimalEdit.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedBigInteger('sighting_id')->nullable()->after('animal_edit_id');
            $table->foreign('sighting_id')->references('id')->on('sightings')->onDelete('cascade');
        });

        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('sighting_id')->nullable()->after('animal_edit_id');
            $table->foreign('sighting_id')->references('id')->on('sightings')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropForeign(['sighting_id']);
            $table->dropColumn('sighting_id');
        });

        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropForeign(['sighting_id']);
            $table->dropColumn('sighting_id');
        });
    }
};

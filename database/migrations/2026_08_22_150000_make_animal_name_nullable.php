<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Pole animal_name jest opcjonalne dla statusu "found" (walidacja: required_if:status,lost),
// ale w bazie było NOT NULL — realny błąd 500 dla każdego zgłoszenia "Znaleziony" bez imienia.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('animal_name')->nullable()->change();
        });

        Schema::table('animal_edits', function (Blueprint $table) {
            $table->string('animal_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('animal_name')->nullable(false)->change();
        });

        Schema::table('animal_edits', function (Blueprint $table) {
            $table->string('animal_name')->nullable(false)->change();
        });
    }
};

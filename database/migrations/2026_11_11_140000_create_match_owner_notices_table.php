<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Mail do autora STAREGO ogłoszenia, gdy nowe ogłoszenie jest do niego podobne
// (App\Services\MatchOwnerNotifier). Tabela pilnuje limitów: jedna para ogłoszeń =
// najwyżej jeden mail, a jedno ogłoszenie dostaje najwyżej jeden mail na kilka dni.
// Jak pozostałe logi dopasowań - BEZ kluczy obcych do animals (ogłoszenia są usuwane).
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_owner_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_search_id')->nullable()->constrained('match_searches')->nullOnDelete();
            // Adresat - stare ogłoszenie (kandydat z wyszukiwania)
            $table->unsignedBigInteger('animal_id')->index();
            // Nowe ogłoszenie, o którym piszemy
            $table->unsignedBigInteger('query_animal_id');
            // strong | possible
            $table->string('level', 10);
            // sent - mail wysłany; cooldown - pominięty, bo niedawno był już mail do tego ogłoszenia
            $table->string('status', 20);
            $table->timestamp('created_at')->nullable();

            $table->unique(['animal_id', 'query_animal_id']);
        });

        // ** Autor wypisał się z tych maili (link w mailu) - dotyczy jednego ogłoszenia
        Schema::table('animals', function (Blueprint $table) {
            $table->timestamp('match_notify_opt_out_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('match_notify_opt_out_at');
        });

        Schema::dropIfExists('match_owner_notices');
    }
};

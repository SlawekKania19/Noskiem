<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();

            // Status moderacji
            $table->enum('mod_status', ['pending', 'approved', 'rejected'])
                  ->default('pending');

            // Status zgłoszenia
            $table->enum('status', ['lost', 'found'])
                  ->default('lost');

            // Podstawowe dane
            $table->string('title');
            $table->text('description');
            $table->string('animal_name');
            $table->text('ident_marks')->nullable();
            $table->boolean('chip_present')->default(false);
            $table->string('chip_number')->nullable();

            // Gatunek i rasa
            $table->unsignedBigInteger('species_id');
            $table->unsignedBigInteger('breed_id');

            // Data zdarzenia
            $table->date('date_event');

            // Lokalizacja
            $table->unsignedBigInteger('voivodship_id');
            $table->unsignedBigInteger('city_id');
            $table->string('location_text');

            // Współrzędne
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Kontakt
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();

            // Token do edycji bez logowania
            $table->string('edit_token')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};

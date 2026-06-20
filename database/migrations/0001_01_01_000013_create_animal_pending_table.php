<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animal_edits', function (Blueprint $table) {
            $table->id();

            // reference to original animal (if this edit is for an existing animal)
            $table->unsignedBigInteger('animal_id')->nullable();

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

            // foreign keys
            $table->foreign('animal_id')
                ->references('id')
                ->on('animals')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animal_edits');
    }
};

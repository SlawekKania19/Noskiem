<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wykonaj migrację.
     */
    public function up(): void
    {
        Schema::create('sightings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('animal_id')->nullable();
            $table->text('description')->nullable();
            $table->text('special_marks')->nullable();
            $table->date('date_seen')->nullable();
            $table->unsignedBigInteger('species_id')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamps();

            $table->foreign('animal_id')
                ->references('id')
                ->on('animals')
                ->onDelete('cascade');
            $table->foreign('species_id')
                ->references('id')
                ->on('species')
                ->onDelete('set null');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::dropIfExists('sightings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();

            // Zdjęcie może należeć do opublikowanego ogłoszenia
            $table->unsignedBigInteger('animal_id')->nullable();

            // ...lub do pending edycji
            $table->unsignedBigInteger('animal_edit_id')->nullable();

            $table->string('path');
            $table->boolean('is_main')->default(false);
            $table->timestamps();

            // FK do animals
            $table->foreign('animal_id')
                ->references('id')
                ->on('animals')
                ->onDelete('cascade');

            // FK do animal_edits
            $table->foreign('animal_edit_id')
                ->references('id')
                ->on('animal_edits')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};

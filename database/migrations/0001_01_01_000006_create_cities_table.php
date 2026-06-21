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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();

            // Powiązanie z województwem
            $table->unsignedBigInteger('voivodship_id');

            // Nazwa miasta
            $table->string('name_pl'); // np. Kraków
            $table->string('name_en')->nullable(); // np. Cracow

            $table->timestamps();

            // Klucz obcy
            $table->foreign('voivodship_id')
                ->references('id')
                ->on('voivodships')
                ->onDelete('restrict');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};

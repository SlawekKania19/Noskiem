<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();

            // powiązanie z gatunkiem
            $table->unsignedBigInteger('species_id');

            // nazwy rasy
            $table->string('breed_pl'); // np. Owczarek niemiecki
            $table->string('breed_en')->nullable(); // na przyszłość
            $table->unsignedBigInteger('species_id');

            $table->timestamps();

            // klucz obcy (możesz dodać teraz lub później)
            // $table->foreign('species_id')->references('id')->on('species');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeds');
    }
};

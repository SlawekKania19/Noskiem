<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voivodships', function (Blueprint $table) {
            $table->id();

            // Nazwa województwa
            $table->string('name_pl'); // np. Śląskie
            $table->string('name_en')->nullable(); // w języku angielskim, np. Silesian, opcjonalnie

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voivodships');
    }
};

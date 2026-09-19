<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------------------------
    // Partnerzy wspierający serwis — pasek z logotypami nad stopką.
    // Zarządzani z panelu (App\Filament\Resources\PartnerResource).
    // ---------------------------

    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // ** Logo i link są opcjonalne — bez logo pasek pokazuje samą nazwę partnera,
            // bez linku baner nie jest klikalny
            $table->string('logo_path')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            // Pasek pobiera tylko aktywnych, w zadanej kolejności
            $table->index(['is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};

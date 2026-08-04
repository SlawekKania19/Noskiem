<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------------------------
    // Tabela statycznych podstron (np. "cookies", w przyszłości "regulamin"),
    // edytowalnych z panelu Filament (App\Filament\Resources\PageResource).
    // Slug jest ustalany tylko przy tworzeniu — patrz komentarz w modelu Page.
    // ---------------------------

    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};

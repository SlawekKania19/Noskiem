<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------------------------
    // Kategorie wpisów bloga (np. "Porady", "Zdrowie"). Zarządzane z panelu
    // Filament (App\Filament\Resources\BlogCategoryResource). Slug ustalany przy
    // tworzeniu — patrz komentarz w modelu BlogCategory.
    // ---------------------------

    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_categories');
    }
};

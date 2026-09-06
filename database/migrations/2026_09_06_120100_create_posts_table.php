<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------------------------
    // Wpisy bloga — artykuły/porady dla właścicieli zwierząt. Treść (`body`) to
    // HTML z edytora WYSIWYG (Filament RichEditor). Widoczne publicznie dopiero
    // gdy status = "published" ORAZ published_at już minęło (scope Post::published).
    // ---------------------------

    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // ** Autor — rola "is_author" na users; nullOnDelete, żeby usunięcie
            // konta nie kasowało wpisów
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // ** Jedna kategoria na wpis (tagi wielokrotne — na później)
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 320)->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_path')->nullable();

            $table->string('status')->default('draft');   // draft | published
            $table->timestamp('published_at')->nullable();

            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->timestamps();

            // ** Lista publiczna filtruje zawsze po tych dwóch polach
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

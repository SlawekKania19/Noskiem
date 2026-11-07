<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Wektory zdjęć do wyszukiwania podobnych zwierząt (moduł noskiem_match/).
// Klucz to zdjęcie + wersja modelu, NIE ogłoszenie — photos.id nie zmienia się przy
// zatwierdzeniu (zdjęcie jest tylko przepinane z animal_edit_id na animal_id), więc
// przynależność do ogłoszenia czytamy zawsze aktualną z tabeli photos.
// Usunięcie zdjęcia (także kaskadą z ogłoszenia) usuwa jego wektory.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_id')->constrained('photos')->cascadeOnDelete();
            // Np. "dinov2-base+detr-crop+cls-v1" — wektory różnych wersji nie są porównywalne
            $table->string('model_version', 64);
            // ** float32 little-endian, znormalizowany L2 (768 × 4 B = 3 KB dla dinov2-base).
            // Null = nie udało się policzyć (uszkodzony plik itp.), powód w "error"
            $table->binary('vector')->nullable();
            $table->unsignedSmallInteger('dim')->nullable();
            // Czy detektor znalazł zwierzę (false = liczone z całego zdjęcia)
            $table->boolean('crop_found')->nullable();
            $table->string('error', 500)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->unique(['photo_id', 'model_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_embeddings');
    }
};

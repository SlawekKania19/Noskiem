<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Kadrowanie miniaturek zdjęć (żeby kwadratowa miniaturka nie ucinała głowy zwierzaka).
// - width/height  - wymiary zapisanego pliku, potrzebne do przeliczenia kadru na object-position
// - focus         - JSON [x1, y1, x2, y2] w ułamkach 0..1 szer./wys. zdjęcia: ramka zwierzęcia
//                   z detekcji (noskiem_match) - obszar, który ma się zmieścić w miniaturce
// - focus_source  - auto (zwierzę wykryte), none (detekcja nic nie znalazła),
//                   null = jeszcze nie sprawdzane (kandydat do php artisan photos:detect-focus)
// - focus_manual  - JSON [x, y] - punkt wskazany przez moderatora, ma pierwszeństwo przed
//                   detekcją; trzymany osobno, żeby dało się wrócić do kadru automatycznego
// Plik zdjęcia się nie zmienia - to tylko sposób wyświetlania.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->unsignedSmallInteger('width')->nullable()->after('path');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
            $table->json('focus')->nullable()->after('height');
            $table->string('focus_source', 10)->nullable()->after('focus');
            $table->json('focus_manual')->nullable()->after('focus_source');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'focus', 'focus_source', 'focus_manual']);
        });
    }
};

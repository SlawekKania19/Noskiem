<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------------------------
    // Ręczna kolejność gatunków w listach/dropdownach (zamiast sortowania alfabetycznego)
    // ---------------------------

    public function up(): void
    {
        Schema::table('species', function (Blueprint $table) {
            $table->unsignedInteger('sortkey')->default(0)->after('name_en');
        });

        // Domyślnie sortkey = id, żeby kolejność na starcie odpowiadała dotychczasowej (wg id)
        DB::statement('UPDATE species SET sortkey = id');
    }

    public function down(): void
    {
        Schema::table('species', function (Blueprint $table) {
            $table->dropColumn('sortkey');
        });
    }
};

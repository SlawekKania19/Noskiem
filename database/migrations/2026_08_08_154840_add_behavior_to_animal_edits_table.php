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
        Schema::table('animal_edits', function (Blueprint $table) {
            $table->text('behavior')->nullable()->after('ident_marks');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('animal_edits', function (Blueprint $table) {
            $table->dropColumn('behavior');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wykonaj migrację.
     *
     * animal_edits.edit_token był unikalny, ale AnimalEditController@update celowo
     * zapisuje ten sam token zwierzęcia (animals.edit_token — tam unikalność jest
     * poprawna) przy każdej kolejnej edycji. Druga edycja tego samego ogłoszenia,
     * zanim pierwsza zostanie zmoderowana, wywoływała naruszenie unikalności.
     */
    public function up(): void
    {
        Schema::table('animal_edits', function (Blueprint $table) {
            $table->dropUnique(['edit_token']);
            $table->index('edit_token');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('animal_edits', function (Blueprint $table) {
            $table->dropIndex(['edit_token']);
            $table->unique('edit_token');
        });
    }
};

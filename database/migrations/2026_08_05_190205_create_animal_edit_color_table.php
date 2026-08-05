<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Pivot kolorów dla zgłoszeń w trakcie moderacji (animal_edits) — analogiczny do animal_color,
// ale dla AnimalEdit. Bez niego formularz zgłoszenia nie ma jak zapisać wybranych kolorów,
// zanim zgłoszenie zostanie zatwierdzone i przeniesione do animals.
// ---------------------------

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_edit_color', function (Blueprint $table) {
            $table->unsignedBigInteger('animal_edit_id');
            $table->unsignedBigInteger('color_id');

            $table->primary(['animal_edit_id', 'color_id']);

            $table->timestamps();

            $table->foreign('animal_edit_id')
                ->references('id')
                ->on('animal_edits')
                ->onDelete('cascade');

            $table->foreign('color_id')
                ->references('id')
                ->on('colors')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_edit_color');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animal_color', function (Blueprint $table) {
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('color_id');

            $table->primary(['animal_id', 'color_id']);

            $table->foreign('animal_id')
                ->references('id')
                ->on('animals')
                ->onDelete('cascade');

            $table->foreign('color_id')
                ->references('id')
                ->on('colors')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animal_color');
    }
};

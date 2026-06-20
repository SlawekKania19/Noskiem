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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->timestamps();
            $table->foreign('animal_id')
                ->references('id')
                ->on('animals')
                ->onDelete('cascade');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

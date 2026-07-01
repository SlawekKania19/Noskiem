<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ** animal_id musi być nullable — nowe ogłoszenia odrzucone przed zatwierdzeniem nie mają jeszcze rekordu Animal
    public function up(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropForeign(['animal_id']);
            $table->unsignedBigInteger('animal_id')->nullable()->change();
            $table->foreign('animal_id')->references('id')->on('animals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropForeign(['animal_id']);
            $table->unsignedBigInteger('animal_id')->nullable(false)->change();
            $table->foreign('animal_id')->references('id')->on('animals')->onDelete('cascade');
        });
    }
};

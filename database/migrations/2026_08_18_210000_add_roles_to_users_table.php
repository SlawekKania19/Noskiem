<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wykonaj migrację.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->boolean('is_moderator')->default(false)->after('is_admin');
            $table->boolean('is_author')->default(false)->after('is_moderator');
        });

        // Do tej pory każdy zalogowany user miał pełny dostęp do panelu — żeby nikt
        // się nie zablokował przy wdrożeniu ról, istniejące konta zostają adminami.
        // Docelowe role dla nich Admin ustawi ręcznie w nowym ekranie użytkowników.
        DB::table('users')->update(['is_admin' => true]);
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'is_moderator', 'is_author']);
        });
    }
};

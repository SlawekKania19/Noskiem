<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Dodaje "resolved" do enuma mod_status na animals — status ustawiany bezpośrednio
// przez zgłaszającego (przycisk "Znaleziono zwierzaka" na stronie edycji, przez
// edit_token), bez moderacji. Na MySQL zmiana enuma surowym SQL (Schema...->change()
// bywa dla enumów MySQL zawodne). Na innych sterownikach (SQLite w testach) enum to
// i tak zwykły varchar z CHECK — przestawiamy go na luźny string.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE animals MODIFY mod_status ENUM('pending', 'approved', 'rejected', 'resolved') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('animals', function (Blueprint $table) {
            $table->string('mod_status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE animals MODIFY mod_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('animals', function (Blueprint $table) {
            $table->string('mod_status')->default('pending')->change();
        });
    }
};

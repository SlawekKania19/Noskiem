<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Dodaje "resolved" do enuma mod_status na animals — status ustawiany bezpośrednio
// przez zgłaszającego (przycisk "Znaleziono zwierzaka" na stronie edycji, przez
// edit_token), bez moderacji. Zmiana enuma robiona surowym SQL — modyfikacja
// enumów przez Schema::table()->change() bywa zawodna dla MySQL.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE animals MODIFY mod_status ENUM('pending', 'approved', 'rejected', 'resolved') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE animals MODIFY mod_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};

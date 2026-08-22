<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Potwierdzenie adresu e-mail zgłaszającego — dopiero po nim zgłoszenie trafia
// do moderatorów (ochrona przed botami/fałszywymi adresami przy nowych
// zgłoszeniach). Edycje istniejących, już zatwierdzonych ogłoszeń (animal_id
// != null) nie przechodzą przez tę bramkę — właściciel dowiódł już kontroli
// nad adresem wcześniej.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_edits', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('mod_status');
        });
    }

    public function down(): void
    {
        Schema::table('animal_edits', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};

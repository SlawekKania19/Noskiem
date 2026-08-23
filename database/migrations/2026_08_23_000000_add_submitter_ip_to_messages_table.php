<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Adres IP osoby wysyłającej wiadomość do autora ogłoszenia — na wypadek
// problemów prawnych. Widoczny wyłącznie w panelu admina (MessageResource —
// RestrictedToAdmin), nigdzie publicznie.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('submitter_ip', 45)->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('submitter_ip');
        });
    }
};

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
        Schema::table('animals', function (Blueprint $table) {
            // Od kiedy ogłoszenie uznajemy za przeterminowane (animals:retention).
            // Null = created_at + 2 lata; przedłużenie przez autora ustawia now + 6 miesięcy.
            $table->timestamp('retention_until')->nullable()->after('last_reminder_at');

            // Moment wysłania maila z ostrzeżeniem o usunięciu — od niego liczymy tydzień
            // do skasowania ogłoszenia. Przedłużenie czyści tę wartość.
            $table->timestamp('retention_notice_at')->nullable()->after('retention_until');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn(['retention_until', 'retention_notice_at']);
        });
    }
};

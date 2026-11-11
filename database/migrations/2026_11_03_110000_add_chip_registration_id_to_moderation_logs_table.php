<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Logi moderacji obejmują też bazę chipów (ChipModerationService) - tak samo
// jak sighting_id dla zgłoszeń "też widziałem"
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->foreignId('chip_registration_id')
                ->nullable()
                ->after('sighting_id')
                ->constrained('chip_registrations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chip_registration_id');
        });
    }
};

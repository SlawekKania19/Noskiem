<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Sightings dostają pełne flow moderacji, jak animal_edits: status moderacji,
// powód odrzucenia, token do linku potwierdzającego e-mail, znacznik potwierdzenia
// i adres IP zgłaszającego. Zatwierdzony sighting pojawia się jako wpis w
// "timeline" pod oryginalnym ogłoszeniem (Animal), nie jako osobne ogłoszenie.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sightings', function (Blueprint $table) {
            $table->enum('mod_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('animal_id');
            $table->text('mod_reject_reason')->nullable()->after('mod_status');
            $table->string('edit_token')->nullable()->after('mod_reject_reason');
            $table->timestamp('email_verified_at')->nullable()->after('edit_token');
            $table->string('submitter_ip', 45)->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('sightings', function (Blueprint $table) {
            $table->dropColumn([
                'mod_status',
                'mod_reject_reason',
                'edit_token',
                'email_verified_at',
                'submitter_ip',
            ]);
        });
    }
};

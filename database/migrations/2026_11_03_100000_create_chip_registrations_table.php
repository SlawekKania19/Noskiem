<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Bezpłatna baza chipów - rejestracja numeru chipa zwierzaka, niezależna od
// ogłoszeń. Flow jak przy sightings: potwierdzenie e-mail -> moderacja. Celowo
// bez imienia i nazwiska właściciela (minimum danych osobowych). Bez retencji.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chip_registrations', function (Blueprint $table) {
            $table->id();
            // ** Bez unique - duplikat numeru trafia do moderatora jako konflikt
            // (patrz ChipRegistration::hasChipConflict), żeby nikt nie "zajął" cudzego chipa
            $table->string('chip_number', 20)->index();
            $table->string('animal_name');
            $table->foreignId('species_id')->nullable()->constrained('species')->nullOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();
            $table->foreignId('voivodeship_id')->nullable()->constrained('voivodeships')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('contact_phone', 20);
            $table->string('contact_email');
            $table->text('additional_info')->nullable();
            // ** Ścieżka na dysku prywatnym ("local") - naklejka to dowód własności, bez publicznego URL
            $table->string('sticker_photo_path');

            $table->enum('mod_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('mod_reject_reason')->nullable();
            // ** Edycja zatwierdzonego wpisu czeka tu na ponowną moderację - do tego czasu
            // w wyszukiwarce widoczna jest dotychczasowa wersja
            $table->json('pending_changes')->nullable();
            $table->string('edit_token')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('submitter_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chip_registrations');
    }
};

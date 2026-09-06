<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------------------------
    // Profil autora bloga — pola widoczne na publicznej stronie /blog/autor/{slug}
    // oraz w wyróżnieniu autora pod wpisem. Wypełniane z panelu (UserResource),
    // wyłącznie dla kont z rolą is_author.
    // ---------------------------

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('is_author');
            $table->string('headline')->nullable()->after('slug');     // motto / rola pod nazwiskiem
            $table->text('bio')->nullable()->after('headline');        // "O autorze" (HTML z WYSIWYG)
            $table->text('signature')->nullable()->after('bio');       // stopka pod artykułami (HTML z WYSIWYG)
            $table->string('avatar_path')->nullable()->after('signature');

            $table->string('website_url')->nullable()->after('avatar_path');
            $table->string('facebook_url')->nullable()->after('website_url');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('tiktok_url')->nullable()->after('instagram_url');
            $table->string('x_url')->nullable()->after('tiktok_url');
            $table->string('youtube_url')->nullable()->after('x_url');
            $table->string('linkedin_url')->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'headline', 'bio', 'signature', 'avatar_path',
                'website_url', 'facebook_url', 'instagram_url', 'tiktok_url',
                'x_url', 'youtube_url', 'linkedin_url',
            ]);
        });
    }
};

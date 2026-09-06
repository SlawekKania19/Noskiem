<?php

namespace App\Filament\Support;

use App\Models\User;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

// ---------------------------
// Wspólna definicja sekcji „Profil autora (blog)". Używana w dwóch miejscach:
//  - UserResource (admin edytuje dowolne konto) — widoczność zależna od toggle is_author
//  - EditProfile (autor edytuje własny profil)  — widoczność: auth()->user()->is_author
// Pola muszą być w User::$fillable; slug dolicza się automatycznie w modelu.
// ---------------------------

class AuthorProfileFields
{
    /**
     * @param  Closure|bool  $visible  reguła widoczności sekcji
     */
    public static function section(Closure|bool $visible = true): Section
    {
        return Section::make('Profil autora (blog)')
            ->description('Dane widoczne publicznie — na stronie autora (/blog/autor/…) i pod jego wpisami.')
            ->visible($visible)
            ->columns(2)
            ->schema([
                TextInput::make('slug')
                    ->label('Adres strony autora (slug)')
                    ->helperText('Końcówka adresu: /blog/autor/TWÓJ-SLUG. Zostaw puste — wygenerujemy z imienia i nazwiska.')
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(User::class, 'slug', ignoreRecord: true),

                FileUpload::make('avatar_path')
                    ->label('Zdjęcie / awatar')
                    ->image()
                    ->imageEditor()
                    ->avatar()
                    ->disk('public')
                    ->directory('avatars')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->helperText('Kwadrat wygląda najlepiej. Maksymalnie 2 MB.'),

                TextInput::make('headline')
                    ->label('Motto / rola')
                    ->helperText('Jedna linijka pod nazwiskiem, np. „Behawiorystka, fundacja Cztery Łapy”.')
                    ->maxLength(255),

                RichEditor::make('bio')
                    ->label('O autorze')
                    ->helperText('Kilka zdań o Tobie i Twojej działalności.')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('blog/authors')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),

                RichEditor::make('signature')
                    ->label('Stopka autora')
                    ->helperText('Pokazujemy ją pod każdym Twoim artykułem i na dole Twojej strony autora — np. podziękowanie, zaproszenie do kontaktu, link do zbiórki.')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('blog/authors')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),

                TextInput::make('website_url')->label('Strona WWW')->url()->maxLength(255)->placeholder('https://…'),
                TextInput::make('facebook_url')->label('Facebook')->url()->maxLength(255)->placeholder('https://facebook.com/…'),
                TextInput::make('instagram_url')->label('Instagram')->url()->maxLength(255)->placeholder('https://instagram.com/…'),
                TextInput::make('tiktok_url')->label('TikTok')->url()->maxLength(255)->placeholder('https://tiktok.com/@…'),
                TextInput::make('x_url')->label('X (Twitter)')->url()->maxLength(255)->placeholder('https://x.com/…'),
                TextInput::make('youtube_url')->label('YouTube')->url()->maxLength(255)->placeholder('https://youtube.com/@…'),
                TextInput::make('linkedin_url')->label('LinkedIn')->url()->maxLength(255)->placeholder('https://linkedin.com/in/…'),

                // ** W UserResource $record = edytowane konto; na stronie „Edytuj profil"
                // $record nie jest wstrzykiwany — spadamy na zalogowanego użytkownika
                Placeholder::make('published_posts_count')
                    ->label('Opublikowane artykuły')
                    ->content(fn (?User $record) => (string) (($record ?? auth()->user())?->publishedPostsCount() ?? 0)),
            ]);
    }
}

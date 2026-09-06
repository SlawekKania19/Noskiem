<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'password', 'is_admin', 'is_moderator', 'is_author',
    // ** Profil autora bloga
    'slug', 'headline', 'bio', 'signature', 'avatar_path',
    'website_url', 'facebook_url', 'instagram_url', 'tiktok_url',
    'x_url', 'youtube_url', 'linkedin_url',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ** Kolejność platform w socialLinks() = kolejność ikon na stronie autora
    private const SOCIAL_FIELDS = [
        'website' => 'website_url',
        'facebook' => 'facebook_url',
        'instagram' => 'instagram_url',
        'tiktok' => 'tiktok_url',
        'x' => 'x_url',
        'youtube' => 'youtube_url',
        'linkedin' => 'linkedin_url',
    ];

    protected static function booted(): void
    {
        // ** Autor dostaje slug (do /blog/autor/{slug}) automatycznie z imienia i nazwiska,
        // jeśli nie ustawiono go ręcznie
        static::saving(function (User $user): void {
            if ($user->is_author && blank($user->slug)) {
                $user->slug = static::uniqueAuthorSlug($user->name, $user->getKey());
            }
        });
    }

    // Dostęp do panelu — dowolna z trzech ról.
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin || $this->is_moderator || $this->is_author;
    }

    // Wpisy bloga, których użytkownik jest autorem
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // ** Profil autora

    // Czy konto ma publiczną stronę autora (rola + slug)
    public function hasPublicAuthorProfile(): bool
    {
        return $this->is_author && filled($this->slug);
    }

    // Liczba opublikowanych artykułów tego autora
    public function publishedPostsCount(): int
    {
        return $this->posts()->published()->count();
    }

    // Uzupełnione linki społecznościowe jako [klucz => url], w ustalonej kolejności
    public function socialLinks(): array
    {
        $links = [];

        foreach (self::SOCIAL_FIELDS as $key => $column) {
            if (filled($this->{$column})) {
                $links[$key] = $this->{$column};
            }
        }

        return $links;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return filled($this->avatar_path)
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    // "O autorze" — HTML z edytora WYSIWYG, oddawany wprost (autorzy zaufani)
    public function getBioHtmlAttribute(): string
    {
        return (string) $this->bio;
    }

    // Stopka pokazywana pod artykułami i na stronie autora
    public function getSignatureHtmlAttribute(): string
    {
        return (string) $this->signature;
    }

    protected static function uniqueAuthorSlug(?string $name, ?int $ignoreId): string
    {
        $base = Str::slug((string) $name) ?: 'autor';
        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_moderator' => 'boolean',
            'is_author' => 'boolean',
        ];
    }
}

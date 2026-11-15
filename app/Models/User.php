<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'email', 'password', 'is_admin', 'is_moderator', 'is_author',
    // ** Profil autora bloga
    'slug', 'headline', 'bio', 'signature', 'avatar_path',
    'website_url', 'facebook_url', 'instagram_url', 'tiktok_url',
    'x_url', 'youtube_url', 'linkedin_url',
    // ** Agent noskiem (agent_blocked_at celowo poza fillable - ustawia tylko admin)
    'is_agent', 'voivodeship_id', 'city_id', 'agent_notify',
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

    // Dostęp do panelu - dowolna z trzech ról.
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin || $this->is_moderator || $this->is_author;
    }

    // ---------------------------
    // Agent noskiem - konto wolontariusza (rejestracja publiczna, bez dostępu do panelu).
    // Aktywny = potwierdził e-mail i nie jest zablokowany; tylko taki dostaje prośby
    // o pomoc i może klikać "Szukam" pod ogłoszeniami.
    // ---------------------------
    public function isActiveAgent(): bool
    {
        return $this->is_agent && $this->email_verified_at !== null && $this->agent_blocked_at === null;
    }

    public function scopeActiveAgents(Builder $query): Builder
    {
        return $query->where('is_agent', true)->whereNotNull('email_verified_at')->whereNull('agent_blocked_at');
    }

    // ** Konto wyłącznie agenta (bez ról w panelu) - takie można usunąć w całości
    public function isAgentOnly(): bool
    {
        return $this->is_agent && ! $this->is_admin && ! $this->is_moderator && ! $this->is_author;
    }

    public function agentAreas(): HasMany
    {
        return $this->hasMany(AgentArea::class);
    }

    // Wysłane prośby o pomoc (dziennik agents:notify)
    public function agentNotifications(): HasMany
    {
        return $this->hasMany(AgentNotification::class);
    }

    // ---------------------------
    // Usunięcie agenta (panel admina, rezygnacja z subskrypcji). Konto wyłącznie agenta
    // znika w całości (kaskady FK zabierają obszary, "Szukam" i dziennik); konto z rolą
    // w panelu zostaje i traci tylko dane agenta.
    // ---------------------------
    public function removeAgent(): void
    {
        if ($this->isAgentOnly()) {
            $this->delete();

            return;
        }

        $this->agentAreas()->delete();
        $this->searchedAnimals()->detach();
        $this->agentNotifications()->delete();
        $this->forceFill(['is_agent' => false, 'agent_notify' => true, 'agent_blocked_at' => null])->save();
    }

    // Ogłoszenia, pod którymi agent kliknął "Szukam"
    public function searchedAnimals(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'agent_searches')->withPivot('created_at');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function voivodeship(): BelongsTo
    {
        return $this->belongsTo(Voivodeship::class);
    }

    // ** Link potwierdzający e-mail agenta - podpisany, ważny 7 dni, działa bez logowania
    public function agentVerificationUrl(): string
    {
        return URL::temporarySignedRoute('agents.verify', now()->addDays(7), [
            'user' => $this->id,
            'hash' => sha1($this->email),
        ]);
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

    // "O autorze" - HTML z edytora WYSIWYG, oddawany wprost (autorzy zaufani)
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
            'is_agent' => 'boolean',
            'agent_notify' => 'boolean',
            'agent_blocked_at' => 'datetime',
        ];
    }
}

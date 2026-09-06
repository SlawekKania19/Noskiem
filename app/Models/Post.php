<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

// ---------------------------
// Wpis bloga. `body` to gotowy HTML z edytora WYSIWYG (Filament RichEditor) —
// autorzy to zaufany personel (rola "is_author"), więc renderujemy go wprost,
// tak samo jak treść stron statycznych (App\Models\Page).
// ---------------------------

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_path',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Route-model-binding po "slug"
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ** Relacje

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    // ** Zapytania

    // Tylko wpisy widoczne publicznie: opublikowane i z datą publikacji, która już minęła
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    // ** Akcesory

    // Treść jako HTML — RichEditor zapisuje już HTML, oddajemy bez zmian
    public function getBodyHtmlAttribute(): string
    {
        return (string) $this->body;
    }

    // Szacowany czas czytania w minutach (~200 słów/min, minimum 1)
    public function getReadingTimeAttribute(): int
    {
        $text = trim(strip_tags((string) $this->body));
        $words = $text === '' ? 0 : count(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY));

        return max(1, (int) ceil($words / 200));
    }

    // Adres URL okładki (albo null) — do <img> i og:image
    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path
            ? Storage::disk('public')->url($this->cover_path)
            : null;
    }
}

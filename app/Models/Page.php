<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// ---------------------------
// Statyczne podstrony (np. "cookies") — treść w Markdown, edytowalna z panelu
// Filament (App\Filament\Resources\PageResource). Slug ustalany tylko przy
// tworzeniu, żeby nie psuć linków do strony (np. z banera cookies).
// ---------------------------

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
    ];

    // Pozwala na route-model-binding po polu "slug" zamiast "id"
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Treść strony przekonwertowana z Markdown na bezpieczny HTML
    public function getContentHtmlAttribute(): string
    {
        return Str::markdown((string) $this->content);
    }
}

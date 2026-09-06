<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ---------------------------
// Kategoria wpisów bloga. Treść zarządzana z panelu Filament
// (App\Filament\Resources\BlogCategoryResource). Slug ustalany przy tworzeniu,
// żeby nie psuć linków /blog/kategoria/{slug}.
// ---------------------------

class BlogCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort',
    ];

    // Route-model-binding po "slug" zamiast "id"
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

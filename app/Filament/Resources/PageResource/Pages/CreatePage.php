<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    // Szerszy formularz — domyślne 7xl jest za wąskie dla długich treści Markdown
    protected Width|string|null $maxContentWidth = Width::Full;
}

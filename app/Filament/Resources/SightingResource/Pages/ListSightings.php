<?php

namespace App\Filament\Resources\SightingResource\Pages;

use App\Filament\Resources\SightingResource;
use Filament\Resources\Pages\ListRecords;

// Strona listy zgłoszeń "też widziałem"
class ListSightings extends ListRecords
{
    protected static string $resource = SightingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

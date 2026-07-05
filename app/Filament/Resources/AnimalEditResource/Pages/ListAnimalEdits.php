<?php

namespace App\Filament\Resources\AnimalEditResource\Pages;

use App\Filament\Resources\AnimalEditResource;
use Filament\Resources\Pages\ListRecords;

// Strona listy zgłoszeń moderacyjnych
class ListAnimalEdits extends ListRecords
{
    protected static string $resource = AnimalEditResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

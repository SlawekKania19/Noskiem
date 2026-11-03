<?php

namespace App\Filament\Resources\ChipRegistrationResource\Pages;

use App\Filament\Resources\ChipRegistrationResource;
use Filament\Resources\Pages\ListRecords;

// Strona listy wpisów w bazie chipów
class ListChipRegistrations extends ListRecords
{
    protected static string $resource = ChipRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

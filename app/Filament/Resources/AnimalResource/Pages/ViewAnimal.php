<?php

namespace App\Filament\Resources\AnimalResource\Pages;

use App\Filament\Actions\MatchPreviewAction;
use App\Filament\Resources\AnimalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

// Strona podglądu ogłoszenia z zakładką zdjęć (relation manager)
class ViewAnimal extends ViewRecord
{
    protected static string $resource = AnimalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ** Podobne ogłoszenia po zdjęciach (AI) — admin i moderator
            MatchPreviewAction::make(),
            EditAction::make(),
            AnimalResource::deleteAction(),
        ];
    }
}

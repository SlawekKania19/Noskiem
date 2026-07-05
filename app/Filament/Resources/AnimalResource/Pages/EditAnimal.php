<?php

namespace App\Filament\Resources\AnimalResource\Pages;

use App\Filament\Resources\AnimalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

// Strona edycji ogłoszenia
class EditAnimal extends EditRecord
{
    protected static string $resource = AnimalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

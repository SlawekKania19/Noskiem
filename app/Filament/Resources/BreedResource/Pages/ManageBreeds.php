<?php

namespace App\Filament\Resources\BreedResource\Pages;

use App\Filament\Resources\BreedResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBreeds extends ManageRecords
{
    protected static string $resource = BreedResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

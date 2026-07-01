<?php

namespace App\Filament\Resources\SpeciesResource\Pages;

use App\Filament\Resources\SpeciesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSpecies extends ManageRecords
{
    protected static string $resource = SpeciesResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Filament\Resources\IdentMarksTagResource\Pages;

use App\Filament\Resources\IdentMarksTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIdentMarksTags extends ManageRecords
{
    protected static string $resource = IdentMarksTagResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

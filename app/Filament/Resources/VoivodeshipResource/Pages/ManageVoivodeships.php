<?php

namespace App\Filament\Resources\VoivodeshipResource\Pages;

use App\Filament\Resources\VoivodeshipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVoivodeships extends ManageRecords
{
    protected static string $resource = VoivodeshipResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

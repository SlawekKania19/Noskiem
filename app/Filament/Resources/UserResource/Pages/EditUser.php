<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

// Strona edycji użytkownika
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ** Bez usuwania własnego konta — patrz też blokada roli Admin w formularzu
            DeleteAction::make()->visible(fn () => $this->record->id !== auth()->id()),
        ];
    }
}

<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

// Lista partnerów — dodawanie i edycja w modalach nad tabelą
class ManagePartners extends ManageRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

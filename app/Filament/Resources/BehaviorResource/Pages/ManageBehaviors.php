<?php

namespace App\Filament\Resources\BehaviorResource\Pages;

use App\Filament\Resources\BehaviorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBehaviors extends ManageRecords
{
    protected static string $resource = BehaviorResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

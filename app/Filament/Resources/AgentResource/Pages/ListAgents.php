<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use Filament\Resources\Pages\ListRecords;

// Strona listy Agentów noskiem (konta zakładają się same - bez przycisku "Utwórz")
class ListAgents extends ListRecords
{
    protected static string $resource = AgentResource::class;
}

<?php

namespace App\Filament\Resources\BlogCategoryResource\Pages;

use App\Filament\Resources\BlogCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlogCategories extends ManageRecords
{
    protected static string $resource = BlogCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

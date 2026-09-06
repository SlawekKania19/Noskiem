<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function () {
                    if ($this->record->cover_path) {
                        Storage::disk('public')->delete($this->record->cover_path);
                    }
                }),
        ];
    }

    // ** Autor niebędący adminem nie może przepisać wpisu na kogoś innego
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! auth()->user()?->is_admin) {
            $data['user_id'] = $this->record->user_id;
        }

        return $data;
    }
}

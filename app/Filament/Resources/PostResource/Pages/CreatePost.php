<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    // ** Autor niebędący adminem zawsze zapisuje wpis na siebie — niezależnie od
    // tego, co przyszło w żądaniu (pole autora ma wtedy ukryte)
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->is_admin) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }
}

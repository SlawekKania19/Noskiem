<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// ---------------------------
// Resource statycznych podstron (np. "cookies", w przyszłości "regulamin").
// Slug jest ustalany tylko przy tworzeniu strony — zablokowany do edycji,
// żeby nie zepsuć linków prowadzących do danej podstrony (np. z banera cookies).
// ---------------------------

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Strony';
    protected static ?string $modelLabel = 'strona';
    protected static ?string $pluralModelLabel = 'strony';
    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('title')
                    ->label('Tytuł')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label('Adres (slug)')
                    ->helperText('Część adresu URL strony, np. "cookies" → /cookies. Nieedytowalny po utworzeniu.')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),

                MarkdownEditor::make('content')
                    ->label('Treść')
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Tytuł')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Adres')->prefix('/'),
                Tables\Columns\TextColumn::make('updated_at')->label('Edytowano')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('title');
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit'   => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}

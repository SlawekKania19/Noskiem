<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogCategoryResource\Pages;
use App\Models\BlogCategory;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

// ---------------------------
// Kategorie wpisów bloga. Tylko Admin. Slug ustalany przy tworzeniu i blokowany
// przy edycji, żeby nie psuć linków /blog/kategoria/{slug}.
// ---------------------------

class BlogCategoryResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToAdmin;

    protected static ?string $model = BlogCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static ?string $navigationLabel = 'Kategorie';
    protected static ?string $modelLabel = 'kategoria';
    protected static ?string $pluralModelLabel = 'kategorie';
    protected static ?int $navigationSort = 82;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')
                    ->label('Nazwa')
                    ->required()
                    ->maxLength(100)
                    // ** Auto-podpowiedź sluga tylko przy tworzeniu
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Adres (slug)')
                    ->helperText('Część adresu URL, np. "porady" → /blog/kategoria/porady. Nieedytowalny po utworzeniu.')
                    ->required()
                    ->maxLength(120)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),

                Textarea::make('description')
                    ->label('Opis')
                    ->helperText('Krótki opis kategorii — pokazywany na stronie kategorii.')
                    ->maxLength(255)
                    ->rows(2),

                TextInput::make('sort')
                    ->label('Kolejność')
                    ->helperText('Mniejsza wartość = wyżej na liście kategorii.')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort')->label('Kolejność')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Adres')->prefix('/blog/kategoria/'),
                Tables\Columns\TextColumn::make('posts_count')->label('Wpisów')->counts('posts')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Edytowano')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->defaultSort('sort');
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBlogCategories::route('/'),
        ];
    }
}

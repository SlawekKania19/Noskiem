<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// ---------------------------
// Wpisy bloga. Dostęp: Admin i Autor. Autor widzi i edytuje tylko własne wpisy
// (getEloquentQuery + can* per rekord), Admin — wszystkie i może zmieniać autora.
// ---------------------------

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static ?string $navigationLabel = 'Wpisy';
    protected static ?string $modelLabel = 'wpis';
    protected static ?string $pluralModelLabel = 'wpisy';
    protected static ?int $navigationSort = 81;

    // ---------------------------
    // Autoryzacja
    // ---------------------------

    protected static function isStaff(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->is_admin || $user?->is_author);
    }

    protected static function ownsOrAdmin(Post $record): bool
    {
        $user = auth()->user();

        return (bool) ($user?->is_admin || $record->user_id === $user?->id);
    }

    public static function canViewAny(): bool
    {
        return static::isStaff();
    }

    public static function canCreate(): bool
    {
        return static::isStaff();
    }

    public static function canView($record): bool
    {
        return static::ownsOrAdmin($record);
    }

    public static function canEdit($record): bool
    {
        return static::ownsOrAdmin($record);
    }

    public static function canDelete($record): bool
    {
        return static::ownsOrAdmin($record);
    }

    // Autor widzi na liście tylko swoje wpisy
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()?->is_admin) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    // ---------------------------
    // Formularz
    // ---------------------------

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([

            Section::make('Treść')->schema([
                TextInput::make('title')
                    ->label('Tytuł')
                    ->required()
                    ->maxLength(160)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set) {
                        // ** Slug podpowiadamy tylko przy tworzeniu — po publikacji jest zablokowany
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Adres (slug)')
                    ->helperText('Część adresu URL wpisu, np. /blog/moj-wpis. Nieedytowalny po utworzeniu.')
                    ->required()
                    ->maxLength(180)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),

                Textarea::make('excerpt')
                    ->label('Zajawka')
                    ->helperText('Krótkie wprowadzenie — widoczne na liście wpisów i (gdy brak opisu SEO) w wynikach wyszukiwania.')
                    ->maxLength(320)
                    ->rows(3)
                    ->live(onBlur: true),

                Select::make('blog_category_id')
                    ->label('Kategoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->label('Nazwa')->required()->maxLength(100),
                        TextInput::make('slug')->label('Slug')->required()->maxLength(120)->alphaDash()->unique('blog_categories', 'slug'),
                        TextInput::make('sort')->label('Kolejność')->numeric()->default(0)->required(),
                    ]),

                FileUpload::make('cover_path')
                    ->label('Okładka')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('blog/covers')
                    ->visibility('public')
                    ->maxSize(4096)
                    ->helperText('Zalecane proporcje ~16:9. Maksymalnie 4 MB.'),

                RichEditor::make('body')
                    ->label('Treść')
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('blog/attachments')
                    ->fileAttachmentsVisibility('public'),
            ]),

            Section::make('Publikacja')->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(['draft' => 'Szkic', 'published' => 'Opublikowany'])
                    ->default('draft')
                    ->required()
                    ->live(),

                DateTimePicker::make('published_at')
                    ->label('Data publikacji')
                    ->seconds(false)
                    ->default(now())
                    ->helperText('Data w przyszłości = publikacja zaplanowana (wpis pojawi się automatycznie).')
                    ->visible(fn ($get) => $get('status') === 'published')
                    ->required(fn ($get) => $get('status') === 'published'),

                // ** Autor: Admin wybiera z listy, Autor ma pole ukryte (na starcie = on sam).
                // Dodatkowo pilnowane po stronie stron Create/Edit.
                Select::make('user_id')
                    ->label('Autor')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id())
                    ->required()
                    ->visible(fn () => auth()->user()?->is_admin ?? false),

                Hidden::make('user_id')
                    ->default(fn () => auth()->id())
                    ->visible(fn () => ! (auth()->user()?->is_admin ?? false)),
            ])->columns(2),

            Section::make('SEO — jak wpis wygląda w Google i w social mediach')
                ->description('To, co widać w wynikach wyszukiwania Google oraz na podglądzie linku wklejonego na Facebooku czy w Messengerze. Oba pola są opcjonalne — zostaw puste, a użyjemy tytułu i zajawki wpisu. Wypełnij tylko, jeśli chcesz, żeby w wyszukiwarce brzmiały inaczej niż na stronie.')
                ->collapsed()
                ->schema([
                    TextInput::make('meta_title')
                        ->label('Tytuł w wyszukiwarce')
                        ->maxLength(60)
                        ->placeholder(fn ($get) => $get('title') ?: 'np. Zaginął pies — pierwsze 24 godziny')
                        ->helperText('Niebieski, klikalny nagłówek wyniku w Google i tytuł podglądu linku w social mediach. Google zwykle pokazuje pierwsze ~60 znaków — dłuższy zostanie ucięty. Jeśli puste, używamy tytułu wpisu.'),

                    Textarea::make('meta_description')
                        ->label('Opis w wyszukiwarce')
                        ->maxLength(160)
                        ->rows(3)
                        ->placeholder(fn ($get) => $get('excerpt') ?: 'Krótkie streszczenie zachęcające do kliknięcia — 1–2 zdania.')
                        ->helperText('Szary tekst pod tytułem w wynikach Google i opis pod linkiem w social mediach. Optymalnie 120–160 znaków; dłuższy zostanie ucięty. Jeśli puste, używamy zajawki wpisu.'),
                ]),
        ]);
    }

    // ---------------------------
    // Tabela
    // ---------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')
                    ->label('Okładka')
                    ->disk('public')
                    ->width(64)
                    ->height(48),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (Post $record) => $record->title),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autor')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategoria')
                    ->badge()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Post $record) => match (true) {
                        $record->status === 'draft' => 'gray',
                        $record->isPublished() => 'success',
                        default => 'warning', // opublikowany, ale data jeszcze nie minęła
                    })
                    ->formatStateUsing(fn (Post $record) => match (true) {
                        $record->status === 'draft' => 'Szkic',
                        $record->isPublished() => 'Opublikowany',
                        default => 'Zaplanowany',
                    }),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publikacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Edytowano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['draft' => 'Szkic', 'published' => 'Opublikowany']),

                Tables\Filters\SelectFilter::make('blog_category_id')
                    ->label('Kategoria')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Autor')
                    ->relationship('author', 'name')
                    ->visible(fn () => auth()->user()?->is_admin ?? false),
            ])
            ->actions([
                Actions\Action::make('preview')
                    ->label('Podgląd')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Post $record) => Route::has('blog.show') ? route('blog.show', $record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn () => Route::has('blog.show')),

                Actions\Action::make('togglePublish')
                    ->label(fn (Post $record) => $record->status === 'published' ? 'Cofnij publikację' : 'Opublikuj')
                    ->icon(fn (Post $record) => $record->status === 'published' ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Post $record) => $record->status === 'published' ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Post $record) {
                        if ($record->status === 'published') {
                            $record->update(['status' => 'draft']);
                        } else {
                            $record->update([
                                'status' => 'published',
                                'published_at' => $record->published_at ?? now(),
                            ]);
                        }

                        Notification::make()->title('Zapisano')->success()->send();
                    }),

                Actions\EditAction::make(),

                Actions\DeleteAction::make()
                    ->before(function (Post $record) {
                        // ** Kasujemy okładkę z dysku razem z wpisem
                        if ($record->cover_path) {
                            Storage::disk('public')->delete($record->cover_path);
                        }
                    }),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}

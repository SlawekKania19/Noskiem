<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimalResource\Pages;
use App\Filament\Resources\AnimalResource\RelationManagers\PhotosRelationManager;
use App\Models\Animal;
use App\Models\Breed;
use App\Models\City;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// ---------------------------
// Resource ogłoszeń (Animal).
// Pełny CRUD z relation managerem zdjęć.
// ---------------------------

class AnimalResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToAdmin;
    use \App\Filament\Concerns\HasRelatedSubmissionsSection;

    protected static ?string $model = Animal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel = 'Ogłoszenia';
    protected static ?string $modelLabel = 'ogłoszenie';
    protected static ?string $pluralModelLabel = 'ogłoszenia';
    protected static ?int $navigationSort = 3;

    // ---------------------------
    // Formularz edycji ogłoszenia
    // ---------------------------

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Podstawowe dane')->schema([
                Grid::make(2)->schema([
                    Select::make('status')
                        ->label('Typ zgłoszenia')
                        ->options(['lost' => 'Zaginął', 'found' => 'Znaleziony'])
                        ->required(),

                    Select::make('mod_status')
                        ->label('Status moderacji')
                        ->options([
                            'pending'  => 'Oczekuje',
                            'approved' => 'Zatwierdzone',
                            'rejected' => 'Odrzucone',
                        ])
                        ->required(),

                    TextInput::make('title')
                        ->label('Tytuł')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('animal_name')->label('Imię zwierzęcia')->maxLength(100),
                    DatePicker::make('date_event')->label('Data zdarzenia')->required(),

                    Select::make('species_id')
                        ->label('Gatunek')
                        ->relationship('species', 'name_pl')
                        ->required()
                        ->live(), // reactive — odświeża listę ras

                    // Rasy filtrowane po wybranym gatunku
                    Select::make('breed_id')
                        ->label('Rasa')
                        ->options(function (Get $get) {
                            $speciesId = $get('species_id');
                            if (! $speciesId) {
                                return Breed::pluck('breed_pl', 'id');
                            }
                            return Breed::where('species_id', $speciesId)->pluck('breed_pl', 'id');
                        }),

                    Textarea::make('description')
                        ->label('Opis')
                        ->required()
                        ->rows(4)
                        ->columnSpan(2),

                    Textarea::make('ident_marks')
                        ->label('Znaki szczególne')
                        ->rows(2)
                        ->columnSpan(2),

                    Textarea::make('behavior')
                        ->label('Zachowanie')
                        ->rows(2)
                        ->columnSpan(2),

                    Toggle::make('chip_present')->label('Chip')->live()->columnSpan(2),

                    TextInput::make('chip_number')
                        ->label('Numer chipa')
                        ->maxLength(50)
                        ->visible(fn (Get $get) => $get('chip_present')),
                ]),
            ]),

            Section::make('Lokalizacja')->schema([
                Grid::make(2)->schema([
                    Select::make('voivodeship_id')
                        ->label('Województwo')
                        ->relationship('voivodeship', 'name_pl')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('city_id', null)),

                    // Miasta wyszukiwane na żądanie w obrębie wybranego województwa — rejestr
                    // SIMC ma ~100 tys. rekordów, więc nie da się ich wczytać do selecta naraz
                    Select::make('city_id')
                        ->label('Miasto')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search, Get $get) {
                            $voivodeshipId = $get('voivodeship_id');
                            if (! $voivodeshipId) {
                                return [];
                            }
                            return City::where('voivodeship_id', $voivodeshipId)
                                ->where('name_pl', 'like', "{$search}%")
                                ->orderBy('name_pl')
                                ->limit(20)
                                ->pluck('name_pl', 'id');
                        })
                        ->getOptionLabelUsing(fn ($value) => City::find($value)?->name_pl)
                        ->disabled(fn (Get $get) => ! $get('voivodeship_id')),

                    TextInput::make('location_text')
                        ->label('Adres / opis miejsca')
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('latitude')
                        ->label('Szerokość geo.')
                        ->numeric()
                        ->step(0.0000001),

                    TextInput::make('longitude')
                        ->label('Długość geo.')
                        ->numeric()
                        ->step(0.0000001),
                ]),
            ]),

            Section::make('Kontakt')->schema([
                Grid::make(3)->schema([
                    TextInput::make('contact_name')->label('Imię i nazwisko')->required()->maxLength(100),
                    TextInput::make('contact_email')->label('E-mail')->email()->required()->maxLength(255),
                    TextInput::make('contact_phone')->label('Telefon')->tel()->maxLength(20),
                ]),
            ]),

        ]);
    }

    // ---------------------------
    // Tabela — lista ogłoszeń
    // ---------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('main_photo')
                    ->label('Zdjęcie')
                    ->getStateUsing(fn ($record) => $record->photos->firstWhere('is_main', true)?->path
                        ?? $record->photos->first()?->path)
                    ->disk('public')
                    ->width(60)
                    ->height(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->title)
                    // ** Stała szerokość + obcięcie CSS, żeby długie tytuły nie wymuszały scrolla poziomego tabeli
                    ->extraAttributes(['class' => 'truncate max-w-[160px] block']),

                Tables\Columns\TextColumn::make('status')
                    ->label('Typ')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'lost'  => 'danger',
                        'found' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'lost'  => 'Zaginął',
                        'found' => 'Znaleziony',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('mod_status')
                    ->label('Moderacja')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'Oczekuje',
                        'approved' => 'Zatwierdzone',
                        'rejected' => 'Odrzucone',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('species.name_pl')->label('Gatunek')->sortable(),
                Tables\Columns\TextColumn::make('city.name_pl')->label('Miasto'),
                Tables\Columns\TextColumn::make('contact_email')->label('E-mail')->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mod_status')
                    ->label('Status moderacji')
                    ->options([
                        'pending'  => 'Oczekuje',
                        'approved' => 'Zatwierdzone',
                        'rejected' => 'Odrzucone',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Typ zgłoszenia')
                    ->options([
                        'lost'  => 'Zaginął',
                        'found' => 'Znaleziony',
                    ]),

                Tables\Filters\SelectFilter::make('species_id')
                    ->label('Gatunek')
                    ->relationship('species', 'name_pl'),
            ])
            ->actions([
                // ** Same ikony zamiast przycisków z tekstem — oszczędza miejsce w wierszu, żeby tabela mieściła się bez scrolla poziomego
                Actions\ViewAction::make()->iconButton(),
                Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ---------------------------
    // Infolist — widok szczegółów ogłoszenia
    // ---------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    static::podstawoweDaneSection(),

                    Grid::make(1)->schema([
                        static::lokalizacjaSection(),
                        static::kontaktSection(),

                        Section::make('Dane techniczne')
                            ->description('Widoczne wyłącznie w panelu admina — na wypadek problemów prawnych.')
                            ->schema([
                                TextEntry::make('submitter_ip')
                                    ->label('Adres IP zgłaszającego')
                                    ->placeholder('Brak danych'),
                            ]),
                    ]),
                ]),

            Section::make('Zdjęcia')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('photos')
                        ->label('')
                        ->schema([
                            ImageEntry::make('path')
                                ->label('')
                                ->disk('public')
                                ->width(200)
                                ->height(150),
                            IconEntry::make('is_main')
                                ->label('Główne')
                                ->boolean()
                                ->trueIcon('heroicon-o-star')
                                ->falseIcon('heroicon-o-star')
                                ->trueColor('warning')
                                ->falseColor('gray'),
                        ])
                        ->columns(4),
                ]),

            static::relatedSubmissionsSection(),

        ]);
    }

    protected static function podstawoweDaneSection(): Section
    {
        return Section::make('Podstawowe dane')->schema([
            Grid::make(2)->schema([
                TextEntry::make('title')->label('Tytuł')->columnSpan(2),
                TextEntry::make('status')
                    ->label('Typ')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'lost'  => 'danger',
                        'found' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'lost'  => 'Zaginął',
                        'found' => 'Znaleziony',
                        default => $state,
                    }),
                TextEntry::make('mod_status')
                    ->label('Status moderacji')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'Oczekuje',
                        'approved' => 'Zatwierdzone',
                        'rejected' => 'Odrzucone',
                        default    => $state,
                    }),
                TextEntry::make('animal_name')->label('Imię'),
                TextEntry::make('date_event')->label('Data zdarzenia')->date('d.m.Y'),
                TextEntry::make('species.name_pl')->label('Gatunek'),
                TextEntry::make('breed.breed_pl')->label('Rasa'),
                TextEntry::make('description')->label('Opis')->columnSpan(2),
                TextEntry::make('ident_marks')->label('Znaki szczególne')->columnSpan(2),
                TextEntry::make('behavior')->label('Zachowanie')->placeholder('Brak')->columnSpan(2),
                IconEntry::make('chip_present')->label('Chip')->boolean(),
                TextEntry::make('chip_number')->label('Numer chipa'),
            ]),
        ]);
    }

    protected static function lokalizacjaSection(): Section
    {
        return Section::make('Lokalizacja')->schema([
            Grid::make(2)->schema([
                TextEntry::make('voivodeship.name_pl')->label('Województwo'),
                TextEntry::make('city.name_pl')->label('Miasto'),
                TextEntry::make('location_text')->label('Adres / opis miejsca')->columnSpan(2),
                TextEntry::make('latitude')->label('Szer. geo.'),
                TextEntry::make('longitude')->label('Dług. geo.'),
            ]),
        ]);
    }

    protected static function kontaktSection(): Section
    {
        return Section::make('Kontakt')->schema([
            Grid::make(3)->schema([
                TextEntry::make('contact_name')->label('Imię i nazwisko'),
                TextEntry::make('contact_email')->label('E-mail'),
                TextEntry::make('contact_phone')->label('Telefon'),
            ]),
        ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAnimals::route('/'),
            'view'   => Pages\ViewAnimal::route('/{record}'),
            'edit'   => Pages\EditAnimal::route('/{record}/edit'),
        ];
    }
}



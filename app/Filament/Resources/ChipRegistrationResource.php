<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChipRegistrationResource\Pages;
use App\Models\Breed;
use App\Models\ChipRegistration;
use App\Models\City;
use App\Models\Species;
use App\Models\Voivodeship;
use App\Services\ChipModerationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

// ---------------------------
// Resource moderacji bazy chipów (ChipRegistration). Lista + podgląd szczegółów
// z akcjami - wzorowane na SightingResource. Zdjęcie naklejki z dysku prywatnego
// (podpisany, czasowy URL), ostrzeżenie gdy numer jest już zatwierdzony w innym wpisie.
// ---------------------------

class ChipRegistrationResource extends Resource
{
    protected static ?string $model = ChipRegistration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'Baza chipów';
    protected static ?string $modelLabel = 'wpis w bazie chipów';
    protected static ?string $pluralModelLabel = 'baza chipów';
    protected static ?int $navigationSort = 3;

    // Moderacja - dostępna dla Admina i Moderatora, tak samo jak SightingResource
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->is_admin || $user?->is_moderator;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    // ** Usuwanie - np. przy rozstrzyganiu konfliktu numerów (stary, nieaktualny wpis)
    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    // Ukrywa zgłoszenia, których autor jeszcze nie potwierdził adresu e-mail
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('email_verified_at');
    }

    // Liczba oczekujących zgłoszeń i edycji - widoczna przy nazwie w menu, ukryta gdy brak (0)
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where(fn ($q) => $q->where('mod_status', 'pending')->orWhereNotNull('pending_changes'))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ** Wspólne dla tabeli i infolisty
    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'pending'  => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default    => 'gray',
        };
    }

    public static function statusLabel(?string $state): ?string
    {
        return match ($state) {
            'pending'  => 'Oczekuje',
            'approved' => 'Zatwierdzone',
            'rejected' => 'Odrzucone',
            default    => $state,
        };
    }

    // Opis do modala "Zatwierdź" - przy konflikcie numerów ostrzega moderatora
    public static function approveDescription(ChipRegistration $record): string
    {
        if ($record->hasChipConflict()) {
            return 'Uwaga: ten numer chipa jest już zatwierdzony w innym wpisie. Po zatwierdzeniu wyszukiwarka pokaże nowszy wpis - jeśli stary jest nieaktualny, usuń go.';
        }

        return 'Wpis będzie wyszukiwalny po numerze chipa.';
    }

    // Opisy pól do porównania "było -> jest" (bez naklejki - ta ma osobny podgląd)
    private const CHANGE_LABELS = [
        'chip_number'     => 'Numer chipa',
        'animal_name'     => 'Imię',
        'species_id'      => 'Gatunek',
        'breed_id'        => 'Rasa',
        'voivodeship_id'  => 'Województwo',
        'city_id'         => 'Miejscowość',
        'contact_phone'   => 'Telefon',
        'contact_email'   => 'E-mail',
        'additional_info' => 'Informacje dodatkowe',
    ];

    // Tylko zmienione pola z pending_changes: [etykieta, było, jest] - id słowników jako nazwy
    public static function changesDiff(ChipRegistration $record): array
    {
        $changes = $record->pending_changes ?? [];

        $display = fn (string $field, $value) => match ($field) {
            'species_id'     => Species::find($value)?->name_pl,
            'breed_id'       => Breed::find($value)?->breed_pl,
            'voivodeship_id' => Voivodeship::find($value)?->name_pl,
            'city_id'        => City::find($value)?->name_pl,
            default          => $value,
        };

        $rows = [];

        foreach (self::CHANGE_LABELS as $field => $label) {
            if (! array_key_exists($field, $changes) || (string) $changes[$field] === (string) $record->{$field}) {
                continue;
            }

            $rows[] = [$label, $display($field, $record->{$field}), $display($field, $changes[$field])];
        }

        return $rows;
    }

    // ---------------------------
    // Tabela - lista wpisów
    // ---------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chip_number')
                    ->label('Numer chipa')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('animal_name')
                    ->label('Imię'),

                Tables\Columns\TextColumn::make('species.name_pl')
                    ->label('Gatunek'),

                Tables\Columns\TextColumn::make('city.name_pl')
                    ->label('Miejscowość'),

                // ** Inny zatwierdzony wpis z tym samym numerem - do rozstrzygnięcia
                Tables\Columns\IconColumn::make('conflict')
                    ->label('Konflikt')
                    ->getStateUsing(fn ($record) => $record->hasChipConflict())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon(null),

                // ** Edycja zatwierdzonego wpisu czeka na moderację (pending_changes)
                Tables\Columns\IconColumn::make('has_changes')
                    ->label('Edycja')
                    ->getStateUsing(fn ($record) => $record->pending_changes !== null)
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->trueColor('warning')
                    ->falseIcon(null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mod_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => static::statusColor($state))
                    ->formatStateUsing(fn ($state) => static::statusLabel($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // ** "Edycje do zatwierdzenia" to wpisy zatwierdzone z pending_changes -
                // osobna opcja, bo po mod_status są nieodróżnialne od zwykłych zatwierdzonych
                Tables\Filters\SelectFilter::make('mod_status')
                    ->label('Status moderacji')
                    ->options([
                        'pending'  => 'Oczekuje',
                        'changes'  => 'Edycje do zatwierdzenia',
                        'approved' => 'Zatwierdzone',
                        'rejected' => 'Odrzucone',
                    ])
                    ->default('pending')
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        null, ''  => $query,
                        'changes' => $query->whereNotNull('pending_changes'),
                        default   => $query->where('mod_status', $data['value']),
                    }),
            ])
            ->actions([
                Actions\ViewAction::make()->label('Otwórz')->iconButton(),

                Actions\Action::make('approve')
                    ->label('Zatwierdź')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Zatwierdzić wpis?')
                    ->modalDescription(fn ($record) => static::approveDescription($record))
                    ->visible(fn ($record) => $record->mod_status === 'pending')
                    ->action(function ($record) {
                        try {
                            app(ChipModerationService::class)->approve($record, auth()->id());
                            Notification::make()->title('Wpis zatwierdzony')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Actions\Action::make('reject')
                    ->label('Odrzuć')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->iconButton()
                    ->modalHeading('Odrzuć wpis')
                    ->form([
                        Textarea::make('reason')
                            ->label('Powód odrzucenia')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->visible(fn ($record) => $record->mod_status === 'pending')
                    ->action(function ($record, array $data) {
                        try {
                            app(ChipModerationService::class)->reject($record, $data['reason'], auth()->id());
                            Notification::make()->title('Wpis odrzucony')->warning()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultPaginationPageOption(25);
    }

    // ---------------------------
    // Infolist - widok szczegółów wpisu
    // ---------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ** Konflikt numerów - linki do innych zatwierdzonych wpisów z tym numerem
            Section::make('Konflikt numeru chipa')
                ->description('Ten numer jest już zatwierdzony w innym wpisie. Porównaj zdjęcia naklejek i zdecyduj, który wpis jest właściwy.')
                ->visible(fn ($record) => $record->hasChipConflict())
                ->schema([
                    TextEntry::make('conflicts')
                        ->label('Inne zatwierdzone wpisy')
                        ->state(fn ($record) => new HtmlString(
                            $record->conflictingRegistrations()->get()
                                ->map(fn ($other) => '<a href="'.e(static::getUrl('view', ['record' => $other])).'" class="underline" target="_blank">#'.$other->id.' - '.e($other->animal_name).' (dodano '.$other->created_at->format('d.m.Y').')</a>')
                                ->implode('<br>')
                        )),
                ]),

            // ** Edycja właściciela czekająca na moderację - tylko zmienione pola
            Section::make('Proponowane zmiany')
                ->description('Właściciel zmienił wpis. Do czasu zatwierdzenia w wyszukiwarce widoczna jest obecna wersja.')
                ->visible(fn ($record) => $record->pending_changes !== null)
                ->schema([
                    TextEntry::make('changes_diff')
                        ->hiddenLabel()
                        ->state(fn ($record) => new HtmlString(
                            '<table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="py-1 pr-4">Pole</th><th class="py-1 pr-4">Było</th><th class="py-1">Jest</th></tr></thead><tbody>'
                            .collect(static::changesDiff($record))
                                ->map(fn ($row) => '<tr class="border-t border-gray-200"><td class="py-1 pr-4 font-medium">'.e($row[0]).'</td><td class="py-1 pr-4 text-gray-500 line-through">'.e($row[1] ?? '-').'</td><td class="py-1">'.e($row[2] ?? '-').'</td></tr>')
                                ->implode('')
                            .'</tbody></table>'
                        ))
                        ->visible(fn ($record) => static::changesDiff($record) !== []),
                    TextEntry::make('no_text_changes')
                        ->hiddenLabel()
                        ->state('Bez zmian w danych tekstowych.')
                        ->visible(fn ($record) => static::changesDiff($record) === []),
                    ImageEntry::make('pending_sticker')
                        ->label('Nowe zdjęcie naklejki')
                        ->state(fn ($record) => $record->pending_changes['sticker_photo_path'] ?? null)
                        ->disk('local')
                        ->visibility('private')
                        ->height(300)
                        ->visible(fn ($record) => ! empty($record->pending_changes['sticker_photo_path'])),
                ]),

            Section::make('Dane zwierzęcia')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('chip_number')->label('Numer chipa')->copyable(),
                    TextEntry::make('mod_status')
                        ->label('Status moderacji')
                        ->badge()
                        ->color(fn ($state) => static::statusColor($state))
                        ->formatStateUsing(fn ($state) => static::statusLabel($state)),
                    TextEntry::make('animal_name')->label('Imię'),
                    TextEntry::make('species.name_pl')->label('Gatunek'),
                    TextEntry::make('breed.breed_pl')->label('Rasa'),
                    TextEntry::make('city.name_pl')
                        ->label('Miejscowość')
                        ->formatStateUsing(fn ($record) => collect([$record->city?->name_pl, $record->voivodeship?->name_pl])->filter()->implode(', ')),
                    TextEntry::make('additional_info')
                        ->label('Informacje dodatkowe')
                        ->placeholder('Brak')
                        ->columnSpan(2),
                    // Powód odrzucenia - widoczny tylko gdy odrzucone
                    TextEntry::make('mod_reject_reason')
                        ->label('Powód odrzucenia')
                        ->columnSpan(2)
                        ->visible(fn ($record) => $record->mod_status === 'rejected' && $record->mod_reject_reason),
                ]),
            ]),

            // ** Dysk prywatny - Filament generuje podpisany URL ważny kilka minut
            Section::make('Zdjęcie naklejki')->schema([
                ImageEntry::make('sticker_photo_path')
                    ->hiddenLabel()
                    ->disk('local')
                    ->visibility('private')
                    ->height(300),
            ]),

            Section::make('Kontakt')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('contact_phone')->label('Telefon'),
                    TextEntry::make('contact_email')->label('E-mail'),
                ]),
            ]),

            Section::make('Dane techniczne')
                ->description('Widoczne wyłącznie w panelu admina - na wypadek problemów prawnych.')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('submitter_ip')
                            ->label('Adres IP zgłaszającego')
                            ->placeholder('Brak danych'),
                        TextEntry::make('created_at')
                            ->label('Dodano')
                            ->dateTime('d.m.Y H:i'),
                    ]),
                ]),

        ]);
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChipRegistrations::route('/'),
            'view'  => Pages\ViewChipRegistration::route('/{record}'),
        ];
    }
}

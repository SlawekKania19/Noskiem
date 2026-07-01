<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimalEditResource\Pages;
use App\Models\AnimalEdit;
use App\Services\ModerationService;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
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
// Resource moderacji zgłoszeń (AnimalEdit).
// Lista oczekujących + podgląd szczegółów z diffem i akcjami.
// ---------------------------

class AnimalEditResource extends Resource
{
    protected static ?string $model = AnimalEdit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Moderacja';
    protected static ?string $modelLabel = 'zgłoszenie';
    protected static ?string $pluralModelLabel = 'zgłoszenia';
    protected static ?int $navigationSort = 1;

    // ---------------------------
    // Tabela — lista zgłoszeń
    // ---------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Miniaturka głównego zdjęcia
                Tables\Columns\ImageColumn::make('first_photo')
                    ->label('Zdjęcie')
                    ->getStateUsing(fn ($record) => $record->photos->first()?->path)
                    ->disk('public')
                    ->width(60)
                    ->height(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->limit(45),

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

                Tables\Columns\TextColumn::make('species.name_pl')
                    ->label('Gatunek')
                    ->sortable(),

                Tables\Columns\TextColumn::make('city.name_pl')
                    ->label('Miasto'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('mod_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'resolved' => 'info',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'Oczekuje',
                        'approved' => 'Zatwierdzone',
                        'rejected' => 'Odrzucone',
                        'resolved' => 'Rozwiązane',
                        default    => $state,
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mod_status')
                    ->label('Status moderacji')
                    ->options([
                        'pending'  => 'Oczekuje',
                        'approved' => 'Zatwierdzone',
                        'rejected' => 'Odrzucone',
                        'resolved' => 'Rozwiązane',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Actions\ViewAction::make()->label('Otwórz'),

                // Szybkie zatwierdzenie z listy
                Actions\Action::make('approve')
                    ->label('Zatwierdź')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Zatwierdzić zgłoszenie?')
                    ->modalDescription('Ogłoszenie zostanie opublikowane na stronie.')
                    ->visible(fn ($record) => $record->mod_status === 'pending')
                    ->action(function ($record) {
                        try {
                            app(ModerationService::class)->approve($record, auth()->id());
                            Notification::make()->title('Zgłoszenie zatwierdzone')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                // Odrzucenie z obowiązkowym powodem
                Actions\Action::make('reject')
                    ->label('Odrzuć')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->modalHeading('Odrzuć zgłoszenie')
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
                            app(ModerationService::class)->reject($record, $data['reason'], auth()->id());
                            Notification::make()->title('Zgłoszenie odrzucone')->warning()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultPaginationPageOption(25);
    }

    // ---------------------------
    // Infolist — widok szczegółów zgłoszenia
    // ---------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Podstawowe dane')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('title')->label('Tytuł')->columnSpan(2),
                    TextEntry::make('status')
                        ->label('Typ zgłoszenia')
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
                            'resolved' => 'Rozwiązane',
                            default    => $state,
                        }),
                    TextEntry::make('animal_name')->label('Imię zwierzęcia'),
                    TextEntry::make('species.name_pl')->label('Gatunek'),
                    TextEntry::make('breed.breed_pl')->label('Rasa'),
                    TextEntry::make('date_event')->label('Data zdarzenia')->date('d.m.Y'),
                    TextEntry::make('description')->label('Opis')->columnSpan(2),
                    TextEntry::make('ident_marks')->label('Znaki szczególne')->columnSpan(2),
                    TextEntry::make('chip_present')
                        ->label('Chip')
                        ->formatStateUsing(fn ($state) => $state ? 'Tak' : 'Nie'),
                    TextEntry::make('chip_number')->label('Numer chipa'),
                    // Powód odrzucenia — widoczny tylko gdy odrzucone
                    TextEntry::make('mod_reject_reason')
                        ->label('Powód odrzucenia')
                        ->columnSpan(2)
                        ->visible(fn ($record) => $record->mod_status === 'rejected' && $record->mod_reject_reason),
                ]),
            ]),

            Section::make('Lokalizacja')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('voivodeship.name_pl')->label('Województwo'),
                    TextEntry::make('city.name_pl')->label('Miasto'),
                    TextEntry::make('location_text')->label('Adres / opis miejsca')->columnSpan(2),
                    TextEntry::make('latitude')->label('Szerokość geograficzna'),
                    TextEntry::make('longitude')->label('Długość geograficzna'),
                ]),
            ]),

            Section::make('Kontakt')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('contact_name')->label('Imię i nazwisko'),
                    TextEntry::make('contact_email')->label('E-mail'),
                    TextEntry::make('contact_phone')->label('Telefon'),
                ]),
            ]),

            Section::make('Zdjęcia')->schema([
                RepeatableEntry::make('photos')
                    ->label('')
                    ->schema([
                        ImageEntry::make('path')
                            ->label('')
                            ->disk('public')
                            ->width(200)
                            ->height(150),
                    ])
                    ->columns(4),
            ]),

            // Diff — widoczny tylko dla edycji istniejącego ogłoszenia
            Section::make('Różnice względem oryginału')
                ->visible(fn ($record) => $record !== null && $record->animal_id !== null)
                ->schema([
                    TextEntry::make('diff_html')
                        ->label('')
                        ->html()
                        ->getStateUsing(function ($record) {
                            $diff = app(ModerationService::class)->diff($record);

                            if (empty($diff)) {
                                return '<p class="text-gray-500">Brak zmian w treści.</p>';
                            }

                            // Mapowanie nazw pól na polskie etykiety
                            $labels = [
                                'status'         => 'Typ zgłoszenia',
                                'title'          => 'Tytuł',
                                'description'    => 'Opis',
                                'animal_name'    => 'Imię',
                                'ident_marks'    => 'Znaki szczególne',
                                'chip_present'   => 'Chip',
                                'chip_number'    => 'Numer chipa',
                                'species_id'     => 'Gatunek (ID)',
                                'breed_id'       => 'Rasa (ID)',
                                'date_event'     => 'Data zdarzenia',
                                'voivodeship_id' => 'Województwo (ID)',
                                'city_id'        => 'Miasto (ID)',
                                'location_text'  => 'Adres / opis',
                                'latitude'       => 'Szer. geo.',
                                'longitude'      => 'Dług. geo.',
                                'contact_name'   => 'Imię kontaktowe',
                                'contact_email'  => 'E-mail kontaktowy',
                                'contact_phone'  => 'Telefon kontaktowy',
                                'photos'         => 'Zdjęcia',
                            ];

                            $rows = '';
                            foreach ($diff as $field => $change) {
                                $label = $labels[$field] ?? $field;
                                if ($field === 'photos') {
                                    $old = count($change['old']) . ' szt.';
                                    $new = count($change['new']) . ' szt.';
                                } else {
                                    $old = e((string) ($change['old'] ?? '—'));
                                    $new = e((string) ($change['new'] ?? '—'));
                                }
                                $rows .= "<tr>
                                    <td style=\"padding:6px 10px;border:1px solid #e5e7eb;font-weight:600\">{$label}</td>
                                    <td style=\"padding:6px 10px;border:1px solid #e5e7eb;background:#fff1f2\">{$old}</td>
                                    <td style=\"padding:6px 10px;border:1px solid #e5e7eb;background:#f0fdf4\">{$new}</td>
                                </tr>";
                            }

                            return "<table style=\"width:100%;border-collapse:collapse;font-size:0.875rem\">
                                <thead>
                                    <tr>
                                        <th style=\"padding:6px 10px;border:1px solid #e5e7eb;background:#f9fafb;text-align:left\">Pole</th>
                                        <th style=\"padding:6px 10px;border:1px solid #e5e7eb;background:#fff1f2;text-align:left\">Przed zmianą</th>
                                        <th style=\"padding:6px 10px;border:1px solid #e5e7eb;background:#f0fdf4;text-align:left\">Po zmianie</th>
                                    </tr>
                                </thead>
                                <tbody>{$rows}</tbody>
                            </table>";
                        }),
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
            'index' => Pages\ListAnimalEdits::route('/'),
            'view'  => Pages\ViewAnimalEdit::route('/{record}'),
        ];
    }
}


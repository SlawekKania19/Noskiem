<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SightingResource\Pages;
use App\Models\Sighting;
use App\Services\SightingModerationService;
use Illuminate\Database\Eloquent\Builder;
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
// Resource moderacji zgłoszeń "też widziałem" (Sighting).
// Lista oczekujących + podgląd szczegółów z akcjami — wzorowane na AnimalEditResource,
// bez sekcji diff (sighting nie jest edycją istniejącego rekordu).
// ---------------------------

class SightingResource extends Resource
{
    protected static ?string $model = Sighting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationLabel = 'Moderacja sub-ogłoszeń';
    protected static ?string $modelLabel = 'zgłoszenie "widziałem"';
    protected static ?string $pluralModelLabel = 'zgłoszenia "widziałem"';
    protected static ?int $navigationSort = 2;

    // Moderacja — dostępna dla Admina i Moderatora, tak samo jak AnimalEditResource
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->is_admin || $user?->is_moderator;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }

    // Ukrywa przed moderatorami zgłoszenia, których autor jeszcze nie potwierdził
    // adresu e-mail (patrz SightingController::confirmEmail)
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('email_verified_at');
    }

    // Liczba oczekujących zgłoszeń — widoczna przy nazwie w menu, ukryta gdy brak (0)
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('mod_status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ---------------------------
    // Tabela — lista zgłoszeń
    // ---------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('first_photo')
                    ->label('Zdjęcie')
                    ->getStateUsing(fn ($record) => $record->photos->first()?->path)
                    ->disk('public')
                    ->width(60)
                    ->height(60),

                Tables\Columns\TextColumn::make('animal.title')
                    ->label('Ogłoszenie')
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->animal?->generated_title)
                    ->url(fn ($record) => $record->animal_id
                        ? AnimalResource::getUrl('view', ['record' => $record->animal_id])
                        : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('date_seen')
                    ->label('Data zaobserwowania')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokalizacja'),

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
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'  => 'Oczekuje',
                        'approved' => 'Zatwierdzone',
                        'rejected' => 'Odrzucone',
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
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Actions\ViewAction::make()->label('Otwórz')->iconButton(),

                Actions\Action::make('approve')
                    ->label('Zatwierdź')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Zatwierdzić zgłoszenie?')
                    ->modalDescription('Zgłoszenie pojawi się w timeline pod ogłoszeniem.')
                    ->visible(fn ($record) => $record->mod_status === 'pending')
                    ->action(function ($record) {
                        try {
                            app(SightingModerationService::class)->approve($record, auth()->id());
                            Notification::make()->title('Zgłoszenie zatwierdzone')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Actions\Action::make('reject')
                    ->label('Odrzuć')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->iconButton()
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
                            app(SightingModerationService::class)->reject($record, $data['reason'], auth()->id());
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
                    TextEntry::make('animal.title')
                        ->label('Ogłoszenie')
                        ->columnSpan(2)
                        ->formatStateUsing(fn ($record) => $record->animal?->generated_title),
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
                    TextEntry::make('date_seen')->label('Data zaobserwowania')->date('d.m.Y'),
                    TextEntry::make('description')->label('Opis')->columnSpan(2),
                    // Powód odrzucenia — widoczny tylko gdy odrzucone
                    TextEntry::make('mod_reject_reason')
                        ->label('Powód odrzucenia')
                        ->columnSpan(2)
                        ->visible(fn ($record) => $record->mod_status === 'rejected' && $record->mod_reject_reason),
                ]),
            ]),

            Section::make('Lokalizacja')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('location')->label('Opis miejsca')->columnSpan(2),
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

            Section::make('Dane techniczne')
                ->description('Widoczne wyłącznie w panelu admina — na wypadek problemów prawnych.')
                ->schema([
                    TextEntry::make('submitter_ip')
                        ->label('Adres IP zgłaszającego')
                        ->placeholder('Brak danych'),
                ]),

            Section::make('Zdjęcia')
                ->visible(fn ($record) => $record->photos->isNotEmpty())
                ->schema([
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

        ]);
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSightings::route('/'),
            'view'  => Pages\ViewSighting::route('/{record}'),
        ];
    }
}

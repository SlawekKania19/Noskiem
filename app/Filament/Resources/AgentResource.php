<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RestrictedToAdmin;
use App\Filament\Resources\AgentResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// ---------------------------
// Agenci noskiem - wolontariusze z kontem (users.is_agent), którzy dostają prośby
// o pomoc w poszukiwaniach w swoich obszarach działania. Konta zakładają się same
// (strona /agenci), tu admin je przegląda, blokuje i usuwa. Tylko dla Admina.
// ---------------------------
class AgentResource extends Resource
{
    use RestrictedToAdmin;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Agenci';
    protected static ?string $modelLabel = 'agent';
    protected static ?string $pluralModelLabel = 'agenci';
    protected static ?string $slug = 'agenci';
    protected static ?int $navigationSort = 98;

    // ** Konta powstają przez rejestrację na stronie, nie w panelu
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_agent', true)
            ->with(['city', 'agentAreas'])
            ->withCount(['agentAreas', 'searchedAnimals', 'agentNotifications']);
    }

    // ** Liczba aktywnych agentów przy pozycji w menu
    public static function getNavigationBadge(): ?string
    {
        return (string) User::activeAgents()->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Imię')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-mail')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city.name_pl')
                    ->label('Miejscowość')
                    ->state(fn (User $record) => $record->city?->label)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('agent_status')
                    ->label('Status')
                    ->state(fn (User $record) => match (true) {
                        $record->agent_blocked_at !== null => 'Zablokowany',
                        $record->email_verified_at === null => 'Niepotwierdzony e-mail',
                        ! $record->agent_notify => 'Powiadomienia wyłączone',
                        default => 'Aktywny',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Aktywny' => 'success',
                        'Zablokowany' => 'danger',
                        'Niepotwierdzony e-mail' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('agent_areas_count')->label('Obszary')->numeric()->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('agent_notifications_count')->label('Wysłane prośby')->numeric()->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('searched_animals_count')->label('Szuka')->numeric()->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('created_at')->label('Dołączył(a)')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('agent_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktywni',
                        'unverified' => 'Niepotwierdzony e-mail',
                        'blocked' => 'Zablokowani',
                        'no_areas' => 'Bez obszarów',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'active' => $query->activeAgents(),
                        'unverified' => $query->whereNull('email_verified_at'),
                        'blocked' => $query->whereNotNull('agent_blocked_at'),
                        'no_areas' => $query->doesntHave('agentAreas'),
                        default => $query,
                    }),
                Tables\Filters\SelectFilter::make('voivodeship_id')
                    ->label('Województwo')
                    ->relationship('voivodeship', 'name_pl'),
            ])
            ->actions([
                // ** Podgląd obszarów działania (punkt + promień, link do mapy)
                Actions\Action::make('areas')
                    ->label('Obszary')
                    ->icon('heroicon-o-map')
                    ->color('gray')
                    ->modalHeading(fn (User $record) => "Obszary działania - {$record->name}")
                    ->modalContent(fn (User $record) => view('filament.agent-areas', ['areas' => $record->agentAreas]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Zamknij'),

                // ** Blokada: agent nie dostaje maili i nie może klikać "Szukam"; konto zostaje
                Actions\Action::make('block')
                    ->label('Zablokuj')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Agent przestanie dostawać prośby o pomoc i nie będzie mógł oznaczać ogłoszeń. Konto zostaje.')
                    ->visible(fn (User $record) => $record->agent_blocked_at === null)
                    ->action(function (User $record) {
                        $record->forceFill(['agent_blocked_at' => now()])->save();
                        Notification::make()->title('Agent zablokowany')->success()->send();
                    }),

                Actions\Action::make('unblock')
                    ->label('Odblokuj')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record) => $record->agent_blocked_at !== null)
                    ->action(function (User $record) {
                        $record->forceFill(['agent_blocked_at' => null])->save();
                        Notification::make()->title('Agent odblokowany')->success()->send();
                    }),

                // ** Konto wyłącznie agenta znika w całości; konto z rolą w panelu
                // (admin/moderator/autor) traci tylko dane agenta
                Actions\Action::make('remove')
                    ->label('Usuń')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Usuń agenta')
                    ->modalDescription(fn (User $record) => $record->isAgentOnly()
                        ? "Konto {$record->email} zostanie usunięte razem z obszarami i oznaczeniami „Szukam”. Tej operacji nie można cofnąć."
                        : "Konto {$record->email} ma też rolę w panelu, więc zostanie - usuniemy tylko obszary, oznaczenia „Szukam” i status agenta.")
                    ->action(function (User $record) {
                        $record->removeAgent();
                        Notification::make()->title('Agent usunięty')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Animal;
use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

// ---------------------------
// Widget statystyk na dashboardzie panelu admina.
// ---------------------------

class StatsOverviewWidget extends BaseWidget
{
    // Odświeżanie co 60 sekund
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // ** Liczniki według statusu moderacji
        $pending  = Animal::where('mod_status', 'pending')->count();
        $approved = Animal::where('mod_status', 'approved')->count();
        $rejected = Animal::where('mod_status', 'rejected')->count();

        // ** Liczniki według typu zgłoszenia (tylko zatwierdzone)
        $lost  = Animal::where('mod_status', 'approved')->where('status', 'lost')->count();
        $found = Animal::where('mod_status', 'approved')->where('status', 'found')->count();

        // ** Wiadomości — nowe dziś i łącznie
        $messagesToday = Message::whereDate('created_at', today())->count();
        $messagesTotal = Message::count();

        return [
            Stat::make('Oczekują na moderację', $pending)
                ->description('Zgłoszenia do rozpatrzenia')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock')
                ->url(route('filament.admin.resources.animal-edits.index')),

            Stat::make('Aktywnych ogłoszeń', $approved)
                ->description("Zaginęło: {$lost} · Znaleziono: {$found}")
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('success')
                ->icon('heroicon-o-newspaper')
                ->url(route('filament.admin.resources.animals.index')),

            Stat::make('Odrzuconych', $rejected)
                ->description('Łącznie odrzuconych zgłoszeń')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($rejected > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Wiadomości kontaktowe', $messagesTotal)
                ->description("Nowych dziś: {$messagesToday}")
                ->descriptionIcon('heroicon-m-envelope')
                ->color($messagesToday > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-envelope')
                ->url(route('filament.admin.resources.messages.index')),
        ];
    }
}

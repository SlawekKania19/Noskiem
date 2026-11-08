<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MatchStatusWidget;
use App\Services\MatchStatus;
use App\Support\MatchSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

// ---------------------------
// Panel "Dopasowanie AI" — u góry stan workera/kolejki/maili (MatchStatusWidget),
// niżej ustawienia dopasowań po zdjęciach (kolejka wektorów,
// progi wyszukiwania, filtry, maile). Zapis do tabeli settings przez MatchSettings,
// które nadpisuje config/noskiem_match.php. Zmiana działa od następnego uruchomienia
// workera (scheduler co minutę).
// ---------------------------

class MatchAi extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.match-ai';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Dopasowanie AI';
    protected static ?string $title = 'Dopasowanie AI';
    protected static ?string $slug = 'dopasowanie-ai';
    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    // ** Formularz pokazuje wartości obowiązujące teraz (z panelu albo domyślne)
    protected function fillForm(): void
    {
        $this->form->fill(Arr::undot(MatchSettings::current()));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kolejka liczenia wektorów')
                    ->description('Worker co minutę bierze partię zdjęć bez wektora i liczy je modelem AI. Pauza ani okno godzin niczego nie gubią — zdjęcia po prostu czekają w kolejce.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('queue.paused')
                            ->label('Wstrzymaj przetwarzanie')
                            ->helperText($this->defaultHint('queue.paused').' Wstrzymuje też maile z dopasowaniami.')
                            ->onColor('danger')
                            ->columnSpanFull(),

                        TextInput::make('queue.hours')
                            ->label('Okno godzin pracy')
                            ->placeholder('np. 22:00-06:00')
                            ->regex('/^\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}$/')
                            ->validationMessages(['regex' => 'Format: GG:MM-GG:MM, np. 22:00-06:00.'])
                            ->helperText('Puste = całą dobę. Może przechodzić przez północ. '.$this->defaultHint('queue.hours')),

                        TextInput::make('queue.batch_limit')
                            ->label('Zdjęć na jedno uruchomienie')
                            ->numeric()->integer()->minValue(1)->maxValue(500)->required()
                            ->helperText($this->defaultHint('queue.batch_limit')),

                        TextInput::make('queue.max_seconds')
                            ->label('Limit czasu uruchomienia')
                            ->numeric()->integer()->minValue(30)->maxValue(1800)->required()
                            ->suffix('s')
                            ->helperText('Worker kończy po bieżącym zdjęciu, reszta idzie w następnym. '.$this->defaultHint('queue.max_seconds')),

                        TextInput::make('queue.threads')
                            ->label('Wątki CPU')
                            ->numeric()->integer()->minValue(1)->maxValue(8)->required()
                            ->helperText('1 wątek ~2,7 s/zdjęcie, 2 wątki ~1,7 s. Więcej = większe obciążenie hostingu. '.$this->defaultHint('queue.threads')),

                        TextInput::make('queue.nice')
                            ->label('Priorytet procesu (nice)')
                            ->numeric()->integer()->minValue(0)->maxValue(19)->required()
                            ->helperText('0–19, wyżej = niższy priorytet (strona WWW dostaje CPU pierwsza). '.$this->defaultHint('queue.nice')),
                    ]),

                Section::make('Wyszukiwanie podobnych')
                    ->description('Progi dotyczą samego podobieństwa zdjęć (0–1). Zostały wstępnie skalibrowane — zmiana wpływa na to, kto dostaje maile z dopasowaniami. Niższy próg = więcej wyników, ale też więcej przypadkowych.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('search.threshold_possible')
                            ->label('Próg „możliwe dopasowanie”')
                            ->numeric()->minValue(0)->maxValue(1)->step(0.01)->required()
                            ->helperText('Poniżej — nie pokazujemy. '.$this->defaultHint('search.threshold_possible')),

                        TextInput::make('search.threshold_strong')
                            ->label('Próg „bardzo podobne”')
                            ->numeric()->minValue(0)->maxValue(1)->step(0.01)->required()
                            ->helperText($this->defaultHint('search.threshold_strong')),

                        TextInput::make('search.top_n')
                            ->label('Maks. wyników w mailu')
                            ->numeric()->integer()->minValue(1)->maxValue(50)->required()
                            ->helperText($this->defaultHint('search.top_n')),
                    ]),

                Section::make('Filtry kandydatów')
                    ->description('Twarde warunki — ogłoszenie, które ich nie spełnia, w ogóle nie jest porównywane.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('search.filters.radius_km')
                            ->label('Promień od miejsca zgłoszenia')
                            ->numeric()->minValue(0)->maxValue(1000)->required()
                            ->suffix('km')
                            ->helperText('0 = bez limitu. '.$this->defaultHint('search.filters.radius_km')),

                        TextInput::make('search.filters.date_window_days')
                            ->label('Okno dat zdarzenia (± dni)')
                            ->numeric()->integer()->minValue(0)->maxValue(365)->required()
                            ->suffix('dni')
                            ->helperText('0 = wyłączone (daty w zgłoszeniach bywają niewiarygodne). '.$this->defaultHint('search.filters.date_window_days')),

                        Toggle::make('search.filters.opposite_status')
                            ->label('Tylko przeciwny status (zaginął ↔ znaleziony)')
                            ->helperText($this->defaultHint('search.filters.opposite_status')),

                        Toggle::make('search.filters.same_species')
                            ->label('Tylko ten sam gatunek')
                            ->helperText($this->defaultHint('search.filters.same_species')),

                        Toggle::make('search.filters.exclude_same_email')
                            ->label('Pomijaj ogłoszenia z tym samym e-mailem autora')
                            ->helperText('Ta sama osoba = zwykle duplikat. Przy testach jednym adresem wyłącz, inaczej nic się nie dopasuje. '.$this->defaultHint('search.filters.exclude_same_email')),
                    ]),

                Section::make('Maile z dopasowaniami')
                    ->schema([
                        TextInput::make('notify.ttl_hours')
                            ->label('Jak długo zgłoszenie czeka na wektory zdjęć')
                            ->numeric()->integer()->minValue(1)->maxValue(720)->required()
                            ->suffix('h')
                            ->helperText('Po tym czasie (np. przy długiej pauzie) mail nie zostanie wysłany — po kilku dniach byłby już mylący. '.$this->defaultHint('notify.ttl_hours')),
                    ]),
            ])
            ->statePath('data');
    }

    // ** Podpowiedź z wartością domyślną (z pliku/.env) pod polem
    protected function defaultHint(string $path): string
    {
        $value = MatchSettings::defaults()[$path];

        $text = match (true) {
            is_bool($value) => $value ? 'włączone' : 'wyłączone',
            $value === '' || $value === null => 'puste',
            default => (string) $value,
        };

        return "Domyślnie: {$text}.";
    }

    // ** Kafelki ze stanem workera, kolejki i maili (odświeżane co 10 s)
    protected function getHeaderWidgets(): array
    {
        return [MatchStatusWidget::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryFailed')
                ->label('Ponów błędne zdjęcia')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Ponowić zdjęcia z błędem?')
                ->modalDescription('Zdjęcia, których nie udało się policzyć, wrócą do kolejki — worker spróbuje je policzyć w najbliższych partiach.')
                ->visible(fn () => app(MatchStatus::class)->failedCount() > 0)
                ->action(function () {
                    $count = app(MatchStatus::class)->retryFailed();

                    Notification::make()->success()->title("Wróciło do kolejki: {$count} zdj.")->send();
                }),

            Action::make('reset')
                ->label('Przywróć domyślne')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Przywrócić ustawienia domyślne?')
                ->modalDescription('Wszystkie zmiany z tej strony zostaną usunięte — obowiązywać będą wartości z pliku konfiguracji (.env).')
                ->action(function () {
                    MatchSettings::reset();
                    $this->fillForm();

                    Notification::make()->success()->title('Przywrócono ustawienia domyślne')->send();
                }),
        ];
    }

    public function save(): void
    {
        $values = Arr::dot($this->form->getState());

        // ** Próg "bardzo podobne" nie może być niższy niż "możliwe"
        if ((float) $values['search.threshold_strong'] < (float) $values['search.threshold_possible']) {
            throw ValidationException::withMessages([
                'data.search.threshold_strong' => 'Próg „bardzo podobne” nie może być niższy niż „możliwe dopasowanie”.',
            ]);
        }

        MatchSettings::save($values);
        $this->fillForm();

        Notification::make()
            ->success()
            ->title('Ustawienia zapisane')
            ->body('Zmiana zadziała przy następnym uruchomieniu workera (do minuty).')
            ->send();
    }
}

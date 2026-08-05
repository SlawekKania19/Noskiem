<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\TitleGenerator;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

// ---------------------------
// Panel ustawień aplikacji (klucz-wartość w tabeli settings).
// Na razie: liczba ogłoszeń na stronie głównej, dane kontaktowe stopki,
// teksty sekcji Hero (Szukam/Znalazłem) oraz podpowiedzi w formularzu zgłoszenia.
// ---------------------------

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Ustawienia';
    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'homepage_items_limit' => Setting::get('homepage_items_limit', '6'),
            'contact_email' => Setting::get('contact_email', 'kontakt@noskiem.pl'),
            'hero_headline_lost' => Setting::get('hero_headline_lost', 'Zaginął Ci pupil? Znajdziemy go noskiem.'),
            'hero_headline_found' => Setting::get('hero_headline_found', 'Znalazłeś zwierzaka? Pomóż mu wrócić do domu.'),
            'hero_description_lost' => Setting::get('hero_description_lost', 'Przeszukaj bazę znalezionych i widzianych zwierząt z całej Polski, zanim dodasz własne ogłoszenie.'),
            'hero_description_found' => Setting::get('hero_description_found', 'Dodaj ogłoszenie o znalezionym zwierzaku, żeby jak najszybciej trafiło do właściciela.'),
            'create_form_hint_lost' => Setting::get('create_form_hint_lost', 'Opisz zwierzę jak najdokładniej — im więcej szczegółów, tym większa szansa na odnalezienie.'),
            'create_form_hint_found' => Setting::get('create_form_hint_found', 'Dziękujemy za zgłoszenie — Twoja pomoc zwiększa szansę, że zwierzę wróci do domu.'),
            'create_form_location_hint_lost' => Setting::get('create_form_location_hint_lost', 'Wskaż miejsce, w którym zwierzę widziano po raz ostatni — to zwiększa szansę na odnalezienie.'),
            'create_form_location_hint_found' => Setting::get('create_form_location_hint_found', 'Wskaż dokładne miejsce, w którym znalazłeś zwierzę — pomoże to właścicielowi je zidentyfikować.'),
            'animal_title_template_lost' => Setting::get('animal_title_template_lost', TitleGenerator::DEFAULT_TEMPLATE_LOST),
            'animal_title_template_found' => Setting::get('animal_title_template_found', TitleGenerator::DEFAULT_TEMPLATE_FOUND),
            'create_form_ident_marks_tags' => Setting::get('create_form_ident_marks_tags', implode("\n", [
                'Blizna',
                'Kulawizna',
                'Zez',
                'Brak ucha',
                'Przycięty ogon',
                'Łaciata sierść',
                'Duża plama/znamię',
                'Różnokolorowe oczy (heterochromia)',
                'Obroża',
                'Sterylizowany/kastrowany',
            ])),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Strona główna')
                    ->description('Sekcje Poszukiwane/Widziane oraz nagłówki Hero')
                    ->schema([
                        TextInput::make('homepage_items_limit')
                            ->label('Liczba ogłoszeń na sekcję')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(24)
                            ->required(),

                        TextInput::make('hero_headline_lost')
                            ->label('Nagłówek Hero — tryb "Szukam"')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('hero_description_lost')
                            ->label('Opis Hero — tryb "Szukam"')
                            ->required()
                            ->rows(2),

                        TextInput::make('hero_headline_found')
                            ->label('Nagłówek Hero — tryb "Znalazłem"')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('hero_description_found')
                            ->label('Opis Hero — tryb "Znalazłem"')
                            ->required()
                            ->rows(2),
                    ]),

                Section::make('Tytuł ogłoszenia')
                    ->description('Tytuł nie jest już wpisywany ręcznie przez użytkownika — generuje się automatycznie z poniższego szablonu. Osobny szablon dla "Zaginiony" i "Znaleziony", bo przy znalezieniu imię zwierzaka prawie nigdy nie jest znane.')
                    ->schema([
                        TextInput::make('animal_title_template_lost')
                            ->label('Szablon tytułu — status "Zaginiony"')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->helperText('Dostępne tagi: '.implode(', ', array_keys(TitleGenerator::TAGS))),

                        Placeholder::make('animal_title_template_lost_preview')
                            ->label('Podgląd (na przykładowych danych)')
                            ->content(fn (Get $get) => TitleGenerator::example($get('animal_title_template_lost') ?: TitleGenerator::DEFAULT_TEMPLATE_LOST, 'lost')),

                        TextInput::make('animal_title_template_found')
                            ->label('Szablon tytułu — status "Znaleziony"')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->helperText('Dostępne tagi: '.implode(', ', array_keys(TitleGenerator::TAGS)).'. Unikaj [Imię] — przy zgłoszeniu znalezienia zwykle nieznane.'),

                        Placeholder::make('animal_title_template_found_preview')
                            ->label('Podgląd (na przykładowych danych)')
                            ->content(fn (Get $get) => TitleGenerator::example($get('animal_title_template_found') ?: TitleGenerator::DEFAULT_TEMPLATE_FOUND, 'found')),
                    ]),

                Section::make('Formularz zgłoszenia')
                    ->description('Tekst pod przełącznikiem Zaginiony/Znaleziony na stronie dodawania ogłoszenia')
                    ->schema([
                        Textarea::make('create_form_hint_lost')
                            ->label('Tekst — status "Zaginiony"')
                            ->required()
                            ->rows(2),

                        Textarea::make('create_form_hint_found')
                            ->label('Tekst — status "Znaleziony"')
                            ->required()
                            ->rows(2),

                        Textarea::make('create_form_ident_marks_tags')
                            ->label('Znaki szczególne — podpowiedzi')
                            ->helperText('Jedna fraza w linii — pojawi się jako przycisk pod polem "Znaki szczególne".')
                            ->required()
                            ->rows(6),

                        Textarea::make('create_form_location_hint_lost')
                            ->label('Tekst pod "Lokalizacja" — status "Zaginiony"')
                            ->required()
                            ->rows(2),

                        Textarea::make('create_form_location_hint_found')
                            ->label('Tekst pod "Lokalizacja" — status "Znaleziony"')
                            ->required()
                            ->rows(2),
                    ]),

                Section::make('Kontakt')
                    ->description('Dane wyświetlane w stopce publicznej strony')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Adres e-mail kontaktowy')
                            ->email()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, (string) $value);
        }

        Notification::make()
            ->success()
            ->title('Ustawienia zapisane')
            ->send();
    }
}

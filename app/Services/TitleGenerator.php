<?php

namespace App\Services;

use App\Models\Setting;

// ---------------------------
// Generuje tytuł ogłoszenia na podstawie szablonu z tagami, edytowalnego
// w panelu (App\Filament\Pages\Settings). Użytkownik formularza publicznego
// nie wpisuje tytułu samodzielnie — jest wyliczany z danych zgłoszenia.
// ---------------------------

class TitleGenerator
{
    // ** Osobne szablony i klucze ustawień dla "zaginął" i "znaleziony" — przy znalezieniu
    // imię zwierzaka prawie nigdy nie jest znane, więc domyślny szablon go nie zawiera
    public const DEFAULT_TEMPLATE_LOST = '[Status]: [Imię] – [Gatunek], [Miejscowość]';

    public const DEFAULT_TEMPLATE_FOUND = '[Status]: [Gatunek] – [Miejscowość]';

    private const SETTING_KEYS = [
        'lost'  => 'animal_title_template_lost',
        'found' => 'animal_title_template_found',
    ];

    // ** Dostępne tagi -> opis, do wyświetlenia w panelu admina
    public const TAGS = [
        '[Imię]'         => 'Imię zwierzaka',
        '[Gatunek]'      => 'Gatunek (np. Pies)',
        '[Rasa]'         => 'Rasa (np. Owczarek niemiecki)',
        '[Miejscowość]'  => 'Miejscowość zdarzenia',
        '[Województwo]'  => 'Województwo zdarzenia',
        '[Status]'       => 'Zaginiony / Znaleziony',
    ];

    private const STATUS_LABELS = [
        'lost'  => 'Zaginiony',
        'found' => 'Znaleziony',
    ];

    private const EXAMPLE_VALUES = [
        'lost' => [
            'animal_name'      => 'Burek',
            'species_name'     => 'Pies',
            'breed_name'       => 'Owczarek niemiecki',
            'city_name'        => 'Kraków',
            'voivodeship_name' => 'Małopolskie',
            'status'           => 'lost',
        ],
        'found' => [
            'animal_name'      => '',
            'species_name'     => 'Pies',
            'breed_name'       => 'Owczarek niemiecki',
            'city_name'        => 'Kraków',
            'voivodeship_name' => 'Małopolskie',
            'status'           => 'found',
        ],
    ];

    // Generuje tytuł na podstawie szablonu zapisanego w ustawieniach — osobnego dla statusu lost/found
    public static function generate(array $values): string
    {
        $status = $values['status'] ?? 'lost';
        $key = self::SETTING_KEYS[$status] ?? self::SETTING_KEYS['lost'];
        $default = $status === 'found' ? self::DEFAULT_TEMPLATE_FOUND : self::DEFAULT_TEMPLATE_LOST;

        return self::render(Setting::get($key, $default), $values);
    }

    // Przykładowy tytuł do podglądu w panelu — na stałych, przykładowych danych dla danego statusu
    public static function example(string $template, string $status = 'lost'): string
    {
        return self::render($template, self::EXAMPLE_VALUES[$status] ?? self::EXAMPLE_VALUES['lost']);
    }

    // Podmienia tagi w szablonie na wartości i porządkuje odstępy
    private static function render(string $template, array $values): string
    {
        $replacements = [
            '[Imię]'        => $values['animal_name'] ?? '',
            '[Gatunek]'     => $values['species_name'] ?? '',
            '[Rasa]'        => $values['breed_name'] ?? '',
            '[Miejscowość]' => $values['city_name'] ?? '',
            '[Województwo]' => $values['voivodeship_name'] ?? '',
            '[Status]'      => self::STATUS_LABELS[$values['status'] ?? ''] ?? '',
        ];

        $title = strtr($template, $replacements);

        return trim(preg_replace('/\s+/', ' ', $title));
    }
}

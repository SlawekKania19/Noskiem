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
    public const DEFAULT_TEMPLATE = '[Status]: [Imię] – [Gatunek], [Miejscowość]';

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
        'animal_name'      => 'Burek',
        'species_name'     => 'Pies',
        'breed_name'       => 'Owczarek niemiecki',
        'city_name'        => 'Kraków',
        'voivodeship_name' => 'Małopolskie',
        'status'           => 'lost',
    ];

    // Generuje tytuł na podstawie szablonu zapisanego w ustawieniach
    public static function generate(array $values): string
    {
        return self::render(Setting::get('animal_title_template', self::DEFAULT_TEMPLATE), $values);
    }

    // Przykładowy tytuł do podglądu w panelu — na stałych, przykładowych danych
    public static function example(string $template): string
    {
        return self::render($template, self::EXAMPLE_VALUES);
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

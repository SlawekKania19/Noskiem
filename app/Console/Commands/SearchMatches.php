<?php

namespace App\Console\Commands;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Services\PhotoMatchService;
use Illuminate\Console\Command;

// ---------------------------
// Diagnostyka / kalibracja: wyszukiwanie podobnych ogłoszeń dla zgłoszenia lub
// ogłoszenia, wynik jako JSON albo tabela. Nic nie zapisuje i nie wysyła.
//   php artisan match:search --edit=12
//   php artisan match:search --animal=34 --all     (także kandydaci poniżej progu)
// ---------------------------
class SearchMatches extends Command
{
    protected $signature = 'match:search
        {--edit= : ID zgłoszenia (animal_edits)}
        {--animal= : ID ogłoszenia (animals)}
        {--all : Pokaż też kandydatów poniżej progu (top log_floor_n)}
        {--json : Surowy JSON}';

    protected $description = 'Pokazuje podobne ogłoszenia po zdjęciach (bez zapisu i maili)';

    public function handle(PhotoMatchService $service): int
    {
        $query = match (true) {
            filled($this->option('edit')) => AnimalEdit::findOrFail($this->option('edit')),
            filled($this->option('animal')) => Animal::findOrFail($this->option('animal')),
            default => null,
        };

        if (! $query) {
            $this->error('Podaj --edit=ID albo --animal=ID');

            return self::INVALID;
        }

        $out = $service->search($query);

        if ($this->option('json')) {
            $this->line(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Status: {$out['status']} | model: {$out['model_version']} | kandydaci: {$out['n_candidates']}"
            ." | porównani: {$out['n_compared']} | bez wektora: {$out['n_pending_embedding']}");

        $rows = collect($out['results'])
            ->filter(fn ($r) => $this->option('all') || $r['shown'])
            ->map(fn ($r) => [
                $r['position'] ?? '-', $r['rank'], $r['listing_id'], number_format($r['score'], 3),
                number_format($r['bonus'], 3), $r['level'],
                $r['breed_match'] === null ? '?' : ($r['breed_match'] ? 'tak' : 'nie'),
                $r['color_overlap'] ?? '?', $r['distance_km'],
            ]);

        if ($rows->isEmpty()) {
            $this->line('Brak podobnych ogłoszeń powyżej progu.');

            return self::SUCCESS;
        }

        $this->table(['poz.', 'rank', 'animal', 'wynik', 'bonus', 'poziom', 'rasa', 'kolory', 'km'], $rows);

        return self::SUCCESS;
    }
}

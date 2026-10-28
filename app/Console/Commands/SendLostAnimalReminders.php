<?php

namespace App\Console\Commands;

use App\Mail\LostAnimalReminder;
use App\Models\Animal;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

// ---------------------------
// Comiesięczne przypomnienia dla właścicieli aktywnych ogłoszeń o zaginionych zwierzakach.
// Odpalana codziennie ze schedulera (routes/console.php) — każde ogłoszenie ma swój
// własny "miesiąc", liczony od ostatniego przypomnienia, a przy pierwszym od created_at
// (czyli momentu zatwierdzenia, bo Animal powstaje dopiero w ModerationService::approve()).
// Liczenie od daty, a nie po dniu miesiąca, sprawia, że ogłoszenia z 29–31 dnia nie giną,
// a dzień bez crona nadrabia się przy następnym uruchomieniu.
// ---------------------------
class SendLostAnimalReminders extends Command
{
    protected $signature = 'animals:send-reminders {--dry-run : Tylko wypisz, kto dostałby przypomnienie}';

    protected $description = 'Wysyła comiesięczne przypomnienia do właścicieli ogłoszeń o zaginionych zwierzakach';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $threshold = now()->subMonthNoOverflow();
        $sent = 0;
        $failed = 0;

        $this->dueQuery($threshold)
            ->with(['species', 'breed', 'city', 'voivodeship']) // do generated_title w mailu
            ->chunkById(100, function ($animals) use ($dryRun, &$sent, &$failed) {
                foreach ($animals as $animal) {
                    if ($dryRun) {
                        $this->line("#{$animal->id} → {$animal->contact_email} ({$animal->generated_title})");
                        $sent++;
                        continue;
                    }

                    // ** Pojedyncza nieudana wysyłka nie przerywa całej partii — ogłoszenie
                    // zostaje z dotychczasowym last_reminder_at i trafi do kolejki jutro
                    try {
                        Mail::to($animal->contact_email, $animal->contact_name)
                            ->send(new LostAnimalReminder($animal));
                    } catch (\Throwable $e) {
                        report($e);
                        $failed++;
                        continue;
                    }

                    // ** Zapis z pominięciem Eloquenta — bez ruszania updated_at
                    // i bez przeliczania search_index (event saved w modelu)
                    Animal::whereKey($animal->id)->toBase()->update(['last_reminder_at' => now()]);
                    $sent++;
                }
            });

        $this->info($dryRun
            ? "Do wysłania: {$sent}"
            : "Wysłano: {$sent}, błędy: {$failed}");

        return self::SUCCESS;
    }

    // ** Aktywne ogłoszenia "Zaginął", którym minął miesiąc od ostatniego przypomnienia
    // (lub od zatwierdzenia, jeśli jeszcze żadnego nie było). Ogłoszenia oznaczone jako
    // odnalezione mają mod_status = resolved, więc same tu nie trafiają.
    protected function dueQuery($threshold): Builder
    {
        return Animal::query()
            ->where('status', 'lost')
            ->where('mod_status', 'approved')
            ->whereNotNull('contact_email')
            ->where(function (Builder $query) use ($threshold) {
                $query->where('last_reminder_at', '<=', $threshold)
                    ->orWhere(fn (Builder $query) => $query
                        ->whereNull('last_reminder_at')
                        ->where('created_at', '<=', $threshold));
            });
    }
}

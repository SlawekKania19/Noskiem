<?php

namespace App\Console\Commands;

use App\Mail\FoundAnimalRetentionNotice;
use App\Models\Animal;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

// ---------------------------
// Retencja starych ogłoszeń. Odpalana codziennie ze schedulera (routes/console.php):
// 1. Aktywne "Znaleziony" po 2 latach (albo po przedłużonym terminie retention_until) -
//    mail z ostrzeżeniem i linkiem do przedłużenia o 6 miesięcy.
// 2. Ostrzeżone tydzień temu i nieprzedłużone - usuwane.
// 3. Zamknięte (resolved, oba statusy) po 2 latach - usuwane od razu, bez maila,
//    bo i tak nie są widoczne na stronie.
// Pliki zdjęć kasuje hook deleting w modelu Animal, resztę rekordów kaskada FK.
// ---------------------------
class ApplyAnimalRetention extends Command
{
    protected $signature = 'animals:retention {--dry-run : Tylko wypisz, co zostałoby usunięte i do kogo poszłoby ostrzeżenie}';

    protected $description = 'Ostrzega autorów starych ogłoszeń "Znaleziony" i usuwa nieprzedłużone oraz stare zamknięte ogłoszenia';

    // Po ilu latach od zatwierdzenia ogłoszenie uznajemy za przeterminowane
    public const EXPIRE_YEARS = 2;

    // Ile dni autor ma na przedłużenie po otrzymaniu ostrzeżenia
    public const GRACE_DAYS = 7;

    // O ile miesięcy (od kliknięcia) autor może przedłużyć ogłoszenie - AnimalEditController::extend()
    public const EXTEND_MONTHS = 6;

    protected bool $dryRun = false;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        // ** Najpierw usuwanie, potem ostrzeżenia - świeżo ostrzeżone ogłoszenie
        // nie ma szans trafić do usunięcia w tym samym przebiegu
        $deletedNoticed = $this->deleteAll($this->noticeExpiredQuery());
        $deletedResolved = $this->deleteAll($this->resolvedExpiredQuery());
        [$noticed, $failed] = $this->sendNotices();

        $this->info($this->dryRun
            ? "Do usunięcia (po ostrzeżeniu): {$deletedNoticed}, do usunięcia (zamknięte): {$deletedResolved}, do ostrzeżenia: {$noticed}"
            : "Usunięto (po ostrzeżeniu): {$deletedNoticed}, usunięto (zamknięte): {$deletedResolved}, ostrzeżono: {$noticed}, błędy wysyłki: {$failed}");

        return self::SUCCESS;
    }

    // ---------------------------
    // Wysyłka ostrzeżeń. retention_notice_at ustawiamy dopiero po udanej wysyłce -
    // tydzień do usunięcia liczy się tylko wtedy, gdy autor faktycznie dostał maila.
    // Nieudana wysyłka = ogłoszenie wraca do kolejki jutro.
    // ---------------------------
    protected function sendNotices(): array
    {
        $sent = 0;
        $failed = 0;

        $this->noticeDueQuery()
            ->with(['species', 'breed', 'city', 'voivodeship']) // do generated_title w mailu
            ->chunkById(100, function ($animals) use (&$sent, &$failed) {
                foreach ($animals as $animal) {
                    if ($this->dryRun) {
                        $this->line("ostrzeżenie #{$animal->id} → {$animal->contact_email} ({$animal->generated_title})");
                        $sent++;
                        continue;
                    }

                    $noticeAt = now();
                    $deleteAt = $noticeAt->copy()->addDays(self::GRACE_DAYS);

                    try {
                        Mail::to($animal->contact_email, $animal->contact_name)
                            ->send(new FoundAnimalRetentionNotice($animal, $deleteAt));
                    } catch (\Throwable $e) {
                        report($e);
                        $failed++;
                        continue;
                    }

                    // ** Zapis z pominięciem Eloquenta - bez ruszania updated_at
                    // i bez przeliczania search_index (event saved w modelu)
                    Animal::whereKey($animal->id)->toBase()->update(['retention_notice_at' => $noticeAt]);
                    $sent++;
                }
            });

        return [$sent, $failed];
    }

    // ** Usuwa wszystkie ogłoszenia z zapytania przez Eloquenta (żeby odpalił hook
    // kasujący pliki zdjęć). Błąd przy jednym nie przerywa reszty - spróbujemy jutro.
    protected function deleteAll(Builder $query): int
    {
        $count = 0;

        $query->chunkById(100, function ($animals) use (&$count) {
            foreach ($animals as $animal) {
                if ($this->dryRun) {
                    $this->line("usunięcie #{$animal->id} ({$animal->status}, {$animal->mod_status}, dodane {$animal->created_at->toDateString()})");
                    $count++;
                    continue;
                }

                try {
                    $animal->delete();
                    $count++;
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        return $count;
    }

    // ** Aktywne "Znaleziony", którym minął termin, a ostrzeżenie jeszcze nie poszło
    protected function noticeDueQuery(): Builder
    {
        return Animal::query()
            ->where('status', 'found')
            ->where('mod_status', 'approved')
            ->whereNull('retention_notice_at')
            ->whereNotNull('contact_email')
            ->where(fn (Builder $query) => $this->whereExpired($query));
    }

    // ** Ostrzeżone co najmniej GRACE_DAYS dni temu (liczone po dniach kalendarzowych -
    // ostrzeżenie z dnia D usuwamy w przebiegu D+7, niezależnie od godziny wysyłki).
    // Przedłużenie czyści retention_notice_at, więc przedłużone tu nie trafią.
    protected function noticeExpiredQuery(): Builder
    {
        return Animal::query()
            ->where('status', 'found')
            ->where('mod_status', 'approved')
            ->where('retention_notice_at', '<=', now()->subDays(self::GRACE_DAYS)->endOfDay());
    }

    // ** Zamknięte (resolved) po terminie - oba statusy, bez ostrzegania
    protected function resolvedExpiredQuery(): Builder
    {
        return Animal::query()
            ->where('mod_status', 'resolved')
            ->where(fn (Builder $query) => $this->whereExpired($query));
    }

    // ** Termin minął: przedłużony (retention_until) albo domyślny - EXPIRE_YEARS od zatwierdzenia
    // (created_at, bo Animal powstaje dopiero w ModerationService::approve())
    protected function whereExpired(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $query) => $query
                ->whereNotNull('retention_until')
                ->where('retention_until', '<=', now()))
            ->orWhere(fn (Builder $query) => $query
                ->whereNull('retention_until')
                ->where('created_at', '<=', now()->subYears(self::EXPIRE_YEARS)));
    }
}

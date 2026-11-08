<?php

namespace Tests\Feature\Matching;

use App\Models\PhotoEmbedding;
use App\Services\MatchStatus;
use App\Services\PhotoEmbeddingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

// ---------------------------
// Kolejka wektorów zdjęć (match:embed, PhotoEmbeddingService) — bez prawdziwego
// Pythona: Process::fake zwraca JSONL w formacie noskiem_match/cli.py embed
// ---------------------------

class PhotoEmbeddingTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
    }

    // ** Odpowiedź Pythona dla podanych zdjęć: wynik ok albo błąd (gdy id w $errors)
    private function pythonOutput(iterable $photoIds, array $errors = [], bool $done = true): string
    {
        $vec = base64_encode(pack('g*', ...array_fill(0, 768, 0.036)));
        $lines = [json_encode(['event' => 'ready', 'model_version' => $this->mv, 'load_ms' => 2400])];

        foreach ($photoIds as $id) {
            $lines[] = json_encode(in_array($id, $errors, true)
                ? ['event' => 'result', 'photo_id' => $id, 'status' => 'error', 'error' => 'OSError: cannot identify image file']
                : ['event' => 'result', 'photo_id' => $id, 'status' => 'ok', 'vector_b64' => $vec, 'dim' => 768, 'crop_found' => true, 'ms' => 2700]);
        }

        if ($done) {
            $lines[] = json_encode(['event' => 'done', 'processed' => 0, 'failed' => 0, 'stopped' => null, 'peak_rss_mb' => 1250.0]);
        }

        return implode("\n", $lines)."\n";
    }

    public function test_kolejka_bierze_tylko_zdjecia_do_porownan_i_najpierw_swieze_zgloszenia(): void
    {
        $approved = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $this->photo(['animal_id' => $this->animal('rejected')->id]);   // odrzucone — pomijamy
        $this->photo(['animal_id' => $this->animal('resolved')->id]);   // odnalezione — pomijamy
        $verified = $this->photo(['animal_edit_id' => $this->edit(true)->id]);
        $this->photo(['animal_edit_id' => $this->edit(false)->id]);     // e-mail niepotwierdzony
        $this->photo(['animal_edit_id' => $this->edit(true, 'rejected')->id]);

        $ids = app(PhotoEmbeddingService::class)->pendingQuery($this->mv)->pluck('id')->all();

        $this->assertSame([$verified->id, $approved->id], $ids);
    }

    public function test_worker_zapisuje_wektory_i_bledy_a_kolejne_uruchomienie_je_pomija(): void
    {
        $a = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $b = $this->photo(['animal_id' => $this->animal('approved')->id]);

        Process::fake(['*' => Process::result($this->pythonOutput([$b->id, $a->id], errors: [$a->id]))]);

        $this->artisan('match:embed')->assertSuccessful();

        $ok = PhotoEmbedding::where('photo_id', $b->id)->first();
        $this->assertSame($this->mv, $ok->model_version);
        $this->assertSame(768, $ok->dim);
        $this->assertCount(768, $ok->floats());
        $this->assertEqualsWithDelta(0.036, $ok->floats()[0], 1e-6);

        $bad = PhotoEmbedding::where('photo_id', $a->id)->first();
        $this->assertNull($bad->vector);
        $this->assertStringContainsString('cannot identify', $bad->error);

        // ** Python dostał ścieżki względne, parametry regulacji i zmienne środowiskowe
        Process::assertRan(function (PendingProcess $process) use ($a) {
            return in_array('--nice', $process->command, true)
                && in_array('--max-seconds', $process->command, true)
                && str_contains($process->input, '"path":"'.$a->path.'"')
                && $process->environment['OMP_NUM_THREADS'] === (string) config('noskiem_match.queue.threads');
        });

        // ** Wznawialność: policzone i błędne nie wracają do kolejki...
        $this->assertSame(0, app(PhotoEmbeddingService::class)->pendingQuery($this->mv)->count());
        // ...chyba że jawnie --retry-failed
        $this->assertSame([$a->id], app(PhotoEmbeddingService::class)->pendingQuery($this->mv, true)->pluck('id')->all());
    }

    public function test_worker_zostawia_znaczniki_dla_panelu_a_ponow_bledne_wraca_zdjecia_do_kolejki(): void
    {
        $a = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $b = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $status = app(MatchStatus::class);

        // ** Przed pierwszym uruchomieniem — brak "pulsu" = scheduler podejrzany
        $this->assertTrue($status->snapshot()['scheduler_stale']);

        Process::fake(['*' => Process::result($this->pythonOutput([$b->id, $a->id], errors: [$a->id]))]);
        $this->artisan('match:embed')->assertSuccessful();

        $s = $status->snapshot();
        $this->assertFalse($s['scheduler_stale']);
        $this->assertNull($s['running']);
        $this->assertSame(1, $s['last_batch']['processed']);
        $this->assertSame(1, $s['last_batch']['failed']);
        $this->assertSame(['pending' => 0, 'failed' => 1, 'done' => 1], array_intersect_key($s['queue'], array_flip(['pending', 'failed', 'done'])));

        // ** Ponów błędne — zdjęcie z błędem wraca do zwykłej kolejki, policzone zostaje
        $this->assertSame(1, $status->retryFailed());
        $this->assertSame([$a->id], app(PhotoEmbeddingService::class)->pendingQuery($this->mv)->pluck('id')->all());

        // ** Pauza też zostawia "puls" — cron działa, tylko worker czeka
        $this->travel(10)->minutes();
        config(['noskiem_match.queue.paused' => true]);
        $this->artisan('match:embed')->assertSuccessful();
        $this->assertFalse($status->snapshot()['scheduler_stale']);
    }

    public function test_awaria_procesu_oznacza_winowajce_i_zachowuje_policzone(): void
    {
        $first = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $killer = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $rest = $this->photo(['animal_id' => $this->animal('approved')->id]);

        // ** Kolejność kolejki: od najnowszych — rest, killer, first. Proces pada na "killer"
        Process::fake(['*' => Process::result($this->pythonOutput([$rest->id], done: false), 'Killed', 137)]);

        $this->artisan('match:embed')->assertFailed();

        $this->assertNotNull(PhotoEmbedding::where('photo_id', $rest->id)->value('vector'));
        $this->assertStringContainsString('Proces przerwany', PhotoEmbedding::where('photo_id', $killer->id)->value('error'));
        $this->assertFalse(PhotoEmbedding::where('photo_id', $first->id)->exists());
    }

    public function test_awaria_przy_ladowaniu_modelu_nie_obciaza_zdjec(): void
    {
        $this->photo(['animal_id' => $this->animal('approved')->id]);

        Process::fake(['*' => Process::result('', 'ModuleNotFoundError: torch', 1)]);

        $this->artisan('match:embed')->assertFailed();

        $this->assertSame(0, PhotoEmbedding::count());
    }

    public function test_wylaczony_modul_pauza_i_okno_godzin_blokuja_worker(): void
    {
        $this->photo(['animal_id' => $this->animal('approved')->id]);
        Process::fake();

        config(['noskiem_match.enabled' => false]);
        $this->artisan('match:embed')->assertSuccessful();

        config(['noskiem_match.enabled' => true, 'noskiem_match.queue.paused' => true]);
        $this->artisan('match:embed')->assertSuccessful();

        Process::assertNothingRan();
        $this->assertSame(0, PhotoEmbedding::count());

        // ** Okno godzin przez północ
        $svc = app(PhotoEmbeddingService::class);
        $tz = 'Europe/Warsaw';
        $this->assertTrue($svc->withinHours('22:00-06:00', CarbonImmutable::parse('2026-11-07 23:30', $tz)));
        $this->assertTrue($svc->withinHours('22:00-06:00', CarbonImmutable::parse('2026-11-07 05:59', $tz)));
        $this->assertFalse($svc->withinHours('22:00-06:00', CarbonImmutable::parse('2026-11-07 12:00', $tz)));
        $this->assertTrue($svc->withinHours('09:00-17:00', CarbonImmutable::parse('2026-11-07 09:00', $tz)));
        $this->assertFalse($svc->withinHours('09:00-17:00', CarbonImmutable::parse('2026-11-07 17:00', $tz)));
    }

    public function test_niezgodna_wersja_modelu_przerywa_bez_zapisu(): void
    {
        $p = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $out = str_replace($this->mv, 'inny-model-v9', $this->pythonOutput([$p->id]));
        Process::fake(['*' => Process::result($out)]);

        $this->expectException(\RuntimeException::class);
        $this->withoutExceptionHandling()->artisan('match:embed');
    }

    public function test_usuniecie_zdjecia_usuwa_wektor(): void
    {
        $p = $this->photo(['animal_id' => $this->animal('approved')->id]);
        PhotoEmbedding::create(['photo_id' => $p->id, 'model_version' => $this->mv, 'vector' => pack('g*', 1.0), 'dim' => 1]);

        $p->delete();

        $this->assertSame(0, PhotoEmbedding::count());
    }
}

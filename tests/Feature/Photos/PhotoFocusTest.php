<?php

namespace Tests\Feature\Photos;

use App\Filament\Resources\AnimalResource\Pages\ViewAnimal;
use App\Models\Photo;
use App\Models\User;
use App\Services\PhotoEmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Matching\MatchingFixtures;
use Tests\TestCase;

// ---------------------------
// Kadrowanie miniaturek wokół zwierzaka (photos.focus / focus_manual).
// Python podstawiony przez Process::fake - format JSONL jak noskiem_match/cli.py.
// ---------------------------
class PhotoFocusTest extends TestCase
{
    use MatchingFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
    }

    // ** Zdjęcie pionowe 1000x2000 (miniaturka kwadratowa widzi połowę wysokości)
    private function portrait(array $attrs = []): Photo
    {
        return new Photo(['path' => 'photos/x.jpg', 'width' => 1000, 'height' => 2000] + $attrs);
    }

    public function test_bez_detekcji_kadr_nieco_powyzej_srodka(): void
    {
        $this->assertSame('50% 30%', $this->portrait()->objectPosition());
    }

    public function test_male_zwierze_u_gory_trafia_na_srodek_miniaturki(): void
    {
        // Ramka 20%-30% wysokości, środek 25% -> wycinek od 0% (dosunięty do góry)
        $photo = $this->portrait(['focus' => [0.3, 0.2, 0.7, 0.3]]);

        $this->assertSame('50% 0%', $photo->objectPosition());

        // Ramka w dolnej części: środek 70%, widoczne 50% -> wycinek 45%-95% -> pozycja 90%
        $photo->focus = [0.3, 0.6, 0.7, 0.8];
        $this->assertSame('50% 90%', $photo->objectPosition());
    }

    public function test_wysokie_zwierze_pokazuje_gore_ramki_z_glowa(): void
    {
        // Ramka 30%-95% (nie mieści się w 50%) -> górna krawędź z zapasem 4%: 26% / 50% = 52%
        $photo = $this->portrait(['focus' => [0.1, 0.3, 0.9, 0.95]]);

        $this->assertSame('50% 52%', $photo->objectPosition());
    }

    public function test_punkt_moderatora_ma_pierwszenstwo_przed_detekcja(): void
    {
        $photo = $this->portrait(['focus' => [0.1, 0.6, 0.9, 0.95]]);
        $photo->setManualFocus(0.5, 0.35);

        // Punkt 35%, widoczne 50% -> wycinek od 10% -> 10% / 50% = 20%
        $this->assertSame('50% 20%', $photo->objectPosition());

        // Detekcja nie nadpisuje punktu moderatora, a powrót do auto korzysta z ramki
        $photo->applyDetection([0.1, 0.6, 0.9, 0.95], [1000, 2000]);
        $this->assertSame('50% 20%', $photo->objectPosition());

        $photo->setManualFocus(null, null);
        $this->assertNull($photo->focus_manual);
        $this->assertSame('50% 100%', $photo->objectPosition());
    }

    public function test_zdjecie_poziome_kadruje_w_poziomie(): void
    {
        // 2000x1000, zwierzę po prawej (75%-95%) -> środek 85%, widoczne 50% -> 60%/50% = 100% (dosunięte)
        $photo = new Photo(['path' => 'x.jpg', 'width' => 2000, 'height' => 1000, 'focus' => [0.75, 0.2, 0.95, 0.9]]);

        $this->assertSame('100% 50%', $photo->objectPosition());
    }

    public function test_worker_zapisuje_ramke_zwierzecia_przy_liczeniu_wektora(): void
    {
        $photo = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $vec = base64_encode(pack('g*', ...array_fill(0, 768, 0.036)));

        Process::fake(['*' => Process::result(implode("\n", [
            json_encode(['event' => 'ready', 'model_version' => $this->mv]),
            json_encode(['event' => 'result', 'photo_id' => $photo->id, 'status' => 'ok', 'vector_b64' => $vec,
                'dim' => 768, 'crop_found' => true, 'box' => [0.1, 0.2, 0.6, 0.9], 'size' => [1080, 1920]]),
            json_encode(['event' => 'done']),
        ]))]);

        app(PhotoEmbeddingService::class)->run(collect([$photo]), $this->mv);

        $photo->refresh();
        $this->assertSame([0.1, 0.2, 0.6, 0.9], $photo->focus);
        $this->assertSame('auto', $photo->focus_source);
        $this->assertSame([1080, 1920], [$photo->width, $photo->height]);
    }

    public function test_moderator_ustawia_i_cofa_kadr_w_panelu(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $animal = $this->animal('approved');
        $photo = $this->photo(['animal_id' => $animal->id]);

        // ** Strona podglądu renderuje się z przyciskiem
        $this->actingAs($admin)->get('/admin/animals/'.$animal->id)->assertOk()->assertSee('Kadry miniaturek');

        // ** Okno akcji renderuje pole z podglądem miniaturki
        Livewire::actingAs($admin)
            ->test(ViewAnimal::class, ['record' => $animal->id])
            ->mountAction('photoFocus')
            ->assertMountedActionModalSee(['Podgląd miniaturki', 'Kadr domyślny (detekcja jeszcze nie ruszyła)']);

        Livewire::actingAs($admin)
            ->test(ViewAnimal::class, ['record' => $animal->id])
            ->callAction('photoFocus', data: ['focus' => ['p'.$photo->id => [0.4, 0.15]]])
            ->assertHasNoActionErrors();

        $this->assertSame([0.4, 0.15], $photo->refresh()->focus_manual);

        // ** Podgląd w panelu pokazuje miniaturkę z tym samym kadrem co strona
        $this->actingAs($admin)->get('/admin/animals/'.$animal->id)
            ->assertOk()
            ->assertSee('object-fit: cover', false)
            ->assertSee('object-position: '.$photo->objectPosition(), false);

        Livewire::actingAs($admin)
            ->test(ViewAnimal::class, ['record' => $animal->id])
            ->callAction('photoFocus', data: ['focus' => ['p'.$photo->id => null]]);

        $this->assertNull($photo->refresh()->focus_manual);
    }

    public function test_reczny_kadr_dziala_na_starszym_zdjeciu_bez_wymiarow(): void
    {
        Storage::fake('public');
        Storage::disk('public')->putFileAs('photos', UploadedFile::fake()->image('old.jpg', 1000, 2000), 'old.jpg');

        $admin = User::factory()->create(['is_admin' => true]);
        $animal = $this->animal('approved');
        $photo = $this->photo(['animal_id' => $animal->id]);
        // ** Zdjęcie sprzed kadrowania: plik jest, wymiarów w bazie brak
        $photo->forceFill(['path' => 'photos/old.jpg', 'width' => null, 'height' => null])->save();

        Livewire::actingAs($admin)
            ->test(ViewAnimal::class, ['record' => $animal->id])
            ->callAction('photoFocus', data: ['focus' => ['p'.$photo->id => [0.5, 0.35]]]);

        $photo->refresh();
        $this->assertSame([1000, 2000], [$photo->width, $photo->height]);
        $this->assertSame('50% 20%', $photo->objectPosition());
    }

    public function test_komenda_uzupelnia_kadry_starych_zdjec(): void
    {
        $found = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $empty = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $done = $this->photo(['animal_id' => $this->animal('approved')->id]);
        $done->update(['focus_source' => 'none']);

        Process::fake(['*' => Process::result(implode("\n", [
            json_encode(['event' => 'ready', 'model_version' => $this->mv]),
            json_encode(['event' => 'result', 'photo_id' => $found->id, 'status' => 'ok', 'box' => [0.2, 0.1, 0.8, 0.5], 'size' => [800, 1200]]),
            json_encode(['event' => 'result', 'photo_id' => $empty->id, 'status' => 'ok', 'box' => null, 'size' => [800, 600]]),
            json_encode(['event' => 'done']),
        ]))]);

        $this->artisan('photos:detect-focus')->assertSuccessful();

        // ** Do Pythona idą tylko zdjęcia bez ustalonego kadru
        Process::assertRan(fn ($process) => str_contains($process->input, '"photo_id":'.$found->id)
            && ! str_contains($process->input, '"photo_id":'.$done->id.','));

        $this->assertSame('auto', $found->refresh()->focus_source);
        $this->assertSame('none', $empty->refresh()->focus_source);
        $this->assertSame(800, $empty->width);
    }
}

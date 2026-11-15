<?php

namespace Tests\Feature;

use App\Http\Controllers\UnsubscribeController;
use App\Mail\AnimalDeletionConfirmed;
use App\Mail\NewSubmissionForModeration;
use App\Models\Animal;
use App\Models\ChipRegistration;
use App\Models\Message;
use App\Models\Sighting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Matching\MatchingFixtures;
use Tests\TestCase;

// ---------------------------
// Rezygnacja z subskrypcji = usunięcie danych powiązanych z adresem e-mail.
// Link w stopce i nagłówku maili, strona z listą i potwierdzeniem, usunięcie po POST.
// ---------------------------
class UnsubscribeTest extends TestCase
{
    use MatchingFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
        Storage::fake('public');
        Storage::fake('local');
    }

    // ** Wysyłka przez mailer "array" - w przeciwieństwie do Mail::fake() buduje pełną wiadomość z nagłówkami
    private function sendAndCapture($mailable, string $to): \Symfony\Component\Mime\Email
    {
        config(['mail.default' => 'array']);
        Mail::to($to)->send($mailable);

        return Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
    }

    private function sighting(array $attrs = []): Sighting
    {
        return Sighting::forceCreate(array_merge([
            'animal_id' => $this->animal('approved', ['contact_email' => 'ktos@example.com'])->id,
            'description' => 'Widziany w parku', 'date_seen' => '2026-11-10', 'location' => 'Park',
            'latitude' => 50.26, 'longitude' => 19.02, 'contact_name' => 'Jan', 'contact_email' => 'jan@example.com',
            'mod_status' => 'approved', 'edit_token' => (string) Str::uuid(),
        ], $attrs));
    }

    private function chip(string $email): ChipRegistration
    {
        Storage::disk('local')->put($path = 'chip-stickers/'.Str::random(8).'.jpg', 'jpg');

        return ChipRegistration::forceCreate([
            'chip_number' => '616093900'.random_int(100000, 999999), 'animal_name' => 'Reks',
            'species_id' => $this->dog->id, 'contact_phone' => '600123456', 'contact_email' => $email,
            'sticker_photo_path' => $path, 'mod_status' => 'approved', 'edit_token' => Str::random(64),
        ]);
    }

    public function test_user_mail_has_unsubscribe_header_and_footer_link(): void
    {
        $this->animal('approved', ['animal_name' => 'Burek']);

        $message = $this->sendAndCapture(new AnimalDeletionConfirmed('Burek', 1, 'Jan'), 'Jan@Example.com');

        // ** Nagłówek "<url>" - token losowy (szyfrowanie), więc wyciągamy go i otwieramy
        $header = $message->getHeaders()->get('List-Unsubscribe')->getBodyAsString();
        $this->assertMatchesRegularExpression('#^<'.preg_quote(url('/rezygnacja').'/', '#').'[A-Za-z0-9_-]+>$#', $header);
        $url = trim($header, '<>');

        $this->assertStringContainsString('Kliknij, aby zrezygnować z subskrypcji', $message->getHtmlBody());
        $this->assertStringContainsString($url, $message->getHtmlBody());

        // ** Bez one-click - rezygnacja usuwa dane, wymaga potwierdzenia na stronie
        $this->assertNull($message->getHeaders()->get('List-Unsubscribe-Post'));

        // ** Link prowadzi do danych adresata (adres znormalizowany do małych liter)
        $this->get($url)->assertOk()->assertSee('Ogłoszenia (1)', false);
    }

    public function test_moderator_mail_has_no_unsubscribe_link(): void
    {
        $message = $this->sendAndCapture(new NewSubmissionForModeration($this->edit()), 'moderator@example.com');

        $this->assertNull($message->getHeaders()->get('List-Unsubscribe'));
        $this->assertStringNotContainsString('zrezygnować z subskrypcji', $message->getHtmlBody());
    }

    public function test_page_lists_data_with_warning_and_deletes_nothing_on_get(): void
    {
        $mine = $this->animal('approved', ['animal_name' => 'Burek']);
        $this->animal('approved', ['animal_name' => 'Cudzy', 'contact_email' => 'inny@example.com']);
        $this->chip('jan@example.com');

        $this->get(UnsubscribeController::urlFor('jan@example.com'))
            ->assertOk()
            ->assertSee('bezpowrotne usunięcie z noskiem.org wszystkich ogłoszeń i zdjęć', false)
            ->assertSee($mine->generated_title)
            ->assertSee('Wpisy w bazie chipów (1)', false)
            ->assertSee('j***@example.com')
            ->assertDontSee('jan@example.com');

        $this->assertModelExists($mine);
    }

    public function test_requires_confirmation_checkbox(): void
    {
        $animal = $this->animal();
        $url = UnsubscribeController::urlFor('jan@example.com');

        $this->from($url)->post($url)
            ->assertRedirect($url)
            ->assertSessionHasErrors('confirm');

        $this->assertModelExists($animal);
    }

    public function test_erases_everything_for_email_and_keeps_other_data(): void
    {
        // ** Dane adresata - adres z inną wielkością liter też jego
        $animal = $this->animal();
        $animalPhoto = $this->photo(['animal_id' => $animal->id]);
        $upperAnimal = $this->animal('pending', ['contact_email' => 'JAN@example.com']);
        $submission = $this->edit(true, 'pending', ['animal_id' => null]);
        $submissionPhoto = $this->photo(['animal_edit_id' => $submission->id]);
        $sighting = $this->sighting();
        $sightingPhoto = $this->photo(['sighting_id' => $sighting->id]);
        $chip = $this->chip('jan@example.com');
        Message::forceCreate(['animal_id' => $this->animal('approved', ['contact_email' => 'ktos@example.com'])->id, 'name' => 'Jan', 'email' => 'jan@example.com', 'message' => 'Hej']);

        foreach ([$animalPhoto, $submissionPhoto, $sightingPhoto] as $photo) {
            Storage::disk('public')->put($photo->path, 'jpg');
        }

        // ** Cudze - zostaje
        $other = $this->animal('approved', ['contact_email' => 'inny@example.com']);
        $otherChip = $this->chip('inny@example.com');

        $this->post(UnsubscribeController::urlFor('jan@example.com'), ['confirm' => '1'])
            ->assertOk()
            ->assertSee('Gotowe');

        $this->assertModelMissing($animal);
        $this->assertModelMissing($upperAnimal);
        $this->assertModelMissing($submission);
        $this->assertModelMissing($sighting);
        $this->assertModelMissing($chip);
        $this->assertSame(0, Message::where('email', 'jan@example.com')->count());

        foreach ([$animalPhoto, $submissionPhoto, $sightingPhoto] as $photo) {
            Storage::disk('public')->assertMissing($photo->path);
        }
        Storage::disk('local')->assertMissing($chip->sticker_photo_path);

        $this->assertModelExists($other);
        $this->assertModelExists($otherChip);
        $this->assertTrue(Animal::where('contact_email', 'ktos@example.com')->exists());
    }

    public function test_tampered_token_returns_404(): void
    {
        $this->get('/rezygnacja/nieprawidlowy-token')->assertNotFound();
        $this->post('/rezygnacja/nieprawidlowy-token', ['confirm' => '1'])->assertNotFound();
    }

    public function test_empty_email_shows_nothing_to_delete(): void
    {
        $this->get(UnsubscribeController::urlFor('nikt@example.com'))
            ->assertOk()
            ->assertSee('Nie mamy żadnych ogłoszeń', false)
            ->assertDontSee('Zrezygnuj i usuń wszystko');
    }
}

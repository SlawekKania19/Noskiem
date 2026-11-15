<?php

namespace Tests\Feature;

use App\Filament\Widgets\Statistics\BlogTopPostsTable;
use App\Filament\Widgets\Statistics\StatisticsOverview;
use App\Filament\Widgets\Statistics\VoivodeshipTable;
use App\Models\Post;
use App\Models\User;
use App\Services\StatsReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\Matching\MatchingFixtures;
use Tests\TestCase;

// ---------------------------
// Podstawowe statystyki: dziennik zdarzeń (stat_events) zapisywany z hooków modeli,
// zestawienia StatsReport, licznik odsłon bloga i strona "Statystyki" w panelu.
// ---------------------------
class StatisticsTest extends TestCase
{
    use MatchingFixtures, RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpMatching();
    }

    private function events(string $type): int
    {
        return DB::table('stat_events')->where('type', $type)->count();
    }

    public function test_listing_lifecycle_is_logged_and_survives_deletion(): void
    {
        $animal = $this->animal();
        $this->assertSame(1, $this->events('animal_published'));

        $this->travel(5)->days();
        $animal->update(['mod_status' => 'resolved']);

        $resolved = DB::table('stat_events')->where('type', 'animal_resolved')->first();
        $this->assertSame('lost', $resolved->listing_status);
        $this->assertSame(5, (int) $resolved->days);

        // ** Ponowny zapis bez zmiany statusu nie dubluje zdarzenia
        $animal->update(['description' => 'Nowy opis']);
        $this->assertSame(1, $this->events('animal_resolved'));

        $animal->delete();

        $this->assertSame(1, $this->events('animal_deleted'));
        $this->assertSame(1, $this->events('animal_published'));
    }

    public function test_submission_counted_after_email_confirmation_and_rejection_logged(): void
    {
        $edit = $this->edit(verified: false, attrs: ['animal_id' => null]);
        $this->assertSame(0, $this->events('animal_submitted'));

        $edit->update(['email_verified_at' => now()]);
        $this->assertSame(1, $this->events('animal_submitted'));

        $edit->update(['mod_status' => 'rejected']);
        $this->assertSame(1, $this->events('animal_rejected'));

        // ** Edycja istniejącego ogłoszenia to nie nowe zgłoszenie
        $this->edit(attrs: ['animal_id' => $this->animal()->id]);
        $this->assertSame(1, $this->events('animal_submitted'));
    }

    public function test_report_counts_resolution_rate_by_published_listing(): void
    {
        $this->animal()->update(['mod_status' => 'resolved']);
        $this->animal();
        $this->animal('approved', ['status' => 'found']);

        $report = new StatsReport('all');

        $this->assertSame(['lost' => 2, 'found' => 1, 'total' => 3], $report->totals()['animal_published']);
        $this->assertSame(['published' => 2, 'resolved' => 1, 'rate' => 50.0], $report->resolution()['lost']);

        $silesia = $report->byVoivodeship()->firstWhere('name', 'śląskie');
        $this->assertSame(2, $silesia['lost']);
        $this->assertSame(1, $silesia['resolved']);
    }

    public function test_period_filter_skips_older_events(): void
    {
        $this->animal();
        DB::table('stat_events')->update(['occurred_at' => now()->subDays(60)]);
        $this->animal();

        $this->assertSame(1, (new StatsReport('30'))->totals()['animal_published']['total']);
        $this->assertSame(2, (new StatsReport('all'))->totals()['animal_published']['total']);
    }

    public function test_blog_view_counted_once_per_session_without_bots_and_staff(): void
    {
        $post = Post::factory()->create();

        $this->withHeader('User-Agent', self::BROWSER)->get(route('blog.show', $post))->assertOk();
        $this->withHeader('User-Agent', self::BROWSER)->get(route('blog.show', $post))->assertOk();

        $this->assertSame(1, (int) $post->fresh()->views_count);
        $this->assertSame(1, (int) DB::table('post_views')->where('post_id', $post->id)->value('views'));

        // ** Nowa sesja = nowa odsłona, tego samego dnia sumowana w jednym wierszu
        $this->flushSession();
        $this->withHeader('User-Agent', self::BROWSER)->get(route('blog.show', $post));
        $this->assertSame(2, (int) DB::table('post_views')->where('post_id', $post->id)->value('views'));

        // ** Bot i zalogowany - bez zmian
        $this->flushSession();
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get(route('blog.show', $post));
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->withHeader('User-Agent', self::BROWSER)->get(route('blog.show', $post));

        $this->assertSame(2, (int) $post->fresh()->views_count);
    }

    public function test_statistics_page_access(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/statystyki')
            ->assertOk()
            ->assertSee('Statystyki');

        $this->actingAs(User::factory()->create(['is_author' => true]))
            ->get('/admin/statystyki')
            ->assertForbidden();
    }

    public function test_widgets_render_with_data(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->animal()->update(['mod_status' => 'resolved']);
        $post = Post::factory()->create(['title' => 'Jak szukać kota']);
        DB::table('post_views')->insert(['post_id' => $post->id, 'date' => today()->toDateString(), 'views' => 7]);

        Livewire::test(StatisticsOverview::class, ['pageFilters' => ['period' => 'all']])
            ->assertSee('Opublikowane ogłoszenia')
            ->assertSee('100%');

        Livewire::test(VoivodeshipTable::class, ['pageFilters' => ['period' => 'all']])
            ->assertSee('śląskie');

        Livewire::test(BlogTopPostsTable::class, ['pageFilters' => ['period' => 'all']])
            ->assertSee('Jak szukać kota')
            ->assertSee('Odsłony w okresie: 7');
    }
}

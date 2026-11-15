<?php

namespace App\Services;

use App\Mail\AgentHelpRequest;
use App\Models\AgentArea;
use App\Models\AgentNotification;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

// ---------------------------
// Prośby o pomoc do Agentów noskiem (php artisan agents:notify, co godzinę).
// Bierze nowe, zatwierdzone ogłoszenia "Zaginął", dla każdego szuka agentów, których
// obszar działania (punkt + promień) je obejmuje, i wysyła JEDEN zbiorczy mail na agenta.
// - para agent + ogłoszenie dostaje mail najwyżej raz (agent_notifications)
// - autor ogłoszenia nie dostaje prośby o pomoc w sprawie własnego zwierzaka
// - ogłoszenie po przejściu dostaje agent_notified_at i więcej nie wraca
// ---------------------------

class AgentNotifier
{
    /**
     * @return array{listings:int, mails:int}
     */
    public function run(): array
    {
        if (! config('noskiem_agents.notify_enabled')) {
            return ['listings' => 0, 'mails' => 0];
        }

        $listings = Animal::query()
            ->where('status', 'lost')
            ->where('mod_status', 'approved')
            ->whereNull('agent_notified_at')
            ->where('created_at', '>=', now()->subDays((int) config('noskiem_agents.listing_max_age_days')))
            ->with(['photos' => fn ($q) => $q->whereNull('sighting_id')->orderByDesc('is_main')->orderBy('id')])
            ->orderBy('id')
            ->get();

        if ($listings->isEmpty()) {
            return ['listings' => 0, 'mails' => 0];
        }

        // ** [user_id => ['user' => User, 'items' => [[animal, area, distance], ...]]]
        $perAgent = [];

        foreach ($listings as $animal) {
            foreach ($this->agentsFor($animal) as [$area, $distance]) {
                $perAgent[$area->user_id]['user'] = $area->user;
                $perAgent[$area->user_id]['items'][] = [$animal, $area, $distance];
            }
        }

        $mails = 0;

        foreach ($perAgent as $entry) {
            // ** Błąd jednego maila nie blokuje pozostałych agentów
            try {
                $this->send($entry['user'], $entry['items']);
                $mails++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // ** Z pominięciem Eloquenta - bez ruszania updated_at i search_index
        Animal::whereKey($listings->modelKeys())->toBase()->update(['agent_notified_at' => now()]);

        return ['listings' => $listings->count(), 'mails' => $mails];
    }

    // ---------------------------
    // Agenci w zasięgu ogłoszenia: [[AgentArea (najbliższy obszar agenta), odległość km], ...]
    // W SQL tylko prostokąt o boku największego dozwolonego promienia (szybki, działa
    // na każdej bazie), dokładna odległość i własny promień obszaru - po stronie PHP
    // ---------------------------
    public function agentsFor(Animal $animal): Collection
    {
        $max = (float) config('noskiem_agents.max_radius_km');
        $dLat = $max / 111.0;
        $dLng = $max / (111.0 * max(0.01, cos(deg2rad($animal->latitude))));

        $already = AgentNotification::where('animal_id', $animal->id)->pluck('user_id')->all();
        $authorEmail = mb_strtolower(trim((string) $animal->contact_email));

        return AgentArea::query()
            ->whereBetween('latitude', [$animal->latitude - $dLat, $animal->latitude + $dLat])
            ->whereBetween('longitude', [$animal->longitude - $dLng, $animal->longitude + $dLng])
            ->whereHas('user', fn ($q) => $q->activeAgents()->where('agent_notify', true))
            ->when($already, fn ($q) => $q->whereNotIn('user_id', $already))
            ->with('user')
            ->get()
            ->map(fn (AgentArea $area) => [$area, $area->distanceTo($animal->latitude, $animal->longitude)])
            ->filter(fn (array $pair) => $pair[1] <= $pair[0]->radius_km)
            ->reject(fn (array $pair) => mb_strtolower($pair[0]->user->email) === $authorEmail)
            // ** Agent z kilkoma pasującymi obszarami - liczy się najbliższy
            ->sortBy(fn (array $pair) => $pair[1])
            ->unique(fn (array $pair) => $pair[0]->user_id)
            ->values();
    }

    protected function send(User $agent, array $items): void
    {
        Mail::to($agent->email, $agent->name)->send(new AgentHelpRequest(
            $agent,
            array_map(fn (array $i) => $this->item(...$i), $items),
            self::links($agent),
        ));

        // ** Zapis PO wysyłce - wpis w dzienniku znaczy, że mail naprawdę wyszedł
        foreach ($items as [$animal, , $distance]) {
            AgentNotification::firstOrCreate(
                ['user_id' => $agent->id, 'animal_id' => $animal->id],
                ['distance_km' => round($distance, 1)],
            );
        }
    }

    // ** Dane ogłoszenia do maila jako zwykła tablica - widok nie odpytuje bazy
    protected function item(Animal $animal, AgentArea $area, float $distance): array
    {
        $photo = $animal->photos->first();

        return [
            'title'          => $animal->generated_title,
            'url'            => route('animals.show', $animal),
            'photo_url'      => $photo ? asset('storage/'.$photo->path) : null,
            'photo_position' => $photo?->objectPosition() ?? '50% 50%',
            'location'       => $animal->location_text,
            'date'           => $animal->date_event?->format('d.m.Y'),
            'distance_km'    => round($distance, 1),
            'area_name'      => $area->name,
        ];
    }

    // ** Panel agenta (wymaga logowania) i wyłączenie maili (podpisany link, bez logowania)
    public static function links(User $agent): array
    {
        return [
            'panel'   => route('agent.panel'),
            'opt_out' => URL::signedRoute('agents.opt-out', ['user' => $agent->id]),
        ];
    }
}

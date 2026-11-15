<?php

namespace App\Http\Controllers;

use App\Models\AgentArea;
use App\Models\Animal;
use App\Models\Voivodeship;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// ---------------------------
// Panel Agenta noskiem (po zalogowaniu): dane konta, obszary działania (punkt + promień,
// zarządzane na mapie - resources/js/agent-areas.js), lista ogłoszeń z kliknięciem
// "Szukam" oraz sam przycisk "Szukam" na stronie ogłoszenia.
// ---------------------------

class AgentPanelController extends Controller
{
    public function show(Request $request)
    {
        $agent = $request->user();

        // ** Konto panelu (admin/moderator/autor) bez roli agenta nie ma tu czego szukać
        if (! $agent->is_agent) {
            return redirect('/admin');
        }

        return view('agents.panel', [
            'agent' => $agent->load('city'),
            'areas' => $agent->agentAreas()->orderBy('id')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'searched' => $agent->searchedAnimals()
                ->with(['photos', 'breed', 'city'])
                ->orderByPivot('created_at', 'desc')
                ->get(),
        ]);
    }

    public function update(Request $request)
    {
        $agent = $this->agent($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'voivodeship_id' => ['required', 'integer', 'exists:voivodeships,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('voivodeship_id', $request->input('voivodeship_id'))],
            'agent_notify' => ['nullable', 'boolean'],
        ], [], ['name' => 'imię', 'city_id' => 'miejscowość', 'voivodeship_id' => 'województwo']);

        $agent->update([...$data, 'agent_notify' => $request->boolean('agent_notify')]);

        return redirect()->route('agent.panel')->with('success', 'Zapisano zmiany.');
    }

    // ---------------------------
    // Obszary działania
    // ---------------------------
    public function storeArea(Request $request)
    {
        $agent = $this->agent($request);

        if ($agent->agentAreas()->count() >= config('noskiem_agents.max_areas')) {
            return back()->withErrors(['area' => 'Masz już maksymalną liczbę obszarów ('.config('noskiem_agents.max_areas').'). Usuń któryś, żeby dodać nowy.']);
        }

        $agent->agentAreas()->create($this->validateArea($request, withPoint: true));

        return redirect()->route('agent.panel')->with('success', 'Obszar dodany. Damy znać, gdy zaginie w nim zwierzak.');
    }

    public function updateArea(Request $request, AgentArea $area)
    {
        abort_unless($area->user_id === $this->agent($request)->id, 403);

        $area->update($this->validateArea($request, withPoint: false));

        return redirect()->route('agent.panel')->with('success', 'Obszar zapisany.');
    }

    public function destroyArea(Request $request, AgentArea $area)
    {
        abort_unless($area->user_id === $this->agent($request)->id, 403);

        $area->delete();

        return redirect()->route('agent.panel')->with('success', 'Obszar usunięty.');
    }

    // ---------------------------
    // Przycisk "Szukam" / "Już nie szukam" na stronie ogłoszenia - tylko aktywny agent
    // (potwierdzony e-mail, bez blokady) i tylko aktywne ogłoszenie "Zaginął"
    // ---------------------------
    public function toggleSearch(Request $request, Animal $animal)
    {
        $agent = $request->user();

        abort_unless($agent->isActiveAgent(), 403);
        abort_unless($animal->status === 'lost' && $animal->mod_status === 'approved', 404);

        $changes = $agent->searchedAnimals()->toggle([$animal->id => ['created_at' => now()]]);

        return back()->with('success', $changes['attached'] !== []
            ? 'Dziękujemy! Właściciel zobaczy, że ktoś szuka jego zwierzaka.'
            : 'Zapisane - nie szukasz już tego zwierzaka.');
    }

    protected function agent(Request $request)
    {
        abort_unless($request->user()->is_agent, 403);

        return $request->user();
    }

    // ** Punkt da się ustawić tylko przy dodawaniu; potem zmienia się nazwę i promień
    protected function validateArea(Request $request, bool $withPoint): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:60'],
            'radius_km' => ['required', 'integer', 'min:'.config('noskiem_agents.min_radius_km'), 'max:'.config('noskiem_agents.max_radius_km')],
            ...($withPoint ? [
                'latitude' => ['required', 'numeric', 'between:48.5,55.5'],
                'longitude' => ['required', 'numeric', 'between:13.5,24.5'],
            ] : []),
        ], [
            'latitude.required' => 'Zaznacz środek obszaru na mapie albo wyszukaj adres.',
            'latitude.between' => 'Obszar musi leżeć w Polsce.',
            'longitude.between' => 'Obszar musi leżeć w Polsce.',
        ], ['name' => 'nazwa', 'radius_km' => 'promień']);
    }
}

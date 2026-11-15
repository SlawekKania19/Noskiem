<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Model reprezentujący miasto, które może mieć wiele zwierząt.
// Ta sama nazwa może się powtarzać w województwie (np. kilkanaście "Dąbrowa") - to różne,
// realne miejscowości z różnymi kodami SYM. Rozróżniamy je gminą, powiatem i rodzajem
// miejscowości; flaga is_ambiguous mówi, czy nazwa wymaga takiego dopisku.
// ---------------------------

class City extends Model
{
    protected $fillable = [
        'teryt_sym',
        'name_pl',
        'name_en',
        'voivodeship_id',
        'kind',
        'gmina',
        'powiat',
    ];

    protected $casts = [
        'is_ambiguous' => 'boolean',
    ];

    // ** Flagę is_ambiguous liczymy sami - po zmianie nazwy/województwa (panel admina)
    // przeliczamy zarówno starą, jak i nową grupę o tej samej nazwie
    protected static function booted(): void
    {
        static::created(fn (City $city) => static::refreshAmbiguity($city->voivodeship_id, $city->name_pl));

        // ** W "updated" getOriginal() zwraca jeszcze wartości sprzed zapisu
        static::updated(function (City $city) {
            if (! $city->wasChanged(['name_pl', 'voivodeship_id'])) {
                return;
            }

            static::refreshAmbiguity($city->voivodeship_id, $city->name_pl);
            static::refreshAmbiguity($city->getOriginal('voivodeship_id'), $city->getOriginal('name_pl'));
        });

        static::deleted(fn (City $city) => static::refreshAmbiguity($city->voivodeship_id, $city->name_pl));
    }

    // ** Relacje

    public function voivodeship(): BelongsTo
    {
        return $this->belongsTo(Voivodeship::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    // ** Dopisek rozróżniający, np. "wieś, gm. Gnojnik, pow. brzeski" - tylko dla powtarzających się nazw
    protected function details(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->is_ambiguous) {
                return null;
            }

            return collect([
                $this->kind,
                $this->gmina ? 'gm. '.$this->gmina : null,
                $this->powiat ? 'pow. '.$this->powiat : null,
            ])->filter()->implode(', ') ?: null;
        });
    }

    // ** Nazwa do wyświetlania, np. "Dąbrowa (wieś, gm. Gnojnik, pow. brzeski)" albo po prostu "Kraków"
    protected function label(): Attribute
    {
        return Attribute::get(fn () => $this->details ? "{$this->name_pl} ({$this->details})" : $this->name_pl);
    }

    // ** Nazwa do geokodowania - przy powtarzającej się nazwie dokładamy gminę,
    // inaczej Nominatim zwróci dowolną "Dąbrowę" z województwa
    public function geocodeName(): string
    {
        return ($this->is_ambiguous && $this->gmina)
            ? "{$this->name_pl}, gmina {$this->gmina}"
            : $this->name_pl;
    }

    // ---------------------------
    // Ustawia is_ambiguous dla wszystkich miejscowości o danej nazwie w województwie.
    // ---------------------------
    public static function refreshAmbiguity(?int $voivodeshipId, ?string $name): void
    {
        if ($voivodeshipId === null || $name === null) {
            return;
        }

        $group = DB::table('cities')->where('voivodeship_id', $voivodeshipId)->where('name_pl', $name);

        (clone $group)->update(['is_ambiguous' => (clone $group)->count() > 1]);
    }

    // ---------------------------
    // Przelicza is_ambiguous dla całej tabeli (seeder, migracja).
    // Najpierw zerujemy wszystko, potem zaznaczamy grupy z więcej niż jedną miejscowością.
    // ---------------------------
    public static function recomputeAmbiguity(): void
    {
        DB::table('cities')->update(['is_ambiguous' => false]);

        DB::table('cities')
            ->select('voivodeship_id', 'name_pl')
            ->groupBy('voivodeship_id', 'name_pl')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('voivodeship_id')
            ->get()
            ->each(fn ($group) => DB::table('cities')
                ->where('voivodeship_id', $group->voivodeship_id)
                ->where('name_pl', $group->name_pl)
                ->update(['is_ambiguous' => true]));
    }
}

# noskiem_match — dopasowania po zdjęciach

Wektory zdjęć zwierząt (DINOv2 + kadrowanie DETR) do wyszukiwania podobnych ogłoszeń.
Python tylko liczy; kolejkę, zapis do bazy i całą logikę biznesową prowadzi Laravel.

## Podział odpowiedzialności
- **Laravel**: kolejka (zdjęcia bez wektora w `photo_embeddings`), regulacja
  (`config/noskiem_match.php`), zapis wektorów, później: wybór kandydatów, logi, maile.
- **Python** (`cli.py embed`): dostaje JSONL `{photo_id, path}` na stdin, zwraca JSONL
  z wektorami na stdout. Nie łączy się z bazą.

## Model
`config.json` — **jedno źródło `model_version`** (czyta je też PHP). Zmiana modelu,
kadrowania, progu detekcji czy marginesu = nowa `model_version` (stare wektory są
nieporównywalne, trzeba policzyć od nowa i powtórzyć kalibrację progów).
Logika 1:1 z `dog_eval.py` (sprawdzone na QA: wektory identyczne jak przy liczeniu paczką).

## Instalacja (hosting: Python 3.12, wspólny venv dla QA i produkcji)
Domyślny `python3` na hostingu to 3.9 — za stary (pillow 12+ i nowsze numpy wymagają 3.10+).
CloudLinux ma nowsze wersje w `/opt/alt/python3XX`; używamy 3.12.

    /opt/alt/python312/bin/python3 -m venv ~/venvs/noskiem_match312
    P=~/venvs/noskiem_match312/bin/python
    $P -m pip install --no-cache-dir --upgrade pip
    $P -m pip install --no-cache-dir torch==2.8.0 torchvision==0.23.0 --index-url https://download.pytorch.org/whl/cpu
    $P -m pip install --no-cache-dir -r requirements.txt
Venv ~1,1 GB, wagi modeli w `~/.cache/huggingface` ~0,6 GB (pobierane przy pierwszym
starcie, wspólne dla wszystkich venv). `--no-cache-dir` — bez kopii pobranych paczek w `~/.cache/pip`.

.env aplikacji:
    MATCH_ENABLED=true
    MATCH_PYTHON=/home/<user>/venvs/noskiem_match312/bin/python
    MATCH_HF_OFFLINE=true        # dopiero PO pierwszym pobraniu wag

Podgląd instalacji: `$P -m pip list` (wersje paczek), `du -sh ~/venvs/* ~/.cache/*` (miejsce).

### Aktualizacja paczek / nowy venv
Stawiamy NOWY venv obok starego, porównujemy wektory tych samych zdjęć w obu
(`cli.py embed` na kilku zdjęciach), przełączamy `MATCH_PYTHON`, stary usuwamy po kilku dniach.
Nawet bez zmiany modelu wektory mogą się minimalnie różnić (2026-11-08, przejście 3.9 → 3.12
i pillow 11.3 → 13.0: cos stary↔nowy 0,999–1,0 — inne dekodowanie części JPG). Wtedy
przeliczamy wszystkie wektory od nowa, żeby w bazie nie mieszały się dwa środowiska.

## Uruchamianie
    php artisan match:embed --dry-run               # ile czeka, czy worker teraz ruszy
    php artisan match:embed                         # jedna partia (scheduler robi to co minutę)
    php artisan match:embed --limit=500 --ignore-hours   # backfill (wznawialny)
    php artisan match:embed --retry-failed          # ponów zdjęcia z błędem
Diagnostyka bez Laravela:
    $P cli.py bench --root <storage/app/public> photos/a.jpg photos/b.jpg

## Regulacja kolejki (.env, patrz config/noskiem_match.php)
| Zmienna | Domyślnie | Co robi |
|---|---|---|
| MATCH_PAUSED | false | wstrzymuje przetwarzanie, kolejka czeka |
| MATCH_HOURS | (pusto) | okno pracy, np. `22:00-06:00` (czas PL) |
| MATCH_NICE | 10 | priorytet CPU (0–19, wyżej = niższy priorytet) |
| MATCH_THREADS | 1 | wątki torcha |
| MATCH_BATCH_LIMIT | 30 | maks. zdjęć na uruchomienie |
| MATCH_MAX_SECONDS | 240 | limit czasu uruchomienia (kończy po bieżącym zdjęciu) |

## Pomiary (QA, 2026-11-07, 1 wątek, nice 10)
- ładowanie modeli: ~2,3 s (offline), ~12 s gdy odpytuje Hugging Face
- jedno zdjęcie: 2,0–3,7 s (średnio ~2,7 s); 2 wątki: ~1,7 s
- RAM procesu: ~340 MB po załadowaniu, szczyt ~1,25–1,4 GB (DETR na zdjęciu ~1024 px)

## Ważne decyzje
- Do kolejki trafiają tylko zdjęcia zatwierdzonych ogłoszeń i zgłoszeń z potwierdzonym
  e-mailem (boty nie zajmują CPU). Zdjęcia `sightings` — na razie pomijane.
- Wektor przypięty do `photo_id` (nie ogłoszenia): przy zatwierdzeniu zdjęcie jest
  przepinane z `animal_edit_id` na `animal_id`, ID zdjęcia się nie zmienia.
- Awaria procesu w połowie partii: policzone zostają, pierwsze nieobsłużone zdjęcie
  dostaje błąd (żeby jedno "zabójcze" zdjęcie nie blokowało kolejki).
- `malloc_trim` po każdym zdjęciu — bez tego RAM rósł z każdym zdjęciem (fragmentacja).
- `torch.set_num_threads` jawnie — CloudLinux pokazuje 32 rdzenie serwera.
- Licencje: DINOv2, DETR, transformers, timm — Apache 2.0; torch, torchvision, numpy — BSD;
  pillow — HPND. Bez ultralytics (AGPL) i MegaDescriptor (CC-BY-NC).

## Wyszukiwanie (App\Services\PhotoMatchService, cli.py search)
- PHP: twarde filtry w SQL (przeciwny status, gatunek, promień km, opcjonalnie okno dat,
  wykluczenie własnego ogłoszenia i tego samego e-maila), wektory kandydatów strumieniowo
  do pliku binarnego float32, metadane rasa/kolory na stdin.
- Python (numpy): wynik zwierzęcia = max cos po parach zdjęć; progi na CZYSTYM wyniku
  obrazu; bonus rasa/kolor tylko do kolejności; pozycja liczona wyłącznie wśród
  pokazanych. Zwraca top `log_floor_n` (także poniżej progu) z `shown`/`position`.
- "Rasa nieznana" = brak danych (ani bonus, ani kara).
- Diagnostyka: `php artisan match:search --edit=ID [--all] [--json]`.
- Pomiar QA (Python 3.9, 1 wątek): 5 000 kandydatów × 3 zdjęcia — 125 ms, 74 MB;
  20 000 × 3 — 350 ms, 224 MB.

## Mail z kandydatami (App\Services\PhotoMatchNotifier, php artisan match:notify)
1. Potwierdzenie e-maila nowego zgłoszenia (ze zdjęciami, moduł włączony) → wpis
   w `match_requests` (status `queued`).
2. `match:embed` od razu liczy wektory zdjęć zgłoszenia (jeszcze przed moderacją).
3. `match:notify` (co minutę): czeka na **zatwierdzenie** przez moderatora → gdy wszystkie
   zdjęcia mają wektory → wyszukiwanie dla nowego `Animal` (z `moderation_logs`, bo
   `animal_edits.animal_id` zostaje null) → mail `MatchCandidatesFound` TYLKO gdy coś
   przekracza próg (`notified`), inaczej `no_matches`.
- Mail dopiero po zatwierdzeniu, żeby moderator decydował, czy autor coś dostanie
  (spam, odrzucone). Odrzucone → `skipped`, bez maila.
- Po zatwierdzeniu czeka na wektory maks. `MATCH_NOTIFY_TTL_HOURS` (72, liczone od
  zatwierdzenia) → `expired`. Nigdy niezmoderowane → `expired` po
  `MATCH_NOTIFY_PENDING_TTL_DAYS` (30). Błąd → 3 próby → `failed`.
- `MATCH_PAUSED` wstrzymuje też maile.

## Logi (do kalibracji — tego nie da się odtworzyć wstecz)
- `match_searches` — każde wyszukiwanie ze statusem ok (także bez wyników ponad progiem):
  zapytanie (edit/animal), `model_version`, `params` (progi, wagi, filtry), liczniki.
- `match_search_results` — top `log_floor_n` kandydatów: `score` (czysty obraz), `bonus`,
  `rank`, `level`, `shown`, `position` (na liście w mailu).
- `match_events` — `click` z maila (podpisany link `/dopasowania/{id}/klik/{pozycja}`):
  pozycja, wersja modelu, User-Agent (do odfiltrowania skanerów poczty). Bez IP.
  SŁABY sygnał — tylko log, nic nie steruje progami ani rankingiem.
- Bez kluczy obcych do ogłoszeń — log przetrwa usunięcie ogłoszenia (same ID, bez danych osobowych).
- Objętość: ~50 wierszy wyników na wyszukiwanie (przy 100 zgłoszeniach/dzień ~1,8 mln/rok).

Przykłady:
    -- Prawdziwa para (np. ogłoszenie oznaczone jako odnalezione dzięki innemu) — jaki miała wynik i czy była pokazana?
    SELECT s.id, s.model_version, r.score, r.rank, r.level, r.shown
      FROM match_search_results r JOIN match_searches s ON s.id = r.match_search_id
     WHERE s.query_animal_edit_id = ? AND r.animal_id = ?;
    -- Rozkład wyników pokazanych vs niepokazanych dla wersji modelu
    SELECT level, COUNT(*), MIN(score), AVG(score), MAX(score)
      FROM match_search_results r JOIN match_searches s ON s.id = r.match_search_id
     WHERE s.model_version = 'dinov2-base+detr-crop+cls-v1' GROUP BY level;

## Na później
- Zdarzenia "potwierdzone przez użytkownika" (przycisk "to ten sam" / "to nie ten") —
  jako osobne typy w `match_events`.
- Ponowne dopasowanie starszych ogłoszeń, gdy pojawia się nowe (Faza 3, z limitem maili).
- Zdjęcia zgłoszeń "widziałem" (sightings).

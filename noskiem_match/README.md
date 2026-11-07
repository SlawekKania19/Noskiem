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

## Instalacja (hosting: Python 3.9, wspólny venv dla QA i produkcji)
    python3 -m venv --without-pip ~/venvs/noskiem_match      # ensurepip na hostingu jest zepsuty
    curl -sS -o /tmp/get-pip.py https://bootstrap.pypa.io/pip/3.9/get-pip.py
    ~/venvs/noskiem_match/bin/python /tmp/get-pip.py
    P=~/venvs/noskiem_match/bin/python
    $P -m pip install torch==2.8.0 torchvision==0.23.0 --index-url https://download.pytorch.org/whl/cpu
    $P -m pip install -r requirements.txt
Venv ~1,1 GB, wagi modeli w `~/.cache/huggingface` ~0,6 GB (pobierane przy pierwszym starcie).

.env aplikacji:
    MATCH_ENABLED=true
    MATCH_PYTHON=/home/<user>/venvs/noskiem_match/bin/python
    MATCH_HF_OFFLINE=true        # dopiero PO pierwszym pobraniu wag

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

## TODO (kolejne etapy)
3. Integracja: po potwierdzeniu e-maila → kolejka → mail z kandydatami.
4. Logi wyszukiwań (top 50, także poniżej progu) i kliknięć.

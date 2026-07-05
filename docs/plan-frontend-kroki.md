# Plan prac — Frontend Noskiem.org

Małe kroki, każdy z osobnym promptem do wklejenia w VS Code (Claude Code). Po każdym kroku jest sposób na szybki test przed przejściem dalej. Kolejność zaprojektowana tak, żeby każdy krok dawał się uruchomić i zobaczyć w przeglądarce.

Stan wyjściowy (sprawdzony 2026-07-05): panel Filament i backend gotowe, ale `/` to wciąż domyślna strona Laravel Breeze, `/animals` zwraca JSON zamiast widoku, a `/animals/create` odwołuje się do widoku który nie istnieje.

---

## Krok 0 — Instalacja Leaflet

**Co robimy:** dodajemy Leaflet do projektu (npm), bez użycia w kodzie jeszcze.

**Test:** `npm run build` przechodzi bez błędów, w `node_modules` jest folder `leaflet`.

**Prompt:**
```
Zainstaluj pakiet leaflet (npm install leaflet) i dodaj import CSS + JS leafleta
w resources/js/app.js oraz resources/css/app.css. Nie twórz jeszcze żadnej mapy
w widokach — tylko przygotuj bibliotekę do użycia. Uruchom npm run build i
upewnij się, że przechodzi bez błędów.
```

---

## Krok 1 — Layout główny (navbar + footer)

**Co robimy:** nowy layout Blade dla części publicznej (`resources/views/layouts/public.blade.php`) z navbarem i footerem wg Figmy — osobny od layoutu Breeze/Filament.

**Test:** stwórz tymczasowo dowolną testową trasę renderującą ten layout i sprawdź w przeglądarce, czy navbar/footer wyglądają zgodnie z Figmą (desktop i mobile).

**Prompt:**
```
Stwórz nowy layout resources/views/layouts/public.blade.php dla publicznej
części Noskiem.org (osobny od layoutu Breeze). Ma zawierać navbar i footer
wg projektu Figma — komponenty "Navbar" (node 22:211) i "Footer" (node 22:307)
z pliku https://www.figma.com/design/cEtR0awrPfm553RxVm90ix/Noskiem.pl-—-Projekt-strony.
Uwzględnij też Bottom Nav dla mobile (node 22:95), widoczny tylko na małych
ekranach. Użyj TailwindCSS zgodnie z konwencją projektu. Layout ma mieć
@yield lub {{ $slot }} na treść strony. Skomentuj kod po polsku zgodnie
z zasadami z CLAUDE.md.
```

---

## Krok 2 — Strona główna (statyczny szkielet, bez danych)

**Co robimy:** podmieniamy `welcome.blade.php` / trasę `/` na nowy widok strony głównej z hero section i switcherem Szukam/Znalazłem — na razie bez prawdziwych danych z bazy (możesz użyć danych przykładowych albo pustej listy).

**Test:** wejdź na `/` w przeglądarce, sprawdź przełączanie Szukam/Znalazłem i responsywność (mobile).

**Prompt:**
```
Zmień trasę GET / w routes/web.php tak, żeby renderowała nowy widok
resources/views/home.blade.php zamiast welcome.blade.php, używając layoutu
layouts/public.blade.php. Zbuduj Hero Section i switcher Szukam/Znalazłem
wg Figmy: desktop node 22:226 (hero) i 22:229 (switcher) dla stanu "Szukam",
mobile node 22:2. Switcher ma przełączać widoczność sekcji (Alpine.js x-data,
bez przeładowania strony). Na razie nie podłączaj prawdziwych ogłoszeń z bazy —
zostaw miejsce (placeholder) na listę kart, to zrobimy w kolejnym kroku.
```

---

## Krok 3 — Lista ogłoszeń jako widok Blade (zamiast JSON)

**Co robimy:** `AnimalController@index` ma zwracać widok z kartami ogłoszeń zamiast surowego JSON.

**Test:** wejdź na `/animals` — powinna wyświetlić się lista kart (miniaturka, tytuł, lokalizacja, status) z danych z seedera.

**Prompt:**
```
Zmień AnimalController@index (app/Http/Controllers/AnimalController.php) tak,
żeby zamiast zwracać JSON renderował widok resources/views/animals/index.blade.php
z layoutem layouts/public.blade.php. Stwórz komponent karty ogłoszenia wg Figmy —
"Karta ogłoszenia (Burek)" node 22:275 z pliku
https://www.figma.com/design/cEtR0awrPfm553RxVm90ix/Noskiem.pl-—-Projekt-strony
— zawierający miniaturkę zdjęcia (główne z tabeli photos), tytuł, lokalizację
i status. Wykorzystaj desktop node 33:130 i mobile node 33:2 jako referencję
układu listy. Zachowaj obecną logikę zapytania (mod_status = approved,
relacje species/breed/voivodeship/city/photos). Skomentuj po polsku wg CLAUDE.md.
```

---

## Krok 4 — Filtry na liście ogłoszeń

**Co robimy:** dodajemy filtrowanie po gatunku, województwie, mieście, statusie, rasie i kolorze.

**Test:** na `/animals` przetestuj każdy filtr osobno i sprawdź czy lista się zawęża poprawnie; sprawdź reset filtrów.

**Prompt:**
```
Dodaj filtrowanie do listy ogłoszeń na /animals: gatunek (species), województwo
(voivodeship), miasto (city), status (lost/found), rasa (breed), kolor dominujący
(color). Filtry jako formularz GET nad listą, zgodny z układem z Figmy
(desktop node 33:130, mobile node 33:2). Rozbuduj AnimalController@index
o obsługę query params i przekaż listy słownikowe (species, voivodeships,
breeds, colors) do widoku. Zachowaj wybrane filtry w polach formularza po
przeładowaniu strony.
```

---

## Krok 5 — Szczegóły ogłoszenia (bez mapy)

**Co robimy:** widok pojedynczego ogłoszenia — galeria zdjęć, opis, dane kontaktowe (telefon ukryty), przycisk "Zgłoś, że widziałem".

**Test:** kliknij kartę na liście, sprawdź czy otwiera się `/animals/{id}` z pełnymi danymi i czy numer telefonu jest ukryty domyślnie.

**Prompt:**
```
Zmień AnimalController@show tak, żeby renderował widok
resources/views/animals/show.blade.php zamiast JSON. Zbuduj układ wg Figmy
(sekcja szczegółów ogłoszenia w projekcie desktop 22:210/22:318 i mobile
22:2/22:106 — użyj get_design_context z Figma MCP na tych node'ach żeby
zobaczyć dokładny układ sekcji "szczegóły"). Zawrzyj: galerię zdjęć z tabeli
photos, opis, datę zdarzenia, znaki szczególne, dane kontaktowe — telefon
ukryty za przyciskiem "Pokaż numer" (ochrona przed botami), formularz
"napisz wiadomość" (zapis do tabeli messages), przycisk "Zgłoś, że widziałem"
linkujący do formularza sightings (na razie może prowadzić donikąd — dodamy
później). Na razie pomiń mapę — to osobny krok.
```

---

## Krok 6 — Mapa Leaflet w szczegółach ogłoszenia

**Co robimy:** dodajemy mapę pokazującą lokalizację zdarzenia na widoku szczegółów.

**Test:** na `/animals/{id}` powinna pojawić się mapa OSM z pinezką w miejscu z `latitude`/`longitude`.

**Prompt:**
```
Dodaj mapę Leaflet do resources/views/animals/show.blade.php, pokazującą
pinezkę w punkcie animal.latitude/longitude na podkładzie OpenStreetMap.
Referencja layoutu: "Mapa lokalizacji" node 22:238 z Figmy. Mapa ma być
tylko do odczytu (bez możliwości przesuwania pinezki). Użyj Leaflet
zainstalowanego w Kroku 0 — zainicjalizuj mapę w osobnym pliku JS
(resources/js/animal-map.js) importowanym w app.js, żeby nie duplikować
kodu między widokiem szczegółów a formularzem (przyda się w Kroku 8).
```

---

## Krok 7 — Formularz zgłoszenia (pola tekstowe, bez zdjęć i mapy)

**Co robimy:** naprawiamy brakujący widok `animals.create` — na razie same pola tekstowe/select, bez uploadu zdjęć i bez interaktywnej mapy (współrzędne wpisywane ręcznie albo pomijane).

**Test:** wejdź na `/animals/create`, wypełnij i wyślij formularz — sprawdź czy trafia do tabeli `animal_edits` ze statusem `pending` (np. przez Filament albo tinker).

**Prompt:**
```
Stwórz resources/views/animals/create.blade.php — to jest widok który dziś
nie istnieje mimo że AnimalEditController@create już go wywołuje (view('animals.create')),
przez co /animals/create rzuca błędem. Zbuduj formularz wg pól z walidacji
w AnimalEditController@store (app/Http/Controllers/AnimalEditController.php):
status, title, description, animal_name, ident_marks, chip_present + chip_number,
species_id, breed_id, date_event, voivodeship_id, city_id, location_text,
latitude, longitude, contact_name, contact_email, contact_phone. Layout wg
Figmy: desktop node 37:101, mobile node 37:2. Na razie latitude/longitude
jako zwykłe pola liczbowe (mapę interaktywną dodamy w kolejnym kroku),
a upload zdjęć pomiń całkowicie — to też osobny krok. Dodaj walidację
błędów po stronie widoku (@error) i komunikat sukcesu po przekierowaniu.
```

---

## Krok 8 — Wybór lokalizacji na mapie + upload zdjęć w formularzu

**Co robimy:** zamieniamy pola latitude/longitude na klikalną mapę Leaflet i dodajemy upload maks. 6 zdjęć.

**Test:** kliknij punkt na mapie w formularzu — pola lat/lng powinny się same wypełnić. Dodaj 6 zdjęć i sprawdź czy 7. się nie da dodać. Wyślij formularz i sprawdź czy pliki trafiają do `storage/app/public` (nie do `public/`) i czy rekordy `photos` się tworzą.

**Prompt:**
```
Rozbuduj resources/views/animals/create.blade.php: zamień pola latitude/longitude
na interaktywną mapę Leaflet (kliknięcie ustawia pinezkę i wypełnia ukryte pola
lat/lng) — wykorzystaj resources/js/animal-map.js z Kroku 6, dodaj tam funkcję
trybu edycji dla formularza. Dodaj pole uploadu zdjęć z limitem 6 plików
i podglądem miniaturek przed wysłaniem. Po stronie backendu: rozbuduj
AnimalEditController@store o walidację zdjęć (max 6, obrazy) i zapis plików
do storage/app/public (NIE do public/), z utworzeniem rekordów w tabeli photos
powiązanych z nowym zgłoszeniem. Zachowaj istniejącą logikę mod_status=pending
i edit_token.
```

---

## Krok 9 — Formularz edycji ogłoszenia (token)

**Co robimy:** widok `animals.edit` — edycja istniejącego ogłoszenia przez link z tokenem.

**Test:** z panelu Filament lub bazy weź `edit_token` istniejącego ogłoszenia, wejdź na `/animals/{id}/edit?token=...`, zmień dane i sprawdź czy trafiają do `animal_edits` ze statusem `pending`, oraz że bez tokenu (lub złym) dostajesz błąd 403.

**Prompt:**
```
Stwórz resources/views/animals/edit.blade.php analogiczny do create.blade.php
z Kroku 7-8, ale wypełniony danymi istniejącego ogłoszenia ($animal przekazywane
z AnimalEditController@edit). Uwzględnij ukryte pole z tokenem przekazywane
dalej w formularzu do AnimalEditController@update. Sprawdź działanie ścieżki
błędnego/brakującego tokenu (już obsłużone w kontrolerze — upewnij się że
przekazujesz token w URL wysyłanego formularza).
```

---

## Krok 10 — Podłączenie strony głównej do prawdziwych danych

**Co robimy:** homepage z Kroku 2 pokazuje realne, najnowsze zatwierdzone ogłoszenia zamiast placeholdera.

**Test:** na `/` sprawdź czy sekcje Szukam/Znalazłem pokazują faktyczne najnowsze ogłoszenia z bazy, każda karta linkuje do `/animals/{id}`.

**Prompt:**
```
Podłącz resources/views/home.blade.php do prawdziwych danych: dodaj metodę
(np. w nowym HomeController albo bezpośrednio w routes/web.php) pobierającą
kilka najnowszych zatwierdzonych ogłoszeń (mod_status=approved) osobno dla
status=lost i status=found, i wyświetl je w sekcjach Szukam/Znalazłem
z komponentu karty ogłoszenia stworzonego w Kroku 3. Dodaj link "Zobacz
wszystkie" prowadzący do /animals z odpowiednim filtrem statusu.
```

---

## Po zakończeniu wszystkich kroków — warto sprawdzić

- Czy wszystkie trasy z routes/web.php faktycznie renderują się bez błędu (ręczne przejście po każdej)
- Czy zdjęcia nigdzie nie trafiają do public/ (tylko storage/app/public)
- Czy Cloudflare Turnstile jest podłączony do formularza (jeśli jeszcze nie — osobny, kolejny krok poza tą listą)
- Czy layouty działają poprawnie na mobile (Figma ma osobne mobile frame'y dla każdego ekranu)

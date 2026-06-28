# Zasady komentowania kodu

Komentarze w języku **polskim**, zwięzłe ale konkretne. Komentujemy wszystko co może być trudniejsze do odczytania.

```
// ---------------------------
// Główny komentarz (funkcja, klasa, większy blok kodu)
// ---------------------------

// ** Mniejszy blok funkcjonalny

// Komentarz inline
```

---

# Noskiem.pl — kontekst projektu

Platforma do zgłaszania i wyszukiwania zaginionych, znalezionych i widzianych zwierząt.
Slogan: **„Znajdziemy go noskiem"**
Źródło: mapa myśli Whimsical (https://whimsical.com/6vveemfMGAPZ91EvBwzoE2)

## Zespół

- **Ewelina** (współzałożycielka) — pomysłodawczyni, produkt, UX, komunikacja, relacje ze sponsorami komercyjnymi, budowanie społeczności (FB, social media). Prowadzi markę kreatywną LUUA.
- **Sławek** (współzałożyciel) — architektura techniczna, integracje API, AI, panel dla miast, relacje z samorządami.
- **Programista zewnętrzny** — planowany jednorazowo w Fazie 3 (AI Vision API + panel dla miast, ~500–1000 zł).

## Model biznesowy

Platforma bezpłatna dla użytkowników. Trzy filary przychodów:

**1. Sponsoring komercyjny** — kliniki weterynaryjne, sklepy zoologiczne (Maxi Zoo, Zooplus), producenci karm (Royal Canin, Purina, Brit), firmy chipowania/GPS, schroniska i fundacje. Ceny: 100–2000 zł/mies.

**2. Partnerstwa z samorządami** — dashboard dla miast z mapą błąkających się zwierząt, statystykami i raportami. Pakiety:
- Podstawowy: 2 000–5 000 zł/rok
- Standard: 5 000–12 000 zł/rok
- Premium: 12 000–25 000 zł/rok
- Województwo: 30 000–60 000 zł/rok

Argument ROI: jeśli 50 zwierząt rocznie wraca do właściciela zamiast do schroniska → oszczędność ~50 000 zł przy koszcie platformy 5–12 tys. zł/rok.

**3. Granty i dofinansowania** — granty miejskie na cyfryzację, programy UE (smart city, Animal Welfare), potencjalny partner: **AI Łukasiewicz (Katowice)** — Ewelina odwiedziła instytucję, rozważa pilotaż panelu dla miast z Katowicami jako pierwszym miastem partnerskim.

## Prognoza przychodów

| Etap | Użytkownicy | Przychód/mies. |
|---|---|---|
| Start (mies. 1–6) | 0–1 000 | 0 zł |
| Wczesny (mies. 6–9) | 1 000–5 000 | 500–2 000 zł |
| Wzrost (mies. 9–12) | 5 000–20 000 | 3 000–8 000 zł |
| Dojrzały (rok 2) | 20 000–50 000 | 8 000–20 000 zł |
| Skala (rok 3+) | 100 000+ | 20 000–50 000 zł |

## Kontekst rynkowy

Na rynku polskim brak centralnej platformy — jedynie chaotyczne grupy FB, OLX i oddzielne strony schronisk. Globalny benchmark: **Petco Love Lost (USA)** — AI dopasowanie twarzy zwierząt, 170 000+ odnalezionych, 3 000+ schronisk. Żadne porównywalne rozwiązanie nie działa w Polsce ani Europie.

## Fazy produktu (perspektywa biznesowa)

- **Faza 1 — MVP** (aktualna): formularz zgłoszenia, baza ogłoszeń z filtrami, powiadomienia email
- **Faza 2 — Rozwój**: mapa (Leaflet/OSM — już zdecydowane), AI opisy ze zdjęcia (GPT-4o Vision)
- **Faza 3 — AI**: automatyczne dopasowania ogłoszeń, panel dla miast, SMS powiadomienia
- **Faza 4 — Skala**: sponsorzy, umowy z miastami, skalowanie

## Stack technologiczny

- **Backend:** Laravel 11 + Filament Admin (panel admina)
- **Frontend:** TailwindCSS + Blade
- **Baza danych:** MySQL / MariaDB
- **Mapa:** OpenStreetMap + Leaflet JS (Google Maps odrzucono)
- **Spam:** Cloudflare Turnstile (darmowy)
- **Repo:** GitHub

## Parametry systemu

- Zasięg ogólnopolski
- Bez logowania — każdy może dodać ogłoszenie; edycja przez link wysłany e-mailem (token)
- Mapa: Leaflet zapisuje współrzędne (latitude/longitude) oraz nazwę najbliższego miasta/wsi
- Moderacja treści przed publikacją
- Trzy typy zgłoszeń: **Zaginął / Znaleziony / Widziany**
- Statusy ogłoszenia: `pending`, `approved`, `rejected`, `resolved`

## Baza danych

### Tabela `animals` (główna, ogłoszenia)
| Pole | Typ |
|---|---|
| id | bigint |
| title | string |
| description | text |
| date_event | date |
| location_text | string |
| latitude | decimal(10,7) |
| longitude | decimal(10,7) |
| species_id | bigint |
| breed_id | bigint |
| animal_name | string |
| chip | bool |
| chip_number | string |
| ident_marks | text |
| contact_name | string |
| contact_email | string |
| contact_phone | string |
| edit_token | string |
| mod_status | enum(pending, approved, rejected, resolved) |
| status | enum(lost, found) |
| city_id | bigint |
| voivodship_id | bigint |
| created | timestamp |
| updated | timestamp |

### Tabela `animals_pending`
Wersja ogłoszenia po edycji, przed ponownym zatwierdzeniem. Te same pola co `animals`.

### Tabela `photos`
| Pole | Typ |
|---|---|
| id | bigint |
| animal_id | bigint |
| path | string |
| is_main | bool |

### Tabela `sightings` (zgłoszenia „widziałem zwierzaka")
| Pole | Typ |
|---|---|
| id | bigint |
| animal_id | bigint |
| description | text |
| date_seen | date |
| location | string |
| latitude | decimal(10,7) |
| longitude | decimal(10,7) |
| special_marks | text |
| species | enum(cat, dog, other) |
| contact_name | string |
| contact_email | string |
| contact_phone | string |
| created | timestamp |

### Tabela `messages` (kontakt do właściciela ogłoszenia)
| Pole | Typ |
|---|---|
| id | bigint |
| animal_id | bigint |
| name | string |
| email | string |
| message | text |
| created | timestamp |

### Tabela `colors` (słownikowa)
| Pole | Typ |
|---|---|
| id | bigint |
| name | string |

### Pivot `animal_color`
| Pole | Typ |
|---|---|
| animal_id | bigint |
| color_id | bigint |

### Tabela `species` (gatunki)
| Pole | Typ |
|---|---|
| id | bigint |
| name_pl | string |
| name_en | string |

### Tabela `breeds` (rasy)
| Pole | Typ |
|---|---|
| id | bigint |
| breed_pl | string |
| breed_en | string |
| species_id | bigint |

### Tabela `voivodships` (województwa)
| Pole | Typ |
|---|---|
| id | bigint |
| name_pl | string |
| name_en | string |

### Tabela `cities`
| Pole | Typ |
|---|---|
| id | bigint |
| city_pl | string |
| city_en | string |

## Flow użytkownika

### Strona główna
- Podział na sekcje: **Poszukiwane** / **Widziane**
- Wyszukiwarka
- Filtry: gatunek / województwo / miasto-wieś / status / rasa / kolor dominujący
- Informacja: „Najpierw poszukaj, zanim dodasz ogłoszenie"

### Lista ogłoszeń
Każda karta: miniaturka, tytuł, lokalizacja, status

### Szczegóły ogłoszenia
- Galeria zdjęć
- Mapa lokalizacji
- Opis
- Kontakt:
  - Pisemny — wiadomość przez stronę
  - Telefoniczny — numer ukryty (ochrona przed botami)
- Przycisk „Zgłoś, że widziałem"
- Data zdarzenia

## Formularz zgłoszenia

Pola formularza (wypełniane przez użytkownika):
- Typ: Zaginął / Znaleziony / Widziany
- Dane kontaktowe
- Zdjęcia (max. 6)
- Lokalizacja — pole tekstowe + kliknięcie na mapie
- Znaki szczególne
- Kolory (wielokrotny wybór)
- Płeć
- Imię zwierzaka
- Chip (tak/nie) → jeśli tak: numer chipa

> Tytuł ogłoszenia jest generowany automatycznie — użytkownik go nie wpisuje.

## Moderacja (Filament)

- Lista ogłoszeń z filtrami: Oczekujące / Zatwierdzone / Odrzucone
- Podgląd zdjęć
- Zmiana statusu jednym kliknięciem
- Powiadomienie e-mail do właściciela po zatwierdzeniu
- Jeśli użytkownik edytuje ogłoszenie → wymagane ponowne zatwierdzenie
  - Wyjątek: zmiana statusu (np. „Odnaleziony") nie wymaga moderacji
  - Do czasu zatwierdzenia widoczna jest poprzednia wersja

## Panel admina

- Statystyki ogłoszeń według statusu
- CRUD ogłoszeń i zdjęć
- Moderacja
- Wiadomości (widok w panelu + powiadomienie e-mail do właściciela)
- Logi
- Ustawienia

## Bezpieczeństwo

- CSRF
- Walidacja formularzy po stronie serwera
- Ochrona przed spamem: Cloudflare Turnstile (darmowe)
- Rate limiting
- Tokeny edycji: szyfrowane i długie
- Zdjęcia przechowywane poza `public/` (katalog `storage`)
- XSS sanitization: Laravel Purifier

## AI (funkcje planowane)

- **Faza 2:** GPT-4o Vision — automatyczny opis zwierzęcia po wgraniu zdjęcia (rasa, kolor, wzory sierści, charakterystyczne cechy, wiek). Koszt: ~0,05–0,10 zł/zdjęcie. Wynik zapisywany jako tekst do bazy.
- **Faza 2:** Wczytywanie danych zwierzaka ze zdjęcia (automatyczne wypełnianie formularza)
- **Faza 3:** Automatyczne dopasowania ogłoszeń — porównywanie nowych zgłoszeń z bazą
- **Faza 3:** Przy zgłoszeniu „Widziany" — automatyczne wyszukiwanie podobnych zwierząt wśród poszukiwanych
- **Faza 3+:** Zaawansowane dopasowanie wizualne (dedykowany model, decyzja po Fazie 2)

Odrzucono: Google Vision AI — rozpoznaje gatunek ale nie rozróżnia umaszczenia ani cech indywidualnych.

## Plan prac

1. **Krok 1 — Szkielet:** instalacja Laravel, konfiguracja bazy, migracje, modele + relacje
2. **Krok 2 — Panel admina:** instalacja Filament, resource Animal, moderacja, upload zdjęć
3. **Krok 3 — Frontend:** lista ogłoszeń, szczegóły, formularz dodawania, mapa Leaflet
4. **Krok 4 — E-maile + tokeny**
5. **Krok 5 — Testy + optymalizacja**

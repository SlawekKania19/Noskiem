# Architektura i stos technologiczny — Noskiem.pl

## Ogólny opis systemu

Noskiem.pl to platforma webowa działająca w modelu SaaS, dostępna przez przeglądarkę internetową bez konieczności instalacji aplikacji. Umożliwia zgłaszanie i wyszukiwanie zaginionych, znalezionych oraz widzianych zwierząt na terenie całej Polski.

---

## Stos technologiczny

### Backend
**Laravel 11** — nowoczesny framework PHP klasy enterprise. Obsługuje logikę biznesową, routing, walidację formularzy, zarządzanie plikami i wysyłkę powiadomień. Wbudowany panel administracyjny oparty o **Filament** umożliwia moderację ogłoszeń i zarządzanie treścią.

### Frontend
**Blade** (silnik szablonów Laravel) + **Tailwind CSS** — responsywny interfejs użytkownika działający w przeglądarce, dostosowany do urządzeń mobilnych i desktopowych.

### Baza danych
**MySQL / MariaDB** — relacyjna baza danych przechowująca ogłoszenia, dane kontaktowe, słowniki gatunków i ras, lokalizacje oraz historię zgłoszeń.

### Przechowywanie zdjęć
Zdjęcia przesyłane przez użytkowników przechowywane są poza katalogiem publicznym serwera (`storage/`), z dostępem przez kontrolowany URL. Każde ogłoszenie może zawierać do 6 zdjęć.

### Mapy i geolokalizacja
**OpenStreetMap** + **Leaflet.js** — darmowa, open-source mapa interaktywna. Użytkownik wskazuje miejsce zdarzenia kliknięciem na mapie; system zapisuje współrzędne GPS (latitude/longitude) oraz najbliższą miejscowość.

### Powiadomienia
**E-mail** — powiadomienia transakcyjne: potwierdzenie zgłoszenia, informacja o moderacji (zatwierdzenie/odrzucenie), link do edycji ogłoszenia, wiadomości od innych użytkowników.

### Logowanie i autoryzacja
System nie wymaga rejestracji konta. Dostęp do edycji ogłoszenia odbywa się przez **unikalny token** wysyłany e-mailem do zgłaszającego. Panel administracyjny chroniony jest logowaniem z uprawnieniami.

### Ochrona przed spamem i nadużyciami
**Cloudflare Turnstile** (darmowy) zamiast klasycznej CAPTCHA. Dodatkowo: walidacja po stronie serwera, rate limiting, sanityzacja danych wejściowych (XSS), tokeny CSRF.

### Hosting
Aplikacja hostowana na serwerze VPS/cloud z systemem Linux, z obsługą PHP 8.x i MySQL. Statyczne zasoby serwowane przez Nginx.

### Repozytorium kodu
**GitHub** — wersjonowanie kodu, współpraca zespołowa, historia zmian.

---

## Moduł AI (planowany — Faza 2 i 3)

### Opis ze zdjęcia (Faza 2)
Integracja z **GPT-4o Vision (OpenAI API)** — po wgraniu zdjęcia zwierzaka system automatycznie generuje opis: gatunek, rasa, kolor, wzory sierści, charakterystyczne cechy, szacowany wiek. Opis zapisywany jest jako tekst w bazie i pomaga w wyszukiwaniu.

### Dopasowywanie ogłoszeń (Faza 3)
Algorytm porównujący nowe zgłoszenia z bazą istniejących ogłoszeń pod kątem podobieństwa wizualnego i opisowego. Przy zgłoszeniu „Widziany" system automatycznie wyszukuje zbliżone ogłoszenia wśród zwierząt uznanych za zaginione.

### Wyszukiwanie wizualne (Faza 3+)
Rozważane wdrożenie dedykowanego modelu dopasowania wizualnego (embedding twarzy zwierząt), analogicznie do rozwiązań stosowanych przez platformę Petco Love Lost (USA) — 170 000+ odnalezionych zwierząt.

---

## Architektura — schemat warstwowy

```
Użytkownik (przeglądarka)
        ↓
   Nginx (serwer WWW)
        ↓
   Laravel 11 (PHP)
   ├── Logika biznesowa
   ├── Filament Admin Panel
   ├── API wewnętrzne (formularze, moderacja)
   └── Integracje zewnętrzne:
       ├── OpenAI API (GPT-4o Vision) — AI opisy
       ├── Cloudflare Turnstile — antyspam
       └── SMTP (e-mail) — powiadomienia
        ↓
   MySQL / MariaDB (baza danych)
   └── Pliki: storage/ (zdjęcia)
```

---

## Skalowalność

Architektura umożliwia skalowanie horyzontalne (dodatkowe instancje aplikacji) oraz pionowe (zwiększenie zasobów serwera). W kolejnych fazach planowane jest wprowadzenie kolejkowania zadań (Laravel Queue) dla operacji AI i wysyłki powiadomień masowych.

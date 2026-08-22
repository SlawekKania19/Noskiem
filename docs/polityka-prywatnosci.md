# Polityka prywatności Noskiem.org

*Szkic roboczy — wersja 0.1, przygotowany [DATA]. Miejsca oznaczone `[DO UZUPEŁNIENIA]` wymagają decyzji lub danych od administratora przed publikacją. Dokument nie zastępuje konsultacji prawnej — przed publikacją zalecana jest weryfikacja przez prawnika/specjalistę RODO, szczególnie w części dotyczącej AI i transferu danych poza EOG.*

## 1. Kto jest administratorem danych

Administratorem danych osobowych przetwarzanych w ramach serwisu Noskiem.org jest:

**[DO UZUPEŁNIENIA: pełna nazwa — osoba fizyczna / działalność gospodarcza / spółka, adres siedziby, NIP/REGON jeśli dotyczy]**

Kontakt w sprawach ochrony danych: **[DO UZUPEŁNIENIA: adres e-mail, np. kontakt@noskiem.org lub rodo@noskiem.org]**

Nie wyznaczono Inspektora Ochrony Danych *(jeśli skala przetwarzania się zmieni, sekcja do aktualizacji)*.

## 2. Jakie dane przetwarzamy i w jakim celu

Noskiem.org działa bez rejestracji konta dla osób zgłaszających zwierzęta — dodanie i edycja ogłoszenia odbywa się przez formularz i unikalny token wysyłany e-mailem. Poniżej dane zbierane w poszczególnych formularzach.

### 2.1 Zgłoszenie zwierzęcia (zaginęło / znaleziono)

Przy dodawaniu ogłoszenia zbieramy:

- imię i nazwisko (lub nazwę) osoby zgłaszającej,
- adres e-mail — służy m.in. do przesłania linku edycyjnego oraz potwierdzeń,
- numer telefonu *(opcjonalnie — telefon nie jest publicznie widoczny na stronie ogłoszenia, wykorzystywany do kontaktu i ochrony przed botami)*,
- dane o zwierzęciu: gatunek, rasa, imię, kolory, znaki szczególne, informację o czipie i jego numer (jeśli podano),
- zdjęcia zwierzęcia (do 6 sztuk),
- lokalizację zdarzenia: opis tekstowy, współrzędne GPS wskazane na mapie, miejscowość i województwo,
- datę zdarzenia,
- adres IP osoby zgłaszającej — zapisywany wyłącznie na potrzeby ewentualnego wyjaśnienia nadużyć lub sporów prawnych dot. treści zgłoszenia; **niewidoczny publicznie**, dostępny tylko dla administratorów serwisu w panelu moderacji.

**Cel przetwarzania:** publikacja ogłoszenia w bazie serwisu, umożliwienie kontaktu między zgłaszającym a osobami, które mogą rozpoznać zwierzę, moderacja treści, obsługa edycji ogłoszenia przez token, wysyłka powiadomień e-mail o statusie zgłoszenia.

**Podstawa prawna:** art. 6 ust. 1 lit. b RODO (działanie na żądanie osoby, której dane dotyczą — świadczenie usługi zgłoszenia/publikacji ogłoszenia) oraz lit. f (prawnie uzasadniony interes administratora — ochrona przed nadużyciami, w tym przechowywanie adresu IP).

### 2.2 Zgłoszenie „widziałem zwierzaka” (sightings)

Przy zgłoszeniu obserwacji zbieramy: imię i nazwisko, adres e-mail, numer telefonu (opcjonalnie), opis, znaki szczególne, datę i miejsce obserwacji (w tym współrzędne GPS).

**Cel i podstawa prawna:** jak wyżej — umożliwienie kontaktu z osobą poszukującą zwierzęcia (art. 6 ust. 1 lit. b i f RODO).

### 2.3 Wiadomość do osoby zgłaszającej (formularz kontaktowy przy ogłoszeniu)

Zbieramy: imię, adres e-mail, treść wiadomości. Wiadomość jest przekazywana (e-mailem oraz w panelu admina) do osoby, która dodała ogłoszenie — nie jest publikowana publicznie.

**Cel i podstawa prawna:** umożliwienie kontaktu w sprawie zgłoszonego zwierzęcia (art. 6 ust. 1 lit. b i f RODO).

### 2.4 Konto w panelu administracyjnym (moderatorzy, administratorzy)

Dla osób zarządzających serwisem (zespół Noskiem.org) przechowujemy: imię/nazwę, adres e-mail, hasło (przechowywane w formie zahaszowanej), rolę w systemie (administrator/moderator/autor), historię działań moderacyjnych (jakie ogłoszenie, jaka decyzja, kiedy).

**Cel i podstawa prawna:** zarządzanie dostępem do panelu i rozliczalność działań moderacyjnych — prawnie uzasadniony interes administratora (art. 6 ust. 1 lit. f RODO) oraz wykonanie umowy/stosunku współpracy z osobą obsługującą panel (lit. b).

### 2.5 Dane techniczne i pliki cookies

Serwis korzysta wyłącznie z niezbędnych plików cookies (`_session_id` — utrzymanie sesji, `XSRF-TOKEN` — ochrona przed atakami CSRF) oraz zapisuje adres IP i user-agent przeglądarki w ramach standardowej obsługi sesji serwera. Nie używamy cookies analitycznych, marketingowych ani reklamowych. Szczegóły: patrz osobna [Informacja o ciasteczkach] *(link do istniejącej podstrony `/cookies`)*.

## 3. Zdjęcia zwierząt

Zdjęcia przesyłane w formularzu są przechowywane poza katalogiem publicznie dostępnym na serwerze i udostępniane przez kontrolowany adres URL. Zdjęcia mogą zawierać metadane (np. dane EXIF, w tym w niektórych przypadkach współrzędne GPS zapisane przez aparat/telefon). **[DO UZUPEŁNIENIA/DO SPRAWDZENIA: czy metadane EXIF są usuwane automatycznie przy przetwarzaniu zdjęcia — jeśli nie, warto rozważyć dodanie takiego mechanizmu i/lub poinformowanie użytkownika w formularzu, że nie powinien wgrywać zdjęć ze zbędnymi metadanymi.]**

## 4. Zautomatyzowane rozpoznawanie i dopasowywanie zdjęć (funkcje planowane)

Serwis planuje wdrożenie (Faza 2 i 3 rozwoju produktu) funkcji opartych o sztuczną inteligencję:

- **automatyczny opis zwierzęcia na podstawie zdjęcia** — zdjęcie przesyłane jest do zewnętrznego dostawcy usługi rozpoznawania obrazu (OpenAI, model GPT-4o Vision) w celu wygenerowania opisu (gatunek, rasa, umaszczenie, cechy szczególne, szacowany wiek); wynik zapisywany jest jako tekst w bazie danych,
- **automatyczne dopasowywanie ogłoszeń** — zdjęcia przetwarzane są w celu wygenerowania reprezentacji liczbowej (tzw. embeddingu) za pomocą zewnętrznego dostawcy (Voyage AI), służącej wyłącznie do porównywania podobieństwa wizualnego między zgłoszeniami „zaginął” a „widziany”/„znaleziono” w obrębie serwisu.

**Ważne:** dopasowania generowane w ten sposób mają charakter wyłącznie pomocniczy (sugestia dla użytkownika lub moderatora) i nie wywołują żadnych skutków prawnych ani innych istotnych skutków wobec osoby, której dane dotyczą, bez udziału człowieka — nie stanowią zautomatyzowanego podejmowania decyzji w rozumieniu art. 22 RODO.

Ponieważ dostawcy ci (OpenAI, Voyage AI) mają siedzibę poza Europejskim Obszarem Gospodarczym, przekazanie danych (zdjęć) odbywa się w oparciu o odpowiednie zabezpieczenia (standardowe klauzule umowne / mechanizm zgodny z RODO — **[DO UZUPEŁNIENIA: potwierdzić aktualny status prawny podstawy transferu u każdego z dostawców przed uruchomieniem tych funkcji w produkcji]**).

*Sekcja do aktywacji/aktualizacji dopiero po faktycznym wdrożeniu tych funkcji na produkcji — obecnie (MVP) serwis ich nie wykorzystuje.*

## 5. Komu przekazujemy dane (odbiorcy i podmioty przetwarzające)

Dane mogą być przekazywane następującym kategoriom odbiorców, wyłącznie w zakresie niezbędnym do świadczenia usługi:

| Podmiot | Rola | Zakres danych |
|---|---|---|
| Dostawca hostingu — **[DO UZUPEŁNIENIA: pełna nazwa, np. SEO Host]** | Przechowywanie bazy danych i plików serwisu | Wszystkie dane przetwarzane przez serwis |
| Dostawca poczty e-mail (SMTP) — **[DO UZUPEŁNIENIA: nazwa dostawcy]** | Wysyłka wiadomości transakcyjnych (potwierdzenia, link edycyjny, powiadomienia o moderacji, wiadomości od innych użytkowników) | Adres e-mail, treść powiadomienia |
| Cloudflare (Turnstile) | Ochrona formularzy przed botami i spamem | Dane techniczne przeglądarki niezbędne do weryfikacji antyspamowej |
| OpenAI (GPT-4o Vision) — *funkcja planowana, nieaktywna w MVP* | Automatyczny opis zwierzęcia ze zdjęcia | Przesłane zdjęcie |
| Voyage AI — *funkcja planowana, nieaktywna w MVP* | Generowanie embeddingów do dopasowywania ogłoszeń | Przesłane zdjęcie |
| OpenStreetMap / dostawca kafelków mapy | Wyświetlanie mapy lokalizacji | Standardowe dane techniczne żądania HTTP (adres IP) przy pobieraniu kafelków mapy |

Dane nie są sprzedawane ani udostępniane w celach marketingowych podmiotom trzecim.

W przyszłości, wraz z rozwojem funkcji panelu dla miast/samorządów, mogą pojawić się kolejni odbiorcy danych zagregowanych/statystycznych — **[DO UZUPEŁNIENIA gdy funkcja zostanie wdrożona: zakres danych udostępnianych partnerom miejskim, czy dane są anonimizowane/agregowane]**.

## 6. Przekazywanie danych poza Europejski Obszar Gospodarczy

Część opisanych wyżej podmiotów (Cloudflare, docelowo OpenAI i Voyage AI) może przetwarzać dane na serwerach zlokalizowanych poza EOG (np. w USA). W takich przypadkach przekazanie odbywa się w oparciu o mechanizmy zgodne z RODO (np. standardowe klauzule umowne zatwierdzone przez Komisję Europejską). **[DO UZUPEŁNIENIA: potwierdzić i wskazać konkretną podstawę prawną transferu dla każdego dostawcy — zwykle znajduje się to w ich polityce prywatności / DPA.]**

## 7. Okres przechowywania danych

**[DO UZUPEŁNIENIA/DO USTALENIA — poniżej proponowane wartości domyślne do akceptacji]**

- Dane ogłoszenia (zgłoszenie zwierzęcia) — przez czas widoczności ogłoszenia w serwisie oraz [DO USTALENIA, np. 12 miesięcy] po oznaczeniu jako „odnaleziony” lub usunięciu, na potrzeby archiwalne i statystyczne, po czym dane kontaktowe są usuwane lub anonimizowane.
- Zgłoszenia „widziałem” oraz wiadomości kontaktowe — [DO USTALENIA, np. 12 miesięcy] od zgłoszenia.
- Adres IP zgłaszającego (`submitter_ip`) — [DO USTALENIA, np. 12 miesięcy], wyłącznie do celów wyjaśnienia ewentualnych nadużyć.
- Dane kont administracyjnych/moderatorskich — przez czas pełnienia funkcji w zespole i przez okres wymagany do rozliczalności działań moderacyjnych.
- Logi sesji (`sessions` — IP, user-agent) — zgodnie z czasem trwania sesji / krótkim okresem technicznym po jej zakończeniu.

## 8. Prawa osoby, której dane dotyczą

Każdej osobie, której dane przetwarzamy, przysługuje prawo do:

- dostępu do swoich danych i uzyskania ich kopii,
- sprostowania (poprawienia) danych,
- usunięcia danych („prawo do bycia zapomnianym”),
- ograniczenia przetwarzania,
- wniesienia sprzeciwu wobec przetwarzania opartego na prawnie uzasadnionym interesie,
- przenoszenia danych,
- wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych (PUODO), ul. Stawki 2, 00-193 Warszawa, jeśli osoba uzna, że przetwarzanie narusza RODO.

W celu skorzystania z powyższych praw należy skontaktować się na adres: **[DO UZUPEŁNIENIA — adres e-mail kontaktowy]**. Ponieważ serwis nie wymaga rejestracji konta, w niektórych przypadkach (np. edycja lub usunięcie ogłoszenia) prostszą drogą jest skorzystanie z linku edycyjnego wysłanego e-mailem przy dodawaniu zgłoszenia.

## 9. Dobrowolność podania danych

Podanie danych w formularzach jest dobrowolne, ale niezbędne do skorzystania z danej funkcji serwisu (np. bez adresu e-mail nie jest możliwe wysłanie linku edycyjnego ani otrzymywanie powiadomień o statusie ogłoszenia; bez zdjęcia i lokalizacji ogłoszenie traci swoją użyteczność).

## 10. Bezpieczeństwo danych

Stosujemy m.in. następujące środki ochrony danych:

- szyfrowane, długie tokeny do edycji ogłoszeń bez konieczności logowania,
- ochronę formularzy przed botami i spamem (Cloudflare Turnstile),
- zabezpieczenia przed atakami CSRF oraz walidację i sanityzację danych wejściowych (ochrona przed XSS),
- ograniczanie liczby żądań (rate limiting),
- przechowywanie zdjęć poza katalogiem publicznym serwera,
- moderację treści przed publikacją,
- hasła do kont administracyjnych przechowywane w formie zahaszowanej.

## 11. Zmiany polityki prywatności

Niniejsza polityka może być okresowo aktualizowana, w szczególności wraz z wdrażaniem nowych funkcji serwisu (np. funkcji AI opisanych w pkt 4, panelu dla miast). Aktualna wersja jest zawsze dostępna pod adresem [DO UZUPEŁNIENIA: URL podstrony]. Data ostatniej aktualizacji: **[DATA]**.

## 12. Kontakt

W sprawach związanych z niniejszą polityką prywatności oraz ochroną danych osobowych można kontaktować się pod adresem: **[DO UZUPEŁNIENIA]**.

---

## Lista otwartych kwestii do decyzji przed publikacją

1. Podmiot administrujący — pełna nazwa, forma prawna, adres, ewentualny NIP (na razie projekt nie ma jeszcze uregulowanej formy prawnej wg dotychczasowych notatek — do potwierdzenia).
2. Docelowy adres e-mail kontaktowy do spraw RODO.
3. Nazwa dostawcy hostingu produkcyjnego i dostawcy poczty SMTP do wpisania w tabeli odbiorców.
4. Okresy przechowywania danych (pkt 7) — obecnie to propozycje robocze, wymagają akceptacji.
5. Czy i jak metadane EXIF ze zdjęć są obsługiwane (pkt 3).
6. Weryfikacja podstawy prawnej transferu danych do OpenAI / Voyage AI, gdy funkcje AI zostaną uruchomione produkcyjnie (pkt 4 i 6) — do zrobienia razem z wdrożeniem tych funkcji, nie musi być gotowe na start MVP.
7. Warto rozważyć dodanie checkboxa akceptacji polityki prywatności przy formularzu zgłoszenia (obecnie brak takiej informacji w opisie formularza).
8. Docelowy adres URL polityki (do wpisania w pkt 11) oraz spójność z istniejącą podstroną `/cookies`.

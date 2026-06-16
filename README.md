# Nosekem

Platforma do zgłaszania i wyszukiwania zaginionych, znalezionych i widzianych zwierząt.  
Projekt oparty na **Laravel** i **Filament Admin**, zaprojektowany z myślą o prostocie, szybkości i realnej skuteczności.

## Funkcje
- Dodawanie ogłoszeń bez logowania (edycja przez link e‑mail)
- Moderacja treści w panelu administracyjnym
- Statusy zgłoszeń (zaginiony, znaleziony, widziany, odnaleziony)
- Mapa lokalizacji (OpenStreetMap + Leaflet)
- Filtrowanie po gatunku, kolorach, województwie, statusie i innych parametrach
- Galeria zdjęć
- Bezpieczny kontakt z ogłoszeniodawcą (formularz + ukryty telefon)
- Zgłoszenia typu „widziano zwierzaka”

## Technologie
- **Laravel 11**
- **Filament Admin**
- **MySQL / MariaDB**
- **TailwindCSS + Blade**
- **OpenStreetMap + Leaflet**

## Dokumentacja
Pełna dokumentacja architektury znajduje się w katalogu `/docs`:
- `/docs/architecture/MindMap.png` — mapa myśli projektu  
- `/docs/architecture/DatabaseSchema.md` — schemat bazy danych  
- `/docs/architecture/UserFlow.md` — przepływ użytkownika  
- `/docs/architecture/AdminFlow.md` — moderacja i panel admina  
- `/docs/architecture/Security.md` — zasady bezpieczeństwa  

## Cel projektu
Stworzyć lekką, szybką i dostępną platformę, która pomaga zwierzakom wracać do domów.

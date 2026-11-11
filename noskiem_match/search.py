"""Wyszukiwanie podobnych zwierząt: max podobieństwa po zdjęciach, progi, miękkie bonusy.
Wymaga tylko numpy (bez torcha) - startuje szybko, wywoływane z PHP przy każdym wyszukiwaniu.
Zgodne z Pythonem 3.9 (hosting)."""
import numpy as np


# ** Zgodność rasy/kolorów - tylko pomocniczo. Znalazca często źle ocenia rasę,
# więc to NIGDY nie wyklucza kandydata; najwyżej lekko zmienia kolejność
def soft_signals(q, c):
    """Zwraca (rasa_zgodna: bool|None, podobieństwo_kolorów: float|None).
    None = brak danych po którejś stronie (np. rasa nieznana) - ani bonus, ani kara."""
    qb, cb = q.get("breed_id"), c.get("breed_id")
    breed = None if qb is None or cb is None else qb == cb
    qc, cc = set(q.get("color_ids") or []), set(c.get("color_ids") or [])
    colors = len(qc & cc) / len(qc | cc) if qc and cc else None
    return breed, colors


def bonus_for(breed, colors, weights):
    b = 0.0
    if breed:
        b += float(weights.get("breed", 0.0))
    if colors is not None:
        b += float(weights.get("color", 0.0)) * colors
    return b


# ---------------------------
# rank - ranking kandydatów dla jednego zapytania
# ---------------------------
def rank(query_vecs, cand_vecs, candidates, query_meta, params):
    """
    query_vecs: (k, d) - zdjęcia ogłoszenia zapytania (znormalizowane L2).
    cand_vecs:  (n, d) - zdjęcia wszystkich kandydatów, sklejone po kolei.
    candidates: lista {listing_id, rows: [start, count], breed_id?, color_ids?} -
                rows wskazuje wiersze cand_vecs należące do kandydata.
    params:     top_n, threshold_possible, threshold_strong, soft_weights, log_floor_n.

    Wynik zwierzęcia = MAX podobieństwa po wszystkich parach zdjęć (zapytanie × kandydat).
    Progi dotyczą CZYSTEGO podobieństwa obrazu (kalibracja nie miesza się z metadanymi);
    bonus rasa/kolor zmienia tylko kolejność. Zwraca top log_floor_n (także poniżej progu,
    do logów) z flagą shown i pozycją na wyświetlanej liście.
    """
    lo, hi = float(params["threshold_possible"]), float(params["threshold_strong"])
    top_n, floor = int(params["top_n"]), int(params.get("log_floor_n", 50))
    weights = params.get("soft_weights") or {}

    rows = []
    if candidates and len(cand_vecs):
        # ** Jedno mnożenie macierzy dla wszystkich par, potem max po zdjęciach zapytania
        best_per_photo = (query_vecs @ cand_vecs.T).max(axis=0)
        for c in candidates:
            start, count = int(c["rows"][0]), int(c["rows"][1])
            if count <= 0:
                continue
            seg = best_per_photo[start:start + count]
            j = int(np.argmax(seg))
            breed, colors = soft_signals(query_meta, c)
            score = float(seg[j])
            bonus = bonus_for(breed, colors, weights)
            rows.append({
                "listing_id": c["listing_id"],
                "score": round(score, 6),
                "bonus": round(bonus, 6),
                "breed_match": breed,
                "color_overlap": None if colors is None else round(colors, 4),
                # ** Indeks najlepiej pasującego zdjęcia kandydata (kolejność z PHP) - do UI/maila
                "best_photo_index": j,
                "level": "strong" if score >= hi else "possible" if score >= lo else "below",
            })

    # Sortowanie: wynik obrazu + bonus; remis -> czysty wynik obrazu
    rows.sort(key=lambda r: (-(r["score"] + r["bonus"]), -r["score"]))

    position = 0
    for i, r in enumerate(rows, 1):
        r["rank"] = i
        # ** Pozycja liczona tylko wśród pokazywanych - kandydat poniżej progu podbity
        # bonusem nie zajmuje miejsca w top N
        if r["level"] != "below" and position < top_n:
            position += 1
            r["shown"], r["position"] = True, position
        else:
            r["shown"], r["position"] = False, None

    # Do zwrotu: wszystkie pokazane + top `floor` ogółem (logi także poniżej progu)
    return [r for r in rows if r["shown"] or r["rank"] <= floor]

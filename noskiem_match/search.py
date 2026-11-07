"""Wyszukiwanie: max podobieństwa po zdjęciach zwierzęcia, progi, miękkie bonusy.
Nie wymaga torcha - tylko numpy."""
import numpy as np


def _set(x):
    return {str(s).strip().lower() for s in (x or []) if str(s).strip()}


def soft_bonus(q, c, w):
    """Mały bonus za zgodność rasy/koloru. Wpływa WYŁĄCZNIE na kolejność, nigdy na to,
    czy kandydat się pojawia (rasę często źle ocenia znalazca)."""
    b = 0.0
    qb, cb = (q.get("breed") or "").strip().lower(), (c.get("breed") or "").strip().lower()
    if qb and cb and qb == cb:
        b += w.get("breed", 0.0)
    qc, cc = _set(q.get("colors")), _set(c.get("colors"))
    if qc and cc:
        b += w.get("color", 0.0) * len(qc & cc) / len(qc | cc)
    return b


def rank(query_vecs, cand_vecs, cand_meta, query_meta, cfg):
    """query_vecs: (k,d) zdjęcia ogłoszenia zapytania. cand_vecs: {listing_id: (n,d)}.
    Wynik zwierzęcia = max po parach zdjęć. Progi dotyczą CZYSTEGO podobieństwa obrazu
    (żeby kalibracja nie mieszała się z metadanymi); bonus wpływa tylko na sortowanie."""
    w = cfg.get("soft_weights", {})
    rows = []
    for lid, vecs in cand_vecs.items():
        sim = float((query_vecs @ vecs.T).max())
        bonus = soft_bonus(query_meta, cand_meta.get(lid, {}), w)
        rows.append({"listing_id": lid, "score": sim, "bonus": bonus})
    rows.sort(key=lambda r: -(r["score"] + r["bonus"]))
    lo, hi = cfg["threshold_possible"], cfg["threshold_strong"]
    for i, r in enumerate(rows, 1):
        r["rank"] = i
        r["level"] = "strong" if r["score"] >= hi else "possible" if r["score"] >= lo else "below"
        r["shown"] = r["level"] != "below" and i <= cfg["top_n"]
    return rows

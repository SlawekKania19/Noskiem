"""Testy bez prawdziwych modeli (atrapa embeddera, syntetyczne wektory).
Uruchomienie: python noskiem_match/tests/test_flow.py   (albo pytest)"""
import argparse
import base64
import io
import json
import sys
import tempfile
from contextlib import redirect_stdout
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import cli  # noqa: E402
from search import rank  # noqa: E402

CFG = cli.load_cfg()


def unit(v):
    return (v / np.linalg.norm(v)).astype(np.float32)


# ** Atrapa modelu - wektor zależny od treści pliku, "zepsuty" plik rzuca wyjątek jak PIL
class FakeEmbedder:
    INFO = {"box": [0.1, 0.2, 0.6, 0.9], "size": [1080, 1920]}

    def __init__(self):
        self.loads = 0
        self.last_info = None

    def load(self):
        self.loads += 1

    def load_detector(self):
        self.loads += 1

    def detect_path(self, path):
        if Path(path).read_bytes().startswith(b"BROKEN"):
            raise OSError("cannot identify image file")
        return dict(self.INFO)

    def embed_path(self, path):
        data = Path(path).read_bytes()
        if data.startswith(b"BROKEN"):
            raise OSError("cannot identify image file")
        self.last_info = dict(self.INFO)
        rng = np.random.default_rng(len(data))
        return unit(rng.normal(size=8)), True


def run_embed(root, lines, max_seconds=0):
    args = argparse.Namespace(root=str(root), threads=1, nice=0, max_seconds=max_seconds)
    buf = io.StringIO()
    with redirect_stdout(buf):
        cli.cmd_embed(args, CFG, embedder=FakeEmbedder(), stdin=io.StringIO("\n".join(lines)))
    return [json.loads(x) for x in buf.getvalue().splitlines()]


def test_embed_protocol():
    root = Path(tempfile.mkdtemp())
    (root / "photos").mkdir()
    (root / "photos" / "a.jpg").write_bytes(b"x" * 10)
    (root / "photos" / "bad.jpg").write_bytes(b"BROKEN")
    (root.parent / "secret.txt").write_bytes(b"tajne")
    lines = [
        json.dumps({"photo_id": 1, "path": "photos/a.jpg"}),
        json.dumps({"photo_id": 2, "path": "photos/bad.jpg"}),       # uszkodzony plik
        json.dumps({"photo_id": 3, "path": "photos/nie-ma.jpg"}),    # brak pliku
        json.dumps({"photo_id": 4, "path": "../secret.txt"}),        # wyjście poza root
        json.dumps({"photo_id": 5, "path": str(root / "photos" / "a.jpg")}),  # ścieżka absolutna
        "to nie jest json",
    ]
    ev = run_embed(root, lines)
    assert ev[0]["event"] == "ready" and ev[0]["model_version"] == CFG["model_version"]
    res = {e["photo_id"]: e for e in ev if e["event"] == "result"}
    ok = res[1]
    assert ok["status"] == "ok" and ok["dim"] == 8
    vec = np.frombuffer(base64.b64decode(ok["vector_b64"]), "<f4")
    assert abs(float(np.linalg.norm(vec)) - 1.0) < 1e-5
    for pid in (2, 3, 4, 5):
        assert res[pid]["status"] == "error", res[pid]
    assert "poza katalogiem" in res[4]["error"]
    # Linia nie-JSON -> błąd bez photo_id, partia idzie dalej
    assert sum(1 for e in ev if e["event"] == "result" and e["photo_id"] is None) == 1
    done = ev[-1]
    assert done["event"] == "done" and done["processed"] == 1 and done["failed"] == 5
    # Ramka zwierzęcia i rozmiar zdjęcia do kadrowania miniaturek
    assert ok["box"] == [0.1, 0.2, 0.6, 0.9] and ok["size"] == [1080, 1920]


def test_detect_protocol():
    root = Path(tempfile.mkdtemp())
    (root / "a.jpg").write_bytes(b"x")
    (root / "bad.jpg").write_bytes(b"BROKEN")
    args = argparse.Namespace(root=str(root), threads=1, nice=0, max_seconds=0)
    buf = io.StringIO()
    lines = [json.dumps({"photo_id": 1, "path": "a.jpg"}), json.dumps({"photo_id": 2, "path": "bad.jpg"})]
    with redirect_stdout(buf):
        cli.cmd_detect(args, CFG, embedder=FakeEmbedder(), stdin=io.StringIO("\n".join(lines)))
    ev = [json.loads(x) for x in buf.getvalue().splitlines()]
    res = {e["photo_id"]: e for e in ev if e["event"] == "result"}
    assert res[1]["status"] == "ok" and res[1]["box"] == [0.1, 0.2, 0.6, 0.9] and "vector_b64" not in res[1]
    assert res[2]["status"] == "error"
    assert ev[-1]["processed"] == 1 and ev[-1]["failed"] == 1


def test_embed_max_seconds():
    root = Path(tempfile.mkdtemp())
    (root / "a.jpg").write_bytes(b"x")
    # Limit ujemny w praktyce = "czas już minął" -> nic nie liczymy, zgłaszamy przerwanie
    ev = run_embed(root, [json.dumps({"photo_id": 1, "path": "a.jpg"})], max_seconds=-1)
    assert ev[-1]["stopped"] == "max_seconds" and ev[-1]["processed"] == 0


PARAMS = {"top_n": 10, "threshold_possible": 0.60, "threshold_strong": 0.75,
          "soft_weights": {"breed": 0.02, "color": 0.02}, "log_floor_n": 50}


def pack(groups):
    """[(listing_id, (n,d), meta)] -> (macierz, candidates z rows)."""
    mats, cands, start = [], [], 0
    for lid, vecs, meta in groups:
        mats.append(vecs)
        cands.append({"listing_id": lid, "rows": [start, len(vecs)], **meta})
        start += len(vecs)
    return np.concatenate(mats), cands


def test_rank():
    rng = np.random.default_rng(0)
    d = 64
    mia = rng.normal(size=d)
    q = np.stack([unit(mia + 0.3 * rng.normal(size=d))])
    groups = [(2, np.stack([unit(rng.normal(size=d)), unit(mia + 0.3 * rng.normal(size=d))]),
               {"breed_id": 9})]  # właściwe zdjęcie jako DRUGIE - max po zdjęciach
    for i in range(3, 13):
        groups.append((i, np.stack([unit(rng.normal(size=d))]), {"breed_id": 5}))
    mat, cands = pack(groups)
    rows = rank(q, mat, cands, {"breed_id": 5}, PARAMS)
    # ** Właściwy na 1. miejscu mimo NIEZGODNEJ rasy (rasa nie wyklucza)
    assert rows[0]["listing_id"] == 2 and rows[0]["shown"] and rows[0]["position"] == 1
    assert rows[0]["best_photo_index"] == 1 and rows[0]["breed_match"] is False
    assert all(r["score"] >= 0.60 for r in rows if r["shown"])       # próg na czystym obrazie
    assert len(rows) == 11                                            # zwraca też poniżej progu


def test_bonus_nie_zajmuje_miejsca_i_nie_przepycha_przez_prog():
    d = 4
    q = np.stack([unit(np.array([1.0, 0, 0, 0]))])

    def at(sim):  # wektor o zadanym cosinusie z q
        return unit(np.array([sim, np.sqrt(1 - sim ** 2), 0, 0]))

    groups = [(1, np.stack([at(0.59)]), {"breed_id": 5, "color_ids": [1]}),   # poniżej progu + bonus
              (2, np.stack([at(0.58)]), {}),
              (3, np.stack([at(0.61)]), {"breed_id": 7}),
              (4, np.stack([at(0.80)]), {})]
    mat, cands = pack(groups)
    params = {**PARAMS, "top_n": 1, "soft_weights": {"breed": 0.05, "color": 0.05}}
    rows = rank(q, mat, cands, {"breed_id": 5, "color_ids": [1]}, params)
    by = {r["listing_id"]: r for r in rows}
    # 0.59 + 0.10 bonusu sortuje się wyżej niż 0.61, ale nadal "below" i niepokazany
    assert by[1]["rank"] < by[3]["rank"] and by[1]["level"] == "below" and not by[1]["shown"]
    assert by[4]["level"] == "strong" and by[4]["position"] == 1
    assert not by[3]["shown"]                                      # top_n = 1
    assert by[1]["breed_match"] is True and by[1]["color_overlap"] == 1.0
    assert by[2]["breed_match"] is None                            # brak danych = bez bonusu/kary


def test_log_floor_i_pusta_pula():
    d = 8
    rng = np.random.default_rng(1)
    q = np.stack([unit(rng.normal(size=d))])
    groups = [(i, np.stack([unit(rng.normal(size=d))]), {}) for i in range(80)]
    mat, cands = pack(groups)
    rows = rank(q, mat, cands, {}, {**PARAMS, "threshold_possible": 2.0, "threshold_strong": 2.0})
    assert len(rows) == 50 and not any(r["shown"] for r in rows)   # nic nad progiem, log top 50
    assert rank(q, mat[:0], [], {}, PARAMS) == []


def test_search_cli():
    d = 4
    tmp = Path(tempfile.mkdtemp())
    q = unit(np.array([1.0, 0, 0, 0]))
    same = unit(np.array([0.95, 0.31, 0, 0]))
    other = unit(np.array([0, 0, 1.0, 0]))
    np.stack([q, other, same]).astype("<f4").tofile(tmp / "v.f32")
    req = {"dim": d, "vectors_file": str(tmp / "v.f32"), "query_rows": [0, 1], "query": {},
           "candidates": [{"listing_id": 7, "rows": [1, 2]}, {"listing_id": 8, "rows": [1, 0]}],
           "params": PARAMS}
    buf = io.StringIO()
    with redirect_stdout(buf):
        cli.cmd_search(None, CFG, stdin=io.StringIO(json.dumps(req)))
    out = json.loads(buf.getvalue())
    assert out["status"] == "ok" and out["n_compared"] == 1      # kandydat 8 bez wektorów
    assert out["results"][0]["listing_id"] == 7 and out["results"][0]["level"] == "strong"
    # Złe rows -> wyjątek (PHP dostanie niezerowy kod wyjścia)
    req["candidates"][0]["rows"] = [2, 5]
    try:
        cli.cmd_search(None, CFG, stdin=io.StringIO(json.dumps(req)))
        assert False, "powinien być wyjątek"
    except ValueError:
        pass


if __name__ == "__main__":
    test_embed_protocol()
    test_detect_protocol()
    test_embed_max_seconds()
    test_rank()
    test_bonus_nie_zajmuje_miejsca_i_nie_przepycha_przez_prog()
    test_log_floor_i_pusta_pula()
    test_search_cli()
    print("OK")

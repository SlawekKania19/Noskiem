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


# ** Atrapa modelu — wektor zależny od treści pliku, "zepsuty" plik rzuca wyjątek jak PIL
class FakeEmbedder:
    def __init__(self):
        self.loads = 0

    def load(self):
        self.loads += 1

    def embed_path(self, path):
        data = Path(path).read_bytes()
        if data.startswith(b"BROKEN"):
            raise OSError("cannot identify image file")
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


def test_embed_max_seconds():
    root = Path(tempfile.mkdtemp())
    (root / "a.jpg").write_bytes(b"x")
    # Limit ujemny w praktyce = "czas już minął" -> nic nie liczymy, zgłaszamy przerwanie
    ev = run_embed(root, [json.dumps({"photo_id": 1, "path": "a.jpg"})], max_seconds=-1)
    assert ev[-1]["stopped"] == "max_seconds" and ev[-1]["processed"] == 0


def test_rank():
    rng = np.random.default_rng(0)
    d = 64
    cfg = {"top_n": 10, "threshold_possible": 0.60, "threshold_strong": 0.75,
           "soft_weights": {"breed": 0.02, "color": 0.02}}
    mia = rng.normal(size=d)
    q = np.stack([unit(mia + 0.3 * rng.normal(size=d))])
    cands = {"L2": np.stack([unit(mia + 0.3 * rng.normal(size=d)), unit(rng.normal(size=d))])}
    for i in range(3, 13):
        cands[f"L{i}"] = np.stack([unit(rng.normal(size=d))])
    meta = {k: {"breed": "x"} for k in cands}
    rows = rank(q, cands, meta, {"breed": "x"}, cfg)
    assert rows[0]["listing_id"] == "L2" and rows[0]["shown"]       # max po zdjęciach
    assert all(r["score"] >= 0.60 for r in rows if r["shown"])       # próg na czystym obrazie
    assert len(rows) == 11                                            # zwraca też poniżej progu


if __name__ == "__main__":
    test_embed_protocol()
    test_embed_max_seconds()
    test_rank()
    print("OK")

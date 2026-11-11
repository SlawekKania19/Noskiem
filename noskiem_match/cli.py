#!/usr/bin/env python3
"""noskiem_match - CLI wywoływane z Laravela (php artisan match:embed).

Użycie:
  python cli.py embed --root /sciezka/storage/app/public [--threads 1] [--nice 10]
                      [--max-seconds 240] < zadania.jsonl
      stdin:  jedna linia JSON na zdjęcie: {"photo_id": 7, "path": "photos/abc.jpg"}
      stdout: JSONL ze zdarzeniami:
              {"event":"ready","model_version":...,"load_ms":...}
              {"event":"result","photo_id":7,"status":"ok","vector_b64":...,"dim":768,
               "crop_found":true,"ms":1234}
              {"event":"result","photo_id":8,"status":"error","error":"..."}
              {"event":"done","processed":..,"failed":..,"stopped":null|"max_seconds",
               "peak_rss_mb":..}

  python cli.py bench --root /sciezka/storage/app/public photos/a.jpg photos/b.jpg
      Pomiar: czas ładowania modeli, czas na zdjęcie, szczytowy RAM procesu.

  python cli.py search < zapytanie.json          (tylko numpy, bez torcha)
      stdin:  {"dim":768, "vectors_file":"/tmp/x.f32", "query_rows":[0,3],
               "query":{"breed_id":5|null,"color_ids":[1,2]},
               "candidates":[{"listing_id":12,"rows":[3,2],"breed_id":..,"color_ids":[..]}],
               "params":{"top_n":10,"threshold_possible":..,"threshold_strong":..,
                         "soft_weights":{"breed":..,"color":..},"log_floor_n":50}}
      stdout: {"status":"ok","results":[{listing_id,score,bonus,rank,level,shown,
               position,breed_match,color_overlap,best_photo_index}], ...}

Python nie łączy się z bazą - kolejkę i zapis wektorów prowadzi PHP.
Zgodne z Pythonem 3.9 (hosting).
"""
import argparse
import base64
import json
import os
import sys
import time
from pathlib import Path

HERE = Path(__file__).resolve().parent


def load_cfg():
    return json.loads((HERE / "config.json").read_text(encoding="utf-8"))


def emit(obj):
    # ** flush po każdej linii - PHP czyta wyniki na bieżąco i zapisuje je od razu,
    # więc przerwany proces nie gubi już policzonych zdjęć
    sys.stdout.write(json.dumps(obj, ensure_ascii=False) + "\n")
    sys.stdout.flush()


def peak_rss_mb():
    """Szczytowe zużycie RAM procesu (MB). Na Windows brak modułu resource -> None."""
    try:
        import resource
    except ImportError:
        return None
    # Linux: ru_maxrss w KB
    return round(resource.getrusage(resource.RUSAGE_SELF).ru_maxrss / 1024, 1)


def safe_path(root, rel):
    """Ścieżka pliku wewnątrz root. Odrzuca ścieżki absolutne i wyjście poza katalog (../)."""
    if not rel or os.path.isabs(rel):
        raise ValueError("niedozwolona ścieżka")
    p = (root / rel).resolve()
    if not p.is_relative_to(root):
        raise ValueError("ścieżka poza katalogiem zdjęć")
    if not p.is_file():
        raise FileNotFoundError("brak pliku")
    return p


def _load_trim():
    """malloc_trim z glibc (Linux). Po każdym zdjęciu oddaje systemowi zwolnioną pamięć -
    bez tego RSS rośnie z każdym zdjęciem przez fragmentację (pomiar na hostingu:
    1,5 GB po 14 zdjęciach przy ~1 GB dla pojedynczego). Poza glibc - brak operacji."""
    try:
        import ctypes
        return ctypes.CDLL("libc.so.6").malloc_trim
    except (OSError, AttributeError):
        return None


_TRIM = _load_trim()


def release_memory():
    if _TRIM is not None:
        _TRIM(0)


def lower_priority(nice):
    # ** Niższy priorytet CPU (nice) - przy obciążeniu serwera strona WWW ma pierwszeństwo
    if nice and hasattr(os, "nice"):
        try:
            os.nice(int(nice))
        except OSError:
            pass


def make_embedder(cfg, threads):
    from embedder import Embedder
    return Embedder(cfg, threads=threads)


# ---------------------------
# embed - przetwarza zadania ze stdin, wynik każdego zdjęcia od razu na stdout
# ---------------------------
def cmd_embed(args, cfg, embedder=None, stdin=None):
    root = Path(args.root).resolve()
    lower_priority(args.nice)
    started = time.monotonic()

    emb = embedder or make_embedder(cfg, args.threads)
    t0 = time.monotonic()
    emb.load()
    emit({"event": "ready", "model_version": cfg["model_version"],
          "load_ms": int((time.monotonic() - t0) * 1000)})

    processed = failed = 0
    stopped = None
    for line in (stdin or sys.stdin):
        line = line.strip()
        if not line:
            continue
        # ** Limit czasu sprawdzany PRZED kolejnym zdjęciem - reszta czeka na następne uruchomienie
        if args.max_seconds and time.monotonic() - started > args.max_seconds:
            stopped = "max_seconds"
            break
        photo_id = None
        try:
            task = json.loads(line)
            photo_id = task["photo_id"]
            t = time.monotonic()
            vec, found = emb.embed_path(safe_path(root, task["path"]))
            emit({"event": "result", "photo_id": photo_id, "status": "ok",
                  "vector_b64": base64.b64encode(vec.astype("<f4").tobytes()).decode("ascii"),
                  "dim": int(vec.shape[0]), "crop_found": bool(found),
                  "ms": int((time.monotonic() - t) * 1000)})
            processed += 1
        except Exception as e:  # noqa: BLE001 - uszkodzony plik nie zatrzymuje całej partii
            failed += 1
            emit({"event": "result", "photo_id": photo_id, "status": "error",
                  "error": f"{type(e).__name__}: {e}"[:500]})
        release_memory()

    emit({"event": "done", "processed": processed, "failed": failed, "stopped": stopped,
          "peak_rss_mb": peak_rss_mb()})
    return 0


# ---------------------------
# bench - pomiar czasu i RAM-u na hostingu
# ---------------------------
def cmd_bench(args, cfg):
    root = Path(args.root).resolve()
    lower_priority(args.nice)
    emb = make_embedder(cfg, args.threads)
    t0 = time.monotonic()
    emb.load()
    out = {"model_version": cfg["model_version"], "threads": args.threads,
           "load_ms": int((time.monotonic() - t0) * 1000),
           "rss_after_load_mb": peak_rss_mb(), "photos": []}
    for rel in args.paths:
        t = time.monotonic()
        try:
            vec, found = emb.embed_path(safe_path(root, rel))
            out["photos"].append({"path": rel, "ms": int((time.monotonic() - t) * 1000),
                                  "crop_found": bool(found), "dim": int(vec.shape[0])})
        except Exception as e:  # noqa: BLE001
            out["photos"].append({"path": rel, "error": f"{type(e).__name__}: {e}"})
        release_memory()
    out["peak_rss_mb"] = peak_rss_mb()
    print(json.dumps(out, ensure_ascii=False, indent=2))
    return 0


# ---------------------------
# search - ranking kandydatów (tylko numpy, bez torcha)
# ---------------------------
def cmd_search(args, cfg, stdin=None):
    import numpy as np
    from search import rank

    t = time.monotonic()
    req = json.load(stdin or sys.stdin)
    dim = int(req["dim"])

    # ** Wektory w pliku binarnym (float32 LE, wiersz po wierszu) - przy tysiącach
    # kandydatów JSON z liczbami byłby kilkadziesiąt razy większy i wolniejszy
    raw = np.fromfile(req["vectors_file"], dtype="<f4")
    if dim <= 0 or raw.size % dim:
        raise ValueError("rozmiar pliku wektorów nie pasuje do dim")
    mat = raw.reshape(-1, dim)

    qs, qc = (int(x) for x in req["query_rows"])
    if qc <= 0 or qs + qc > len(mat):
        raise ValueError("niepoprawne query_rows")
    for c in req["candidates"]:
        s, n = (int(x) for x in c["rows"])
        if s < 0 or s + n > len(mat):
            raise ValueError("niepoprawne rows kandydata %s" % c.get("listing_id"))

    results = rank(mat[qs:qs + qc], mat, req["candidates"], req.get("query") or {}, req["params"])
    print(json.dumps({"status": "ok", "model_version": cfg["model_version"],
                      "n_compared": sum(1 for c in req["candidates"] if int(c["rows"][1]) > 0),
                      "results": results, "ms": int((time.monotonic() - t) * 1000)},
                     ensure_ascii=False))
    return 0


def build_parser():
    ap = argparse.ArgumentParser()
    sub = ap.add_subparsers(dest="cmd", required=True)
    sub.add_parser("search")
    for name in ("embed", "bench"):
        p = sub.add_parser(name)
        p.add_argument("--root", required=True, help="katalog zdjęć (storage/app/public)")
        p.add_argument("--threads", type=int, default=1)
        p.add_argument("--nice", type=int, default=0)
        if name == "embed":
            p.add_argument("--max-seconds", type=int, default=0, help="0 = bez limitu")
        else:
            p.add_argument("paths", nargs="+")
    return ap


def main(argv=None):
    args = build_parser().parse_args(argv)
    cfg = load_cfg()
    if args.cmd == "embed":
        return cmd_embed(args, cfg)
    if args.cmd == "search":
        return cmd_search(args, cfg)
    return cmd_bench(args, cfg)


if __name__ == "__main__":
    sys.exit(main())

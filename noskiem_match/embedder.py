"""Embedding zdjęcia: kadrowanie zwierzęcia (DETR) + DINOv2 CLS, normalizacja L2.

Logika 1:1 z dog_eval.py (crop_animals + embed_dinov2, pooling "cls").
Wymaga: torch (CPU), transformers, pillow, timm (backbone DETR). Licencje: Apache 2.0 / BSD.
Modele ładowane leniwie i tylko raz na proces.
Zgodne z Pythonem 3.9 (hosting).
"""
import numpy as np

# Nazwy klas COCO traktowane jako zwierzę (jak w dog_eval.py)
ANIMALS = {"bird", "cat", "dog", "horse", "sheep", "cow", "elephant", "bear", "zebra", "giraffe"}


# ---------------------------
# Embedder — trzyma modele w pamięci, liczy wektor dla pojedynczego zdjęcia
# ---------------------------
class Embedder:
    def __init__(self, cfg, threads=1):
        self.cfg = cfg
        self.threads = threads
        self._loaded = False

    def load(self):
        if self._loaded:
            return
        import torch
        from transformers import AutoImageProcessor, AutoModel

        # ** Hosting (CloudLinux LVE) pokazuje wszystkie rdzenie serwera (32), a konto ma
        # limit ~1 rdzenia. Domyślna liczba wątków torcha = liczba rdzeni -> wątki dławiłyby się
        # nawzajem. Liczbę wątków ustawiamy jawnie z konfiguracji.
        torch.set_num_threads(max(1, int(self.threads)))
        self.torch = torch
        # ** use_fast=False jawnie — tak liczył dog_eval.py (domyślny "wolny" procesor).
        # Domyślna wartość ma się zmienić w kolejnych wersjach transformers, a "szybki"
        # procesor daje minimalnie inne wektory -> po cichu rozjechałby się z model_version
        self.proc = AutoImageProcessor.from_pretrained(self.cfg["embedding_model"], use_fast=False)
        self.model = AutoModel.from_pretrained(self.cfg["embedding_model"]).eval()
        self.det = None
        if self.cfg.get("use_crop"):
            from transformers import DetrForObjectDetection, DetrImageProcessor
            self.det_proc = DetrImageProcessor.from_pretrained(self.cfg["detector_model"], use_fast=False)
            # ** use_pretrained_backbone=False — bez tego timm najpierw ładuje (i przy pierwszym
            # starcie pobiera) wagi ImageNet szkieletu ResNet, które checkpoint DETR i tak
            # nadpisuje. Sprawdzone na QA: wektory bit w bit identyczne, bez ~300 linii
            # ostrzeżeń w stderr i bez zbędnego pobierania wag przy świeżej instalacji
            self.det = DetrForObjectDetection.from_pretrained(
                self.cfg["detector_model"], use_pretrained_backbone=False).eval()
        self._loaded = True

    @staticmethod
    def open_image(path):
        from PIL import Image, ImageOps
        # ** exif_transpose — telefony zapisują obrót w EXIF zamiast obracać piksele.
        # Laravel (Intervention) już obraca przy zapisie, ale to tanie zabezpieczenie.
        with Image.open(path) as im:
            return ImageOps.exif_transpose(im).convert("RGB")

    # ** Wycina największe wykryte zwierzę (z marginesem). Gdy nic nie wykryto — całe zdjęcie.
    def _crop(self, img):
        max_side = self.cfg.get("det_max_side", 1024)
        margin = self.cfg.get("crop_margin", 0.1)
        scale = min(1.0, max_side / max(img.size))
        # Detekcja na pomniejszonym zdjęciu (szybciej na CPU), kadr z oryginału
        small = img.resize((max(1, round(img.width * scale)), max(1, round(img.height * scale))))
        with self.torch.inference_mode():
            out = self.det(**self.det_proc(images=small, return_tensors="pt"))
        res = self.det_proc.post_process_object_detection(
            out, threshold=self.cfg.get("det_threshold", 0.4),
            target_sizes=[(small.height, small.width)])[0]
        best = None
        for lab, box in zip(res["labels"], res["boxes"]):
            if self.det.config.id2label[int(lab)] not in ANIMALS:
                continue
            x1, y1, x2, y2 = (v / scale for v in box.tolist())
            area = (x2 - x1) * (y2 - y1)
            if best is None or area > best[0]:
                best = (area, x1, y1, x2, y2)
        if not best:
            return img, False
        _, x1, y1, x2, y2 = best
        w, h = x2 - x1, y2 - y1
        box = (max(0, x1 - margin * w), max(0, y1 - margin * h),
               min(img.width, x2 + margin * w), min(img.height, y2 + margin * h))
        return img.crop(tuple(int(v) for v in box)), True

    def embed_path(self, path):
        """Zwraca (wektor float32 znormalizowany L2, czy_znaleziono_zwierzę)."""
        self.load()
        img = self.open_image(path)
        found = False
        if self.det is not None:
            img, found = self._crop(img)
        with self.torch.inference_mode():
            h = self.model(**self.proc(images=[img], return_tensors="pt")).last_hidden_state
        # Token CLS = pozycja 0
        v = h[0, 0].cpu().numpy().astype(np.float32)
        n = float(np.linalg.norm(v))
        if not np.isfinite(n) or n == 0.0:
            raise ValueError("pusty/niepoprawny wektor")
        return v / n, found

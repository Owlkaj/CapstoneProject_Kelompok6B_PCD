"""
Diagnostic tool for field photos.

For every image in a folder it prints predictions with and without the leaf
isolation preprocessing, and saves (1) the EXIF-corrected original,
(2) the segmentation mask overlay, and (3) the preprocessed image, so you can
see with your own eyes whether segmentation is helping or destroying the leaf.

Usage:
    ..\\ml\\.venv\\Scripts\\python.exe tools\\diagnose_leaf.py "C:\\path\\to\\photos"
    ..\\ml\\.venv\\Scripts\\python.exe tools\\diagnose_leaf.py "C:\\path\\to\\photos" --out diagnostics --policy always
"""
import argparse
import io
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageOps

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from app.services.inference import InferenceService
from app.services.leaf_preprocessing import LeafPreprocessor

EXTS = {".jpg", ".jpeg", ".png"}


def load_exif(path: Path) -> Image.Image:
    return ImageOps.exif_transpose(Image.open(path)).convert("RGB")


def mask_overlay(image: Image.Image, mask: np.ndarray) -> Image.Image:
    arr = np.asarray(image).astype(np.float32).copy()
    m = mask.astype(bool)
    overlay = np.array([255.0, 40.0, 40.0], dtype=np.float32)
    arr[m] = arr[m] * 0.5 + overlay * 0.5
    return Image.fromarray(np.clip(arr, 0, 255).astype(np.uint8))


def to_bytes(img: Image.Image) -> bytes:
    buf = io.BytesIO()
    img.save(buf, format="JPEG", quality=92)
    return buf.getvalue()


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("folder", help="Folder containing field photos")
    ap.add_argument("--out", default="diagnostics", help="Output folder for diagnostic images")
    ap.add_argument("--policy", default="always", choices=["always", "adaptive"])
    args = ap.parse_args()

    folder = Path(args.folder)
    if not folder.is_dir():
        print(f"[ERROR] Not a folder: {folder}")
        return
    files = [p for p in sorted(folder.iterdir()) if p.suffix.lower() in EXTS]
    if not files:
        print(f"[ERROR] No images (.jpg/.jpeg/.png) in {folder}")
        return

    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)

    root = Path(__file__).resolve().parent.parent
    service = InferenceService(
        model_path=str(root / "models" / "final_model.pt"),
        label_mapping_path=str(root / "models" / "label_mapping.json"),
        preprocessing_path=str(root / "models" / "preprocessing.json"),
        device_name="cpu",
    )
    service.load()

    pre = LeafPreprocessor(enabled=True, method="auto", policy=args.policy)

    print("\n" + "=" * 78)
    print(f"DIAGNOSING {len(files)} IMAGE(S) FROM {folder}")
    print("=" * 78)

    for p in files:
        image = load_exif(p)
        mask, seg_method = pre.segment(image)
        leaf_ratio = round(float(mask.sum()) / mask.size, 3) if mask is not None else None
        would_apply = pre._should_apply(image, mask) if mask is not None else False

        processed, meta = pre.process(image, enabled=True, policy=args.policy)

        # Predict on the raw image and on the (policy-controlled) preprocessed image.
        raw_pred = service.predict(to_bytes(image), preprocess=False)
        proc_pred = service.predict(to_bytes(processed), preprocess=False)

        stem = p.stem
        image.save(out / f"{stem}_1_original.jpg", quality=92)
        if mask is not None:
            mask_overlay(image, mask).save(out / f"{stem}_2_mask.jpg", quality=92)
        processed.save(out / f"{stem}_3_preprocessed.jpg", quality=92)

        print(f"\n{p.name}")
        print(f"  size={image.size}  seg={seg_method}  leaf_ratio={leaf_ratio}  adaptive_would_apply={would_apply}")
        print(f"  policy={args.policy}  applied={meta['applied']}  skipped={meta.get('skipped', False)}")
        print(f"  RAW        : {raw_pred['class']:<36} {raw_pred['confidence']*100:5.1f}%")
        print(f"  PREPROCESS : {proc_pred['class']:<36} {proc_pred['confidence']*100:5.1f}%")
        print(f"  TOP5 (pre) : " + ", ".join(f"{t['class']} {t['percentage']}%" for t in proc_pred["top_predictions"]))
        agree = raw_pred["class"] == proc_pred["class"]
        print(f"  raw vs preprocessed agree: {agree}")

    print("\n" + "=" * 78)
    print(f"Diagnostic images written to: {out.resolve()}")
    print("Open *_2_mask.jpg to check if the mask actually covers the leaf.")
    print("=" * 78)


if __name__ == "__main__":
    main()

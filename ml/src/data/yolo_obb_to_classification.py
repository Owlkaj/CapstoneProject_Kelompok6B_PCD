"""
Convert a YOLOv8-OBB detection dataset into a classification dataset.

Roboflow "YOLOv8 Oriented Object Detection" labels have 9 numbers per line:

    class_id  x1 y1  x2 y2  x3 y3  x4 y4      (all normalised 0..1)

This script turns every oriented box into an axis-aligned crop and saves it as a
per-class image, then writes a manifest compatible with the rest of the pipeline
(`image_path,label,class_name,split,source,is_augmented`).

Design notes:
- Splits from the source (train/valid/test) are preserved (they are already
  per-image, hence leakage-free across splits); run `dedupe_manifest.py`
  afterwards to also drop near-duplicates.
- Boxes that are too small (in pixels OR in relative area) are skipped, because
  upscaling a tiny crop to 224x224 only produces blur — a measured problem in
  Roboflow OBB tomato datasets (~40% of boxes < 1% of the image).
- Classes not present in the TomatCare taxonomy are dropped (e.g. Powdery
  Mildew) unless explicitly mapped.

Usage:
    python -m src.data.yolo_obb_to_classification \
        --root "../dataset yolo/New folder" \
        --out "../ml/dataset/field_yolo" \
        --manifest "../ml/dataset/splits/manifest_yolo.csv" \
        --margin 0.12 --min-box-px 48 --max-boxes-per-image 20
"""
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

import numpy as np
import yaml
from PIL import Image, ImageOps

sys.path.insert(0, str(Path(__file__).resolve().parent.parent.parent))

from src.data.prepare_dataset import CLASS_TO_IDX  # noqa: E402

# Roboflow class name -> TomatCare canonical class name (None = drop the class)
DEFAULT_CLASS_MAP = {
    # --- shared / common -----------------------------------------------------------------
    "healthy": "Healthy",
    "leaf mold": "Leaf Mold",
    "yellow leaf curl virus": "Tomato Yellow Leaf Curl Virus (TYLCV)",
    "tomato yellow leaf curl virus": "Tomato Yellow Leaf Curl Virus (TYLCV)",
    "early blight": "Early Blight",
    "late blight": "Late Blight",
    "mosaic virus": "Tomato Mosaic Virus",
    "tomato mosaic virus": "Tomato Mosaic Virus",
    "septoria": "Septoria Leaf Spot",
    "septoria leaf spot": "Septoria Leaf Spot",
    "spider mites": "Spider Mites",
    "bacterial spot": "Bacterial Spot",
    "target spot": "Target Spot",
    # --- dropped: not part of the 10 TomatCare classes ------------------------------------
    "powdery mildew": None,
    "leaf miner": None,
}

IMAGE_EXTS = {".jpg", ".jpeg", ".png"}


def load_data_yaml(root: Path) -> dict[int, str]:
    """Return {class_id: class_name} from data.yaml (handles list or dict names)."""
    cfg = yaml.safe_load((root / "data.yaml").read_text(encoding="utf-8"))
    names = cfg.get("names", {})
    if isinstance(names, list):
        return {i: str(n) for i, n in enumerate(names)}
    return {int(k): str(v) for k, v in names.items()}


def obb_to_bbox(coords: list[float]):
    """Convert 4 normalised corner points to a normalised axis-aligned bbox."""
    xs = coords[0::2]
    ys = coords[1::2]
    return min(xs), min(ys), max(xs), max(ys)


def resolve_split_dirs(root: Path):
    """Roboflow layout: <split>/images + <split>/labels."""
    out = {}
    for split in ("train", "valid", "test"):
        img_dir = root / split / "images"
        lbl_dir = root / split / "labels"
        if img_dir.is_dir():
            out[split] = (img_dir, lbl_dir)
    return out


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--root", required=True, help="Dataset root containing data.yaml")
    ap.add_argument("--out", required=True, help="Output folder for crops")
    ap.add_argument("--manifest", required=True, help="Output manifest CSV path")
    ap.add_argument("--margin", type=float, default=0.12, help="Relative margin around each box")
    ap.add_argument("--min-box-px", type=int, default=48, help="Skip boxes smaller than this (px, either side)")
    ap.add_argument("--min-area-ratio", type=float, default=0.0005, help="Skip boxes smaller than this fraction of the image")
    ap.add_argument("--max-boxes-per-image", type=int, default=20, help="Keep only the largest N boxes per image")
    ap.add_argument("--supersample", type=int, default=2, help="Upscale small crops by this factor (max)")
    ap.add_argument("--jpeg-quality", type=int, default=92)
    ap.add_argument("--limit", type=int, default=0, help="Only process N images (0 = all) for a quick check")
    args = ap.parse_args()

    root = Path(args.root)
    out = Path(args.out)
    manifest_path = Path(args.manifest)

    class_names = load_data_yaml(root)
    class_map = {}
    for cid, name in class_names.items():
        key = name.strip().lower()
        mapped = DEFAULT_CLASS_MAP.get(key, "__unmapped__")
        class_map[cid] = mapped
        if mapped == "__unmapped__":
            print(f"  [WARN] Class '{name}' is not in the map - it will be DROPPED.")
            class_map[cid] = None

    print("=" * 74)
    print(f"YOLO-OBB -> CLASSIFICATION   root={root}")
    print("=" * 74)
    print("Class mapping:")
    for cid, name in sorted(class_names.items()):
        print(f"  {cid}: {name:<28} -> {class_map[cid]}")
    print()

    splits = resolve_split_dirs(root)
    if not splits:
        print(f"[ERROR] No <split>/images folders found under {root}")
        return

    rows: list[dict] = []
    stats = {
        "images_seen": 0, "images_used": 0, "boxes_total": 0,
        "boxes_kept": 0, "dropped_class": 0, "dropped_small": 0,
        "dropped_degenerate": 0, "dropped_capped": 0, "unreadable": 0,
    }
    per_class: dict[str, int] = {}

    for split, (img_dir, lbl_dir) in splits.items():
        images = sorted(p for p in img_dir.iterdir() if p.suffix.lower() in IMAGE_EXTS)
        if args.limit:
            images = images[: args.limit]

        for img_path in images:
            stats["images_seen"] += 1
            lbl_path = lbl_dir / (img_path.stem + ".txt")
            if not lbl_path.exists():
                continue
            lines = [ln for ln in lbl_path.read_text(encoding="utf-8").strip().splitlines() if ln.strip()]
            if not lines:
                continue

            try:
                with Image.open(img_path) as raw:
                    img = ImageOps.exif_transpose(raw).convert("RGB")
            except Exception as exc:
                stats["unreadable"] += 1
                print(f"  [WARN] unreadable {img_path.name}: {exc}")
                continue

            W, H = img.size
            boxes = []
            for ln in lines:
                parts = ln.split()
                stats["boxes_total"] += 1
                try:
                    cid = int(float(parts[0]))
                    coords = [float(x) for x in parts[1:]]
                except ValueError:
                    stats["dropped_degenerate"] += 1
                    continue

                target = class_map.get(cid)
                if target is None:
                    stats["dropped_class"] += 1
                    continue
                if len(coords) < 8:
                    stats["dropped_degenerate"] += 1
                    continue

                x1, y1, x2, y2 = obb_to_bbox(coords[:8])
                w_px = (x2 - x1) * W
                h_px = (y2 - y1) * H
                if w_px <= 1 or h_px <= 1:
                    stats["dropped_degenerate"] += 1
                    continue
                if w_px < args.min_box_px and h_px < args.min_box_px:
                    stats["dropped_small"] += 1
                    continue
                if (w_px * h_px) / (W * H) < args.min_area_ratio:
                    stats["dropped_small"] += 1
                    continue
                boxes.append((target, x1, y1, x2, y2, w_px * h_px))

            # Keep only the largest N boxes so a single image cannot flood the dataset.
            if len(boxes) > args.max_boxes_per_image:
                boxes.sort(key=lambda b: b[5], reverse=True)
                stats["dropped_capped"] += len(boxes) - args.max_boxes_per_image
                boxes = boxes[: args.max_boxes_per_image]

            if not boxes:
                continue

            stats["images_used"] += 1
            for i, (target, x1, y1, x2, y2, _area) in enumerate(boxes):
                mw = (x2 - x1) * args.margin
                mh = (y2 - y1) * args.margin
                bx1 = max(0, int(round((x1 - mw) * W)))
                by1 = max(0, int(round((y1 - mh) * H)))
                bx2 = min(W, int(round((x2 + mw) * W)))
                by2 = min(H, int(round((y2 + mh) * H)))
                if bx2 <= bx1 or by2 <= by1:
                    stats["dropped_degenerate"] += 1
                    continue

                crop = img.crop((bx1, by1, bx2, by2))
                # Upscale very small crops modestly so they are not pure blur at 224.
                short = min(crop.size)
                if short > 0 and short < 160:
                    factor = min(args.supersample, max(1, 160 // short))
                    if factor > 1:
                        crop = crop.resize((crop.width * factor, crop.height * factor), Image.LANCZOS)

                class_slug = target.replace(" ", "_").replace("(", "").replace(")", "")
                dest_dir = out / "crops" / class_slug / split
                dest_dir.mkdir(parents=True, exist_ok=True)
                dest = dest_dir / f"{img_path.stem}__b{i:02d}.jpg"
                crop.save(dest, quality=args.jpeg_quality)

                rows.append({
                    "image_path": str(dest.resolve()),
                    "label": CLASS_TO_IDX[target],
                    "class_name": target,
                    "split": split if split != "valid" else "val",
                    "source": "field",
                    "is_augmented": False,
                })
                stats["boxes_kept"] += 1
                per_class[target] = per_class.get(target, 0) + 1

        print(f"  [{split}] done - crops so far: {stats['boxes_kept']}")

    if not rows:
        print("\n[ERROR] No crops produced.")
        return

    import pandas as pd

    df = pd.DataFrame(rows)
    manifest_path.parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(manifest_path, index=False)

    print("\n" + "=" * 74)
    print("RINGKASAN")
    print("=" * 74)
    print(f"  gambar dipindai        : {stats['images_seen']}")
    print(f"  gambar menghasilkan crop: {stats['images_used']}")
    print(f"  box total              : {stats['boxes_total']}")
    print(f"  box dipakai (crop)     : {stats['boxes_kept']}")
    print(f"  dibuang - kelas lain   : {stats['dropped_class']}")
    print(f"  dibuang - box kecil    : {stats['dropped_small']}")
    print(f"  dibuang - degenerat    : {stats['dropped_degenerate']}")
    print(f"  dibuang - kena cap     : {stats['dropped_capped']}")
    print(f"  gambar tak terbaca     : {stats['unreadable']}")
    print("\n  crop per kelas:")
    for k, v in sorted(per_class.items(), key=lambda x: -x[1]):
        print(f"    {k:<38} {v}")
    print("\n  crop per split:")
    print(df.groupby(["split", "class_name"]).size().unstack(fill_value=0).to_string())
    print(f"\n  -> manifest: {manifest_path}")
    print(f"  -> crops   : {out / 'crops'}")

    summary = {
        "root": str(root.resolve()),
        "stats": stats,
        "per_class": per_class,
        "class_map": {str(k): v for k, v in class_map.items()},
        "params": {
            "margin": args.margin, "min_box_px": args.min_box_px,
            "min_area_ratio": args.min_area_ratio, "max_boxes_per_image": args.max_boxes_per_image,
            "supersample": args.supersample,
        },
    }
    (out / "conversion_summary.json").write_text(
        json.dumps(summary, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"  -> summary : {out / 'conversion_summary.json'}")


if __name__ == "__main__":
    main()

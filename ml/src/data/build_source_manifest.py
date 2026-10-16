"""
Build a source-tagged manifest from an ImageFolder-style dataset.

Designed for the Skema B workflow (domain-specialised fine-tuning):

    dataset_root/
        train/<class_folder>/...
        valid/<class_folder>/...     (optional)
        test/<class_folder>/...      (optional)

It produces manifests compatible with `TomatoDataset` (columns:
image_path,label,class_name,split) plus two extra columns used for auditing:
`source` (plantvillage | field | unknown) and `source_score` (0..1 confidence).

Source detection is a cheap, dependency-light heuristic calibrated on measured
data (edge colour saturation + edge texture + image size). It is meant to
SEPARATE capture styles, not to be ground truth. Always review a sample.

Usage:
    python -m src.data.build_source_manifest \
        --root "dataset lapangan" --out ml/dataset/splits --tag field \
        --test-ratio 0.15 --seed 42
"""
from __future__ import annotations

import argparse
import json
import random
import sys
from pathlib import Path

import numpy as np
import pandas as pd
from PIL import Image, ImageOps

sys.path.insert(0, str(Path(__file__).resolve().parent.parent.parent))

from src.data.prepare_dataset import CLASS_TO_IDX  # noqa: E402

# Folder-name -> canonical class name (fuzzy, case/space/underscore insensitive)
_FOLDER_ALIASES = {
    "bacterialspot": "Bacterial Spot",
    "bacterialspot": "Bacterial Spot",
    "earlyblight": "Early Blight",
    "healthy": "Healthy",
    "lateblight": "Late Blight",
    "leafmold": "Leaf Mold",
    "septorialeafspot": "Septoria Leaf Spot",
    "spidermites": "Spider Mites",
    "spidermitestwospottedspidermite": "Spider Mites",
    "targetspot": "Target Spot",
    "tomatomosaicvirus": "Tomato Mosaic Virus",
    "tomatoyellowleafcurlvirus": "Tomato Yellow Leaf Curl Virus (TYLCV)",
    "tomatoyellowleafcurlvirustylcv": "Tomato Yellow Leaf Curl Virus (TYLCV)",
}

IMAGE_EXTS = {".jpg", ".jpeg", ".png"}


def canonical_class(folder_name: str) -> str | None:
    key = "".join(ch for ch in folder_name.lower() if ch.isalnum())
    if key in _FOLDER_ALIASES:
        return _FOLDER_ALIASES[key]
    # substring fallback
    for alias, canonical in _FOLDER_ALIASES.items():
        if alias[:8] and alias[:8] in key:
            return canonical
    return None


def domain_features(path: Path) -> tuple[float, float, int, int, float]:
    """(edge_saturation, edge_std, width, height, black_corner_fraction).

    The black-corner fraction detects rotation/padding artefacts left by
    augmentation tools (e.g. Roboflow), which otherwise make a PlantVillage
    image look like a "field" capture.
    """
    with Image.open(path) as raw:
        img = ImageOps.exif_transpose(raw).convert("RGB")
    arr = np.asarray(img, dtype=np.float32)
    h, w = arr.shape[:2]
    k = max(2, int(min(h, w) * 0.10))
    ring = np.concatenate([
        arr[:k].reshape(-1, 3), arr[-k:].reshape(-1, 3),
        arr[:, :k].reshape(-1, 3), arr[:, -k:].reshape(-1, 3),
    ])
    mx, mn = ring.max(1), ring.min(1)
    sat = float(np.mean(np.where(mx > 0, (mx - mn) / np.maximum(mx, 1e-6), 0.0)))
    std = float(ring.std(0).mean())

    kc = max(2, int(min(h, w) * 0.06))
    corners = np.concatenate([
        arr[:kc, :kc].reshape(-1, 3), arr[:kc, -kc:].reshape(-1, 3),
        arr[-kc:, :kc].reshape(-1, 3), arr[-kc:, -kc:].reshape(-1, 3),
    ])
    black_frac = float((corners.max(axis=1) < 25).mean())
    return sat, std, w, h, black_frac


# Calibrated on measured data: PlantVillage ~0.13 sat / ~33 std, field ~0.42 sat / ~59 std.
FIELD_SAT_THRESHOLD = 0.30
FIELD_STD_THRESHOLD = 46.0
AUG_ARTIFACT_BLACK_FRACTION = 0.02


def classify_source(sat: float, std: float, black_frac: float = 0.0) -> tuple[str, float]:
    # Rotation/padding artefacts betray an augmented PlantVillage image, not a
    # genuine field capture — regardless of how "colourful" its border looks.
    if black_frac > AUG_ARTIFACT_BLACK_FRACTION:
        return "augmented", 0.9

    score = max(
        min(1.0, max(0.0, (sat - 0.10) / 0.35)),
        min(1.0, max(0.0, (std - 25.0) / 40.0)),
    )
    if sat >= FIELD_SAT_THRESHOLD or std >= FIELD_STD_THRESHOLD:
        return "field", round(score, 3)
    if sat <= 0.20 and std <= 38.0:
        return "plantvillage", round(1.0 - score, 3)
    return "unknown", round(score, 3)


def scan(root: Path) -> pd.DataFrame:
    rows = []
    for split_dir in sorted(p for p in root.iterdir() if p.is_dir()):
        for class_dir in sorted(p for p in split_dir.iterdir() if p.is_dir()):
            canonical = canonical_class(class_dir.name)
            if canonical is None:
                print(f"  [WARN] Unmapped class folder, skipped: {class_dir.name}")
                continue
            for img in class_dir.iterdir():
                if img.suffix.lower() not in IMAGE_EXTS:
                    continue
                try:
                    sat, std, w, h, black_frac = domain_features(img)
                except Exception as exc:
                    print(f"  [WARN] Unreadable, skipped: {img.name} ({exc})")
                    continue
                src, score = classify_source(sat, std, black_frac)
                rows.append({
                    "image_path": str(img.resolve()),
                    "label": CLASS_TO_IDX[canonical],
                    "class_name": canonical,
                    "split": split_dir.name,
                    "source": src,
                    "source_score": score,
                    "edge_sat": round(sat, 4),
                    "edge_std": round(std, 2),
                    "black_corner_frac": round(black_frac, 4),
                    "width": w,
                    "height": h,
                })
    return pd.DataFrame(rows)


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--root", required=True, help="ImageFolder-style dataset root")
    ap.add_argument("--out", required=True, help="Output directory for manifests")
    ap.add_argument("--tag", default="field", help="Tag used in output file names")
    ap.add_argument("--test-ratio", type=float, default=0.15,
                    help="Fraction of the 'valid' split reassigned to test")
    ap.add_argument("--seed", type=int, default=42)
    args = ap.parse_args()

    root = Path(args.root)
    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    random.seed(args.seed)

    print("=" * 70)
    print(f"BUILD SOURCE MANIFEST  root={root}")
    print("=" * 70)

    df = scan(root)
    if df.empty:
        print("[ERROR] No images found.")
        return

    print(f"Scanned {len(df)} images")
    print("\nSource distribution:")
    print(df["source"].value_counts().to_string())
    print("\nPer-class / source:")
    print(df.groupby(["class_name", "source"]).size().unstack(fill_value=0).to_string())

    # Reassign part of the 'valid' split to 'test' (keeps a real held-out set)
    valid_mask = df["split"].isin(["valid", "val"])
    valid_idx = df.index[valid_mask].tolist()
    random.shuffle(valid_idx)
    n_test = int(round(len(valid_idx) * args.test_ratio))
    df.loc[valid_idx[:n_test], "split"] = "test"
    df.loc[valid_idx[n_test:], "split"] = "val"
    df.loc[df["split"] == "train", "split"] = "train"

    print("\nFinal split distribution:")
    print(df.groupby(["split", "source"]).size().unstack(fill_value=0).to_string())

    cols = ["image_path", "label", "class_name", "split", "source", "source_score",
            "edge_sat", "edge_std", "black_corner_frac", "width", "height"]

    combined = out / f"manifest_{args.tag}.csv"
    df[cols].to_csv(combined, index=False)
    print(f"\n  -> {combined}")

    for src in ("plantvillage", "field", "augmented"):
        sub = df[df["source"] == src]
        if len(sub):
            p = out / f"manifest_{args.tag}_{src}.csv"
            sub[cols].to_csv(p, index=False)
            print(f"  -> {p}  ({len(sub)} rows)")

    unknown = df[df["source"] == "unknown"]
    if len(unknown):
        amb = out / f"manifest_{args.tag}_ambiguous.csv"
        unknown[cols].to_csv(amb, index=False)
        print(f"  -> {amb}  ({len(unknown)} rows need manual review)")

    summary = {
        "root": str(root.resolve()),
        "total": int(len(df)),
        "by_source": df["source"].value_counts().to_dict(),
        "by_split": df["split"].value_counts().to_dict(),
        "thresholds": {"sat": FIELD_SAT_THRESHOLD, "std": FIELD_STD_THRESHOLD},
        "seed": args.seed,
    }
    (out / f"manifest_{args.tag}_summary.json").write_text(
        json.dumps(summary, indent=2, ensure_ascii=False), encoding="utf-8"
    )
    print("\nDone.")


if __name__ == "__main__":
    main()

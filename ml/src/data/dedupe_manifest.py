"""
Remove near-duplicate leakage across manifest splits.

For every image in `val`/`test`, compare a 16x16 average-hash against the
`train` split (and against earlier rows) and drop it when the Hamming distance
is below the threshold. Dropping from val/test (never from train) keeps the
evaluation honest while preserving training data.

Usage:
    python -m src.data.dedupe_manifest --manifest dataset/splits/manifest_field_only.csv \
        --out dataset/splits/manifest_field_only_clean.csv --threshold 4
"""
from __future__ import annotations

import argparse
from pathlib import Path

import numpy as np
import pandas as pd
from PIL import Image, ImageOps


def ahash(path: str, size: int = 16) -> np.ndarray:
    with Image.open(path) as im:
        img = ImageOps.exif_transpose(im).convert("L").resize((size, size), Image.BILINEAR)
    arr = np.asarray(img, dtype=np.float32)
    return (arr > arr.mean()).flatten()


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--manifest", required=True)
    ap.add_argument("--out", required=True)
    ap.add_argument("--threshold", type=int, default=4, help="Max Hamming distance treated as a duplicate")
    args = ap.parse_args()

    df = pd.read_csv(args.manifest)
    df["_hash"] = [ahash(p) for p in df["image_path"]]

    train_h = df.loc[df["split"] == "train", "_hash"].tolist()
    keep, dropped = [], []
    seen_val_test: list[np.ndarray] = []

    for _, row in df.iterrows():
        h = row["_hash"]
        if row["split"] == "train":
            keep.append(row)
            continue
        dist_train = min((np.count_nonzero(h != t) for t in train_h), default=999)
        dist_peer = min((np.count_nonzero(h != t) for t in seen_val_test), default=999)
        if dist_train <= args.threshold or dist_peer <= args.threshold:
            dropped.append(row)
        else:
            keep.append(row)
            seen_val_test.append(h)

    out = pd.DataFrame(keep).drop(columns=["_hash"])
    out.to_csv(args.out, index=False)

    print(f"Input rows : {len(df)}")
    print(f"Dropped    : {len(dropped)} (near-duplicates leaked from train/val)")
    print(f"Kept rows  : {len(out)}")
    print("\nSplit distribution after cleanup:")
    print(out.groupby(["split", "class_name"]).size().unstack(0).fillna(0).astype(int).to_string())
    if dropped:
        print("\nExamples dropped:")
        for r in dropped[:10]:
            print(f"  [{r['split']}] {Path(r['image_path']).name}")


if __name__ == "__main__":
    main()

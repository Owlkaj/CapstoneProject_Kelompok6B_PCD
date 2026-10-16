"""
Tag an existing manifest with the same `source` columns as
`build_source_manifest.py`, so PlantVillage and field manifests share one schema.

Usage:
    python -m src.data.tag_manifest_source \
        --manifest ml/dataset/splits/manifest.csv \
        --out ml/dataset/splits/manifest_pv.csv \
        --source plantvillage
"""
from __future__ import annotations

import argparse
import sys
from pathlib import Path

import pandas as pd

sys.path.insert(0, str(Path(__file__).resolve().parent.parent.parent))

from src.data.build_source_manifest import domain_features, classify_source  # noqa: E402


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--manifest", required=True)
    ap.add_argument("--out", required=True)
    ap.add_argument("--source", default=None,
                    help="Force this source label (e.g. plantvillage). If omitted, it is inferred.")
    ap.add_argument("--limit", type=int, default=0, help="Only process N rows (0 = all) for quick checks")
    args = ap.parse_args()

    df = pd.read_csv(args.manifest)
    if args.limit:
        df = df.head(args.limit).copy()

    sources, scores, sats, stds, blacks, ws, hs = [], [], [], [], [], [], []
    for i, row in df.iterrows():
        p = Path(row["image_path"])
        try:
            sat, std, w, h, black_frac = domain_features(p)
        except Exception:
            sat, std, w, h, black_frac = float("nan"), float("nan"), 0, 0, float("nan")
        if args.source:
            src, score = args.source, 1.0
        else:
            src, score = classify_source(sat, std, black_frac)
        sources.append(src); scores.append(score)
        sats.append(round(sat, 4) if sat == sat else None)
        stds.append(round(std, 2) if std == std else None)
        blacks.append(round(black_frac, 4) if black_frac == black_frac else None)
        ws.append(w); hs.append(h)
        if (i + 1) % 2000 == 0:
            print(f"  processed {i+1}/{len(df)}")

    df["source"] = sources
    df["source_score"] = scores
    df["edge_sat"] = sats
    df["edge_std"] = stds
    df["black_corner_frac"] = blacks
    df["width"] = ws
    df["height"] = hs

    cols = ["image_path", "label", "class_name", "split", "source", "source_score",
            "edge_sat", "edge_std", "black_corner_frac", "width", "height"]
    Path(args.out).parent.mkdir(parents=True, exist_ok=True)
    df[cols].to_csv(args.out, index=False)
    print(f"Wrote {args.out} ({len(df)} rows)")
    print(df["source"].value_counts().to_string())


if __name__ == "__main__":
    main()

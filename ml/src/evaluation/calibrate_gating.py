"""
Calibrate confidence-gating thresholds from labelled test data.

Measures, for every test image: top-1 confidence, top1-top2 margin, sharpness
(variance of Laplacian), brightness and resolution — then reports how well each
signal separates correct from incorrect predictions.

Usage (from ml/):
    ..\ml\.venv\Scripts\python.exe -m src.evaluation.calibrate_gating \
        --model models/model_yolo3_mixed.pt \
        --manifest dataset/splits/manifest_yolo3_clean.csv
"""
from __future__ import annotations

import argparse
import io
import json
import sys
from pathlib import Path

import numpy as np
import pandas as pd
from PIL import Image, ImageOps

sys.path.insert(0, str(Path(__file__).resolve().parent.parent.parent))

API_DIR = Path(__file__).resolve().parent.parent.parent.parent / "fastapi"
sys.path.insert(0, str(API_DIR))

from app.services.inference import InferenceService  # noqa: E402


def sharpness(gray: np.ndarray) -> float:
    """Variance of the Laplacian — classic blur detector."""
    try:
        from scipy import ndimage
        return float(ndimage.laplace(gray.astype(np.float32)).var())
    except Exception:
        gx = np.diff(gray.astype(np.float32), axis=1)
        gy = np.diff(gray.astype(np.float32), axis=0)
        return float((gx ** 2).mean() + (gy ** 2).mean())


def features(image: Image.Image) -> dict:
    gray = np.asarray(image.convert("L"))
    return {
        "sharpness": sharpness(gray),
        "brightness": float(gray.mean()),
        "min_side": int(min(image.size)),
    }


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--model", required=True)
    ap.add_argument("--manifest", required=True)
    ap.add_argument("--split", default="test")
    ap.add_argument("--limit", type=int, default=800)
    args = ap.parse_args()

    root = Path(__file__).resolve().parent.parent.parent.parent
    ML_ROOT = root / "ml"

    def _resolve(p: str) -> Path:
        cand = Path(p)
        if cand.is_absolute() and cand.exists():
            return cand
        for base in (Path.cwd(), root, ML_ROOT):
            alt = base / cand
            if alt.exists():
                return alt
        return cand

    svc = InferenceService(
        model_path=str(_resolve(args.model)),
        label_mapping_path=str(root / "fastapi" / "models" / "label_mapping.json"),
        preprocessing_path=str(root / "fastapi" / "models" / "preprocessing.json"),
        device_name="cpu",
    )
    svc.load()

    df = pd.read_csv(_resolve(args.manifest))
    df = df[df["split"] == args.split]
    if args.limit:
        df = df.sample(n=min(args.limit, len(df)), random_state=0).reset_index(drop=True)

    rows = []
    for i, r in df.iterrows():
        p = Path(r["image_path"])
        img = ImageOps.exif_transpose(Image.open(p)).convert("RGB")
        buf = io.BytesIO()
        img.save(buf, format="JPEG", quality=92)
        out = svc.predict(buf.getvalue(), preprocess=False)

        probs = sorted(out["probabilities"].values(), reverse=True)
        margin = probs[0] - probs[1] if len(probs) > 1 else 0.0
        f = features(img)
        rows.append({
            "correct": int(out["class"] == r["class_name"]),
            "confidence": out["confidence"],
            "margin": margin,
            "class": out["class"],
            "truth": r["class_name"],
            **f,
        })
        if (i + 1) % 100 == 0:
            print(f"  {i+1}/{len(df)}")

    d = pd.DataFrame(rows)
    acc = d["correct"].mean()
    print(f"\n{'='*72}\nCALIBRATION  n={len(d)}  accuracy={acc*100:.2f}%\n{'='*72}")

    def report(col):
        ok = d[d.correct == 1][col]
        bad = d[d.correct == 0][col]
        print(f"\n{col}:")
        print(f"  benar  : mean={ok.mean():.4f}  p10={ok.quantile(.10):.4f}  p25={ok.quantile(.25):.4f}  median={ok.median():.4f}")
        if len(bad):
            print(f"  salah  : mean={bad.mean():.4f}  p10={bad.quantile(.10):.4f}  p75={bad.quantile(.75):.4f}  median={bad.median():.4f}")

    for c in ("confidence", "margin", "sharpness", "brightness", "min_side"):
        report(c)

    # Sweep single thresholds: what fraction of WRONG predictions would be caught,
    # at the cost of how many CORRECT ones?
    print(f"\n{'-'*72}\nSWEEP confidence threshold (gate = confidence >= t)\n{'-'*72}")
    print(f"{'t':>6} {'wrong_caught':>13} {'correct_lost':>13} {'reject_rate':>12} {'acc_of_accepted':>16}")
    for t in [0.30, 0.40, 0.50, 0.55, 0.60, 0.70, 0.80, 0.90]:
        accepted = d[d.confidence >= t]
        rejected_wrong = d[(d.confidence < t) & (d.correct == 0)]
        rejected_right = d[(d.confidence < t) & (d.correct == 1)]
        acc_acc = accepted.correct.mean() if len(accepted) else float("nan")
        print(f"{t:>6.2f} {len(rejected_wrong):>13} {len(rejected_right):>13} "
              f"{(len(d)-len(accepted))/len(d)*100:>11.1f}% {acc_acc*100:>15.2f}%")

    out_dir = ML_ROOT / "results"
    out_dir.mkdir(parents=True, exist_ok=True)
    (out_dir / "gating_calibration.json").write_text(
        json.dumps({"n": len(d), "accuracy": float(acc),
                    "summary": {c: {"correct_mean": float(d[d.correct == 1][c].mean()),
                                    "wrong_mean": float(d[d.correct == 0][c].mean()) if (d.correct == 0).any() else None}
                                for c in ("confidence", "margin", "sharpness", "brightness", "min_side")}},
                   indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"\nSaved: {out_dir / 'gating_calibration.json'}")


if __name__ == "__main__":
    main()

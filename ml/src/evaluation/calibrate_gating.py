"""
Calibrate confidence-gating thresholds from labelled validation data.

Measures, for every validation image: top-1 confidence, top1-top2 margin,
sharpness (variance of Laplacian), brightness and resolution — then reports how
well each signal separates correct from incorrect predictions without touching
the held-out test split.

Usage (from ml/):
    ..\ml\.venv\Scripts\python.exe -m src.evaluation.calibrate_gating \
        --model models/model_yolo3_grouped.pt \
        --manifest dataset/splits/manifest_field_only_clean.csv \
        --split val
"""
from __future__ import annotations

import argparse
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
    ap.add_argument("--split", default="val", help="Split used for calibration (default: val; avoid test)")
    ap.add_argument("--limit", type=int, default=0, help="Max samples (0 = all rows in split)")
    ap.add_argument("--device", default="auto", help="Inference device (auto, directml, cpu)")
    args = ap.parse_args()

    if args.split == "test":
        print("[WARNING] Calibrating on 'test' split leaks threshold selection into final evaluation. "
              "Use --split val for threshold selection.")

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

    model_path = _resolve(args.model)
    manifest_path = _resolve(args.manifest)

    svc = InferenceService(
        model_path=str(model_path),
        label_mapping_path=str(root / "fastapi" / "models" / "label_mapping.json"),
        preprocessing_path=str(root / "fastapi" / "models" / "preprocessing.json"),
        device_name=args.device,
    )
    svc.load()

    df = pd.read_csv(manifest_path)
    df = df[df["split"] == args.split].reset_index(drop=True)
    if args.limit and args.limit > 0:
        df = df.sample(n=min(args.limit, len(df)), random_state=0).reset_index(drop=True)

    rows = []
    for i, r in df.iterrows():
        p = _resolve(str(r["image_path"]))
        raw_bytes = p.read_bytes()
        img = ImageOps.exif_transpose(Image.open(p)).convert("RGB")
        out = svc.predict(raw_bytes, preprocess=False)

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
        if (i + 1) % 100 == 0 or (i + 1) == len(df):
            print(f"  {i+1}/{len(df)}")

    d = pd.DataFrame(rows)
    acc = float(d["correct"].mean())
    n_correct = int((d["correct"] == 1).sum())
    n_wrong = int((d["correct"] == 0).sum())
    print(f"\n{'='*76}\nCALIBRATION ({args.split.upper()})  n={len(d)} (benar={n_correct}, salah={n_wrong})  accuracy={acc*100:.2f}%\n{'='*76}")

    def report(col: str) -> dict:
        ok = d[d.correct == 1][col]
        bad = d[d.correct == 0][col]
        print(f"\n{col}:")
        print(f"  benar  : mean={ok.mean():.4f}  p10={ok.quantile(.10):.4f}  p25={ok.quantile(.25):.4f}  median={ok.median():.4f}")
        stats = {
            "correct_mean": float(ok.mean()),
            "correct_p10": float(ok.quantile(0.10)),
            "correct_p25": float(ok.quantile(0.25)),
            "correct_median": float(ok.median()),
            "wrong_mean": None,
            "wrong_p10": None,
            "wrong_p75": None,
            "wrong_median": None,
        }
        if len(bad):
            print(f"  salah  : mean={bad.mean():.4f}  p10={bad.quantile(.10):.4f}  p75={bad.quantile(.75):.4f}  median={bad.median():.4f}")
            stats.update({
                "wrong_mean": float(bad.mean()),
                "wrong_p10": float(bad.quantile(0.10)),
                "wrong_p75": float(bad.quantile(0.75)),
                "wrong_median": float(bad.median()),
            })
        return stats

    summary_stats = {}
    for c in ("confidence", "margin", "sharpness", "brightness", "min_side"):
        summary_stats[c] = report(c)

    # Sweep single thresholds: what fraction of WRONG predictions would be caught,
    # at the cost of how many CORRECT ones?
    print(f"\n{'-'*76}\nSWEEP confidence threshold (gate = confidence >= t)\n{'-'*76}")
    print(f"{'t':>6} {'wrong_caught':>18} {'correct_lost':>18} {'flag_rate':>11} {'acc_accepted':>14}")
    sweep_rows = []
    for t in [0.30, 0.40, 0.45, 0.50, 0.55, 0.60, 0.65, 0.70, 0.80, 0.90]:
        accepted = d[d.confidence >= t]
        rejected_wrong = int(len(d[(d.confidence < t) & (d.correct == 0)]))
        rejected_right = int(len(d[(d.confidence < t) & (d.correct == 1)]))
        acc_acc = float(accepted.correct.mean()) if len(accepted) else float("nan")
        wrong_pct = (rejected_wrong / n_wrong * 100.0) if n_wrong else 0.0
        right_pct = (rejected_right / n_correct * 100.0) if n_correct else 0.0
        flag_rate = (len(d) - len(accepted)) / len(d) * 100.0
        print(
            f"{t:>6.2f} "
            f"{rejected_wrong:>4}/{n_wrong:<3} ({wrong_pct:>5.1f}%) "
            f"{rejected_right:>4}/{n_correct:<4} ({right_pct:>4.1f}%) "
            f"{flag_rate:>10.1f}% "
            f"{acc_acc*100:>13.2f}%"
        )
        sweep_rows.append({
            "threshold": t,
            "wrong_caught": rejected_wrong,
            "wrong_caught_pct": round(wrong_pct, 2),
            "correct_lost": rejected_right,
            "correct_lost_pct": round(right_pct, 2),
            "flag_rate_pct": round(flag_rate, 2),
            "acc_of_accepted": round(acc_acc, 4),
        })

    out_dir = ML_ROOT / "results"
    out_dir.mkdir(parents=True, exist_ok=True)
    payload = {
        "model": model_path.name,
        "manifest": manifest_path.name,
        "split": args.split,
        "n": len(d),
        "n_correct": n_correct,
        "n_wrong": n_wrong,
        "accuracy": round(acc, 6),
        "recommended_threshold": 0.60,
        "summary": summary_stats,
        "sweep": sweep_rows,
    }
    (out_dir / "gating_calibration.json").write_text(
        json.dumps(payload, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )
    print(f"\nSaved: {out_dir / 'gating_calibration.json'}")


if __name__ == "__main__":
    main()

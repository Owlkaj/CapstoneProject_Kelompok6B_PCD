"""
Compare two model checkpoints on a folder of images (per-image, both domains).

Useful for "before vs after" checks: baseline E02 vs the field-adapted model.

Usage:
    ..\\ml\\.venv\\Scripts\\python.exe tools\\compare_models.py "..\\scratch\\bacterial spot" ^
        --model-a ..\\ml\\models\\final_model.pt ^
        --model-b ..\\ml\\models\\model_field_mixed.pt ^
        --label-a "Baseline(E02)" --label-b "Varian B"
"""
import argparse
import io
import sys
from pathlib import Path

from PIL import Image, ImageOps

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from app.services.inference import InferenceService

EXTS = {".jpg", ".jpeg", ".png"}


def to_bytes(img: Image.Image) -> bytes:
    buf = io.BytesIO()
    img.save(buf, format="JPEG", quality=92)
    return buf.getvalue()


def build(model_path: str) -> InferenceService:
    root = Path(__file__).resolve().parent.parent
    svc = InferenceService(
        model_path=model_path,
        label_mapping_path=str(root / "models" / "label_mapping.json"),
        preprocessing_path=str(root / "models" / "preprocessing.json"),
        device_name="cpu",
    )
    svc.load()
    return svc


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("folder")
    ap.add_argument("--model-a", required=True)
    ap.add_argument("--model-b", required=True)
    ap.add_argument("--label-a", default="A")
    ap.add_argument("--label-b", default="B")
    ap.add_argument("--truth", default=None, help="Optional ground-truth class applied to all images")
    ap.add_argument("--preprocess", default="false", choices=["true", "false"],
                    help="Force leaf preprocessing on/off for both models")
    args = ap.parse_args()

    folder = Path(args.folder)
    files = [p for p in sorted(folder.iterdir()) if p.suffix.lower() in EXTS]
    if not files:
        print(f"[ERROR] No images in {folder}")
        return

    svc_a = build(args.model_a)
    svc_b = build(args.model_b)
    use_pre = args.preprocess == "true"

    print("\n" + "=" * 92)
    print(f"COMPARE  {len(files)} image(s)   preprocessing={use_pre}")
    print(f"  A = {args.label_a}   ({args.model_a})")
    print(f"  B = {args.label_b}   ({args.model_b})")
    print("=" * 92)

    hits_a = hits_b = 0
    for p in files:
        img = ImageOps.exif_transpose(Image.open(p)).convert("RGB")
        raw = to_bytes(img)
        a = svc_a.predict(raw, preprocess=use_pre)
        b = svc_b.predict(raw, preprocess=use_pre)
        truth = args.truth
        mark_a = mark_b = ""
        if truth:
            mark_a = "OK" if a["class"] == truth else "X"
            mark_b = "OK" if b["class"] == truth else "X"
            hits_a += a["class"] == truth
            hits_b += b["class"] == truth
        print(f"\n{p.name}")
        print(f"  A {args.label_a:<14}: {a['class']:<34} {a['confidence']*100:5.1f}% {mark_a}")
        print(f"  B {args.label_b:<14}: {b['class']:<34} {b['confidence']*100:5.1f}% {mark_b}")

    if args.truth:
        n = len(files)
        print("\n" + "=" * 92)
        print(f"GROUND TRUTH = {args.truth}")
        print(f"  {args.label_a}: {hits_a}/{n}")
        print(f"  {args.label_b}: {hits_b}/{n}")
        print("=" * 92)


if __name__ == "__main__":
    main()

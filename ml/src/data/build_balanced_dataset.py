"""
Build a balanced, domain-tagged dataset for Skema B.

Goal (as specified):
    - 2,000 images per class from the OLD PlantVillage dataset  (20,000 total)
    - 2,000 images per class from the NEW field-only dataset    (20,000 total)
    -                                       combined total = 40,000

Per class, for the target (field) half:
    * if more than the target exist -> randomly trim (seeded)
    * if fewer than the target exist -> oversample (and optionally write
      augmented copies) so the class reaches the target

Splits are GROUP-AWARE. Every crop cut from one source image shares a
`group_id`; all crops of that image go to exactly one split (train/val/test),
so a leaf cannot appear in both train and test. The assignment is done globally
across classes with `StratifiedGroupKFold`, then capped per class to the target
counts. Old manifests without `group_id` are supported: the id is inferred from
the file name (`<source-stem>__b<box>.jpg`).

Two augmentation modes:
    rows    (default) : duplicate manifest rows; augmentation happens on-the-fly
                        during training (TomatoDataset + get_train_transforms).
                        No disk bloat, different augmentation every epoch.
    offline           : physically write augmented JPEGs to disk.

Augmentation is applied to the TRAIN split only. Validation/test always use the
real, un-augmented images so metrics stay honest.

Usage:
    # report only (no files written)
    python -m src.data.build_balanced_dataset --field-manifest ml/dataset/splits/manifest_field_field.csv \
        --pv-manifest ml/dataset/splits/manifest.csv --out ml/dataset/splits --dry-run

    # actually build (oversampling rows)
    python -m src.data.build_balanced_dataset ... --augment-mode rows

    # build with physical augmented files
    python -m src.data.build_balanced_dataset ... --augment-mode offline
"""
from __future__ import annotations

import argparse
import json
import random
import sys
from pathlib import Path

import pandas as pd
from PIL import Image, ImageEnhance, ImageOps
from sklearn.model_selection import StratifiedGroupKFold

sys.path.insert(0, str(Path(__file__).resolve().parent.parent.parent))

from src.data.prepare_dataset import CLASS_TO_IDX, IDX_TO_CLASS  # noqa: E402

TRAIN_RATIO, VAL_RATIO, TEST_RATIO = 0.8, 0.1, 0.1


def _infer_group_id(image_path: str) -> str:
    """Best-effort group id for manifests created before `group_id` existed.

    YOLO crops are named `<source-image-stem>__b<box-index>.jpg`, so stripping
    the `__b...` suffix recovers the original image. For ordinary photos the
    file name itself is already unique.
    """
    return Path(str(image_path)).name.split("__b")[0]


def _row_group_id(row) -> str:
    gid = row.get("group_id")
    if pd.notna(gid) and str(gid).strip():
        return str(gid)
    return _infer_group_id(row["image_path"])


def _count_group_overlaps(rows: list[dict]) -> int:
    """Return how many source-image groups appear in more than one split."""
    split_by_group: dict[str, str] = {}
    leaked: set[str] = set()
    for row in rows:
        gid = str(row.get("group_id") or _infer_group_id(row["image_path"]))
        split = row["split"]
        if gid in split_by_group and split_by_group[gid] != split:
            leaked.add(gid)
        else:
            split_by_group.setdefault(gid, split)
    return len(leaked)


def _assign_group_splits(df: pd.DataFrame, seed: int) -> pd.DataFrame:
    """Assign every source-image group to train/val/test, globally.

    A single YOLO/field image can yield many crops. A random crop-level split
    puts crops of the same leaf in both train and test, which leaks information
    and inflates the test score. `StratifiedGroupKFold` keeps every `group_id`
    in exactly one split while trying to preserve the class distribution.

    Fold 0 -> test, fold 1 -> validation, folds 2..9 -> train (80/10/10).
    """
    df = df.copy()
    if "group_id" not in df.columns:
        df["group_id"] = df["image_path"].map(_infer_group_id)

    # One row per source image; use its most frequent class for stratification.
    group_df = (
        df.groupby("group_id", as_index=False)
        .agg(label=("label", lambda s: int(s.mode().iloc[0])))
    )
    n_groups = len(group_df)
    min_class_groups = int(group_df.groupby("label").size().min()) if n_groups else 0
    n_splits = min(10, n_groups, min_class_groups)

    if n_splits < 3:
        # Too few independent source images for an honest 3-way split.
        print(f"  [WARN] only {n_groups} source image group(s) / "
              f"{min_class_groups} in the smallest class; val/test left empty "
              f"to avoid leakage.")
        df["_assigned_split"] = "train"
        return df

    sgkf = StratifiedGroupKFold(n_splits=n_splits, shuffle=True, random_state=seed)
    group_df["fold"] = -1
    for fold, (_, val_idx) in enumerate(
        sgkf.split(group_df, group_df["label"], group_df["group_id"])
    ):
        group_df.loc[val_idx, "fold"] = fold

    fold_map = dict(zip(group_df["group_id"], group_df["fold"]))
    df["_fold"] = df["group_id"].map(fold_map)
    df["_assigned_split"] = df["_fold"].map(
        lambda f: "test" if f == 0 else ("val" if f == 1 else "train")
    )
    return df


def _augment_image(img: Image.Image, rng: random.Random) -> Image.Image:
    """Light, label-preserving augmentation (PIL only, no extra deps)."""
    if rng.random() < 0.5:
        img = ImageOps.mirror(img)
    if rng.random() < 0.3:
        img = ImageOps.flip(img)
    if rng.random() < 0.8:
        img = img.rotate(rng.uniform(-25, 25), resample=Image.BILINEAR, expand=False)
    if rng.random() < 0.5:
        img = ImageEnhance.Brightness(img).enhance(rng.uniform(0.8, 1.25))
    if rng.random() < 0.5:
        img = ImageEnhance.Contrast(img).enhance(rng.uniform(0.8, 1.25))
    if rng.random() < 0.4:
        img = ImageEnhance.Color(img).enhance(rng.uniform(0.85, 1.15))
    if rng.random() < 0.3:
        w, h = img.size
        scale = rng.uniform(0.85, 1.0)
        nw, nh = int(w * scale), int(h * scale)
        x = rng.randint(0, w - nw)
        y = rng.randint(0, h - nh)
        img = img.crop((x, y, x + nw, y + nh)).resize((w, h), Image.BILINEAR)
    return img


def process_source(
    name: str,
    df: pd.DataFrame,
    target: int,
    out_dir: Path,
    augment_mode: str,
    val_cap: int,
    test_cap: int,
    seed: int,
    dry_run: bool,
) -> tuple[list[dict], dict]:
    rows: list[dict] = []
    report: dict = {}

    # Assign source-image groups across ALL classes first, so one photo can
    # never appear in train for one class and in test for another.
    if "_assigned_split" not in df.columns:
        df = _assign_group_splits(df, seed)

    for cls, cls_df in df.groupby("class_name"):
        label = CLASS_TO_IDX[cls]

        # Target counts per split (80/10/10 of `target`).
        t_train = int(round(target * TRAIN_RATIO))
        t_val = int(round(target * VAL_RATIO))
        t_test = target - t_train - t_val

        # Split comes from the global group assignment; cap per class to keep
        # the balanced target. Capping only drops rows, it never moves a group
        # into another split.
        train_df = cls_df[cls_df["_assigned_split"] == "train"]
        val_df = cls_df[cls_df["_assigned_split"] == "val"]
        test_df = cls_df[cls_df["_assigned_split"] == "test"]
        train_df = train_df.iloc[:t_train] if len(train_df) > t_train else train_df
        val_df = val_df.iloc[:t_val] if len(val_df) > t_val else val_df
        test_df = test_df.iloc[:t_test] if len(test_df) > t_test else test_df

        real_train = len(train_df)
        used_total = real_train + len(val_df) + len(test_df)
        need_train = max(0, t_train - real_train)
        trimmed = max(0, len(cls_df) - used_total)

        aug_written = 0
        for _, r in train_df.iterrows():
            rows.append({"image_path": r["image_path"], "label": label, "class_name": cls,
                         "split": "train", "source": name, "is_augmented": False,
                         "group_id": _row_group_id(r)})
        for _, r in val_df.iterrows():
            rows.append({"image_path": r["image_path"], "label": label, "class_name": cls,
                         "split": "val", "source": name, "is_augmented": False,
                         "group_id": _row_group_id(r)})
        for _, r in test_df.iterrows():
            rows.append({"image_path": r["image_path"], "label": label, "class_name": cls,
                         "split": "test", "source": name, "is_augmented": False,
                         "group_id": _row_group_id(r)})

        if need_train and len(train_df) > 0:
            if augment_mode == "offline" and not dry_run:
                dest = out_dir / "augmented" / name / cls.replace(" ", "_").replace("/", "_")
                dest.mkdir(parents=True, exist_ok=True)
                rng = random.Random(seed)
                pool = train_df.to_dict("records")
                for i in range(need_train):
                    src_row = pool[i % len(pool)]
                    try:
                        with Image.open(src_row["image_path"]) as im:
                            aug = _augment_image(ImageOps.exif_transpose(im).convert("RGB"), rng)
                        aug.save(dest / f"aug_{i:05d}.jpg", quality=92)
                        rows.append({"image_path": str((dest / f'aug_{i:05d}.jpg').resolve()),
                                     "label": label, "class_name": cls, "split": "train",
                                     "source": name, "is_augmented": True,
                                     "group_id": _row_group_id(src_row)})
                        aug_written += 1
                    except Exception:
                        pass
            else:
                # Oversampling: repeat existing rows; on-the-fly augmentation
                # in get_train_transforms makes each repeat differ each epoch.
                pool = train_df.to_dict("records")
                for i in range(need_train):
                    src_row = pool[i % len(pool)]
                    rows.append({"image_path": src_row["image_path"], "label": label,
                                 "class_name": cls, "split": "train", "source": name,
                                 "is_augmented": True,
                                 "group_id": _row_group_id(src_row)})

        report[cls] = {
            "real_available": int(len(cls_df)),
            "real_train": real_train,
            "train_target": t_train,
            "trimmed": int(trimmed),
            "augmented_needed": int(need_train),
            "augmented_written": int(aug_written),
            "val_used": int(len(val_df)),
            "test_used": int(len(test_df)),
            "duplication_factor": round(t_train / real_train, 2) if real_train else None,
        }

    return rows, report


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--field-manifest", required=True)
    ap.add_argument("--pv-manifest", required=True)
    ap.add_argument("--out", required=True)
    ap.add_argument("--per-class", type=int, default=2000, help="Target images per class per domain")
    ap.add_argument("--augment-mode", choices=["rows", "offline"], default="rows")
    ap.add_argument("--val-cap", type=int, default=200)
    ap.add_argument("--test-cap", type=int, default=200)
    ap.add_argument("--seed", type=int, default=42)
    ap.add_argument("--dry-run", action="store_true")
    args = ap.parse_args()

    out = Path(args.out)
    print("=" * 74)
    print(f"BUILD BALANCED DATASET   target/class={args.per_class}  mode={args.augment_mode}"
          f"{'  [DRY RUN]' if args.dry_run else ''}")
    print("=" * 74)

    field_df = pd.read_csv(args.field_manifest)
    pv_df = pd.read_csv(args.pv_manifest)

    if "source" in field_df.columns:
        before = len(field_df)
        field_df = field_df[field_df["source"] == "field"]
        print(f"Field manifest: kept {len(field_df)} field rows (of {before})")
    field_df = field_df[field_df["class_name"].isin(CLASS_TO_IDX)]

    rows_field, rep_field = process_source(
        "field", field_df, args.per_class, out, args.augment_mode,
        args.val_cap, args.test_cap, args.seed, args.dry_run)

    # PlantVillage half: the old manifest already holds 2,000/class; keep its splits.
    pv_rows = pv_df[["image_path", "label", "class_name", "split"]].copy()
    pv_rows["source"] = "plantvillage"
    pv_rows["is_augmented"] = False
    # PlantVillage images are standalone files, so each file is its own group.
    pv_rows["group_id"] = pv_rows["image_path"].map(_infer_group_id)
    rows_pv = pv_rows.to_dict("records")
    rep_pv = {}
    for cls, g in pv_df.groupby("class_name"):
        rep_pv[cls] = {
            "real_available": int(len(g)),
            "by_split": g.groupby("split").size().to_dict(),
        }

    combined = pd.DataFrame(rows_pv + rows_field)
    cols = ["image_path", "label", "class_name", "split", "source", "is_augmented", "group_id"]

    leaked_groups = _count_group_overlaps(rows_field)
    print(f"\n  Group leakage check (field): {leaked_groups} source image(s) "
          f"appear in more than one split")
    if leaked_groups:
        raise SystemExit("[ERROR] Group-aware split failed: refusing to write leaking manifest.")

    print("\n--- PlantVillage half (from old manifest) ---")
    print(f"  rows: {len(rows_pv)}")
    print("\n--- Field half (from new dataset, field-only) ---")
    print(f"  rows after balancing: {len(rows_field)}")

    print("\n--- Field per-class balancing report ---")
    fr = pd.DataFrame(rep_field).T
    print(fr.to_string())

    print("\n--- Combined totals ---")
    print(combined.groupby(["source", "split"]).size().unstack(fill_value=0).to_string())
    print(f"\nTOTAL rows: {len(combined)}")
    print(f"  plantvillage: {(combined['source'] == 'plantvillage').sum()}")
    print(f"  field       : {(combined['source'] == 'field').sum()}")

    tiny = [c for c, r in rep_field.items() if r["real_available"] < args.per_class * 0.25]
    if tiny:
        print("\n[WARNING] Kelas dengan data field sangat sedikit (<25% target) —")
        print("          augmentasi hanya menduplikasi informasi, bukan menambah.")
        for c in tiny:
            print(f"          - {c}: hanya {rep_field[c]['real_available']} gambar asli "
                  f"(duplikasi {rep_field[c]['duplication_factor']}x)")

    if args.dry_run:
        print("\n[DRY RUN] Tidak ada file/manifest yang ditulis.")
        return

    out.mkdir(parents=True, exist_ok=True)
    fields_df = pd.DataFrame(rows_field, columns=cols)
    combined = pd.DataFrame(rows_pv + rows_field)[cols]

    # Field-only manifest (variant A: fine-tune on the target domain only)
    field_only_path = out / "manifest_field_only.csv"
    fields_df.to_csv(field_only_path, index=False)
    print(f"  -> {field_only_path}  ({len(fields_df)} rows)")

    manifest_path = out / "manifest_combined.csv"
    combined.to_csv(manifest_path, index=False)
    print(f"  -> {manifest_path}  ({len(combined)} rows)")

    summary = {
        "per_class_target": args.per_class,
        "augment_mode": args.augment_mode,
        "split_strategy": "group_id (source image, leakage-safe)",
        "group_leakage_field": int(leaked_groups),
        "total_rows": int(len(combined)),
        "by_source": combined["source"].value_counts().to_dict(),
        "by_source_split": combined.groupby(["source", "split"]).size().unstack(fill_value=0).to_dict(),
        "field_report": rep_field,
        "pv_report": rep_pv,
    }
    (out / "manifest_combined_summary.json").write_text(
        json.dumps(summary, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"  -> {out / 'manifest_combined_summary.json'}")
    print("\nDone.")


if __name__ == "__main__":
    main()

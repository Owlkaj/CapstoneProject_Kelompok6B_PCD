import os
import json
import hashlib
from pathlib import Path
from PIL import Image
import pandas as pd
import numpy as np

# PRD Defined Class Mappings (Bab 3.2)
RAW_FOLDER_MAP = {
    "Tomato__Tomato_Bacterial_spot": "Bacterial Spot",
    "Tomato__Tomato_Early_blight": "Early Blight",
    "Tomato__Tomato_Healthy": "Healthy",
    "Tomato__Tomato_Late_blight": "Late Blight",
    "Tomato__Tomato_Leaf_Mold": "Leaf Mold",
    "Tomato__Tomato_Mosaic_virus": "Tomato Mosaic Virus",
    "Tomato__Tomato_Septoria_leaf_spot": "Septoria Leaf Spot",
    "Tomato__Tomato_Spider_mites": "Spider Mites",
    "Tomato__Tomato_Target_Spot": "Target Spot",
    "Tomato__Tomato_Yellow_Leaf_Curl_Virus": "Tomato Yellow Leaf Curl Virus (TYLCV)",
}

CLASS_TO_IDX = {
    "Bacterial Spot": 0,
    "Early Blight": 1,
    "Healthy": 2,
    "Late Blight": 3,
    "Leaf Mold": 4,
    "Tomato Mosaic Virus": 5,
    "Septoria Leaf Spot": 6,
    "Spider Mites": 7,
    "Target Spot": 8,
    "Tomato Yellow Leaf Curl Virus (TYLCV)": 9,
}

IDX_TO_CLASS = {v: k for k, v in CLASS_TO_IDX.items()}

SEED = 42
TARGET_PER_CLASS = 2000
TRAIN_RATIO = 0.8  # 1600 per class
VAL_RATIO = 0.1    # 200 per class
TEST_RATIO = 0.1   # 200 per class

def compute_file_hash(filepath: Path) -> str:
    hasher = hashlib.md5()
    with open(filepath, "rb") as f:
        while chunk := f.read(65536):
            hasher.update(chunk)
    return hasher.hexdigest()

def prepare_dataset(raw_dir: Path, output_dir: Path, models_dir: Path):
    print("=" * 60)
    print("PHASE 2 - DATASET INSPECTION, CLEANING & STRATIFIED SPLIT")
    print("=" * 60)
    print(f"Raw Data Directory: {raw_dir.resolve()}")
    print(f"Target Working Dataset: 20,000 images (2,000 / class)")
    print(f"Random Seed: {SEED}")

    np.random.seed(SEED)
    output_dir.mkdir(parents=True, exist_ok=True)
    models_dir.mkdir(parents=True, exist_ok=True)

    # 1. Scan and inspect all images in raw directories
    print("\n[1/4] Scanning and validating raw images...")
    class_images = {cls_name: [] for cls_name in CLASS_TO_IDX.keys()}
    global_seen_hashes = {}
    duplicates_count = 0
    corrupt_count = 0
    total_scanned = 0

    subdirs = ["train", "val", "test"]
    for subdir in subdirs:
        subpath = raw_dir / subdir
        if not subpath.exists():
            continue
        for folder in subpath.iterdir():
            if not folder.is_dir() or folder.name not in RAW_FOLDER_MAP:
                continue
            cls_name = RAW_FOLDER_MAP[folder.name]
            for img_file in folder.iterdir():
                if not img_file.is_file() or img_file.suffix.lower() not in [".jpg", ".jpeg", ".png"]:
                    continue
                total_scanned += 1
                try:
                    # Quick integrity check
                    with Image.open(img_file) as img:
                        img.verify()
                    # Check hash for deduplication
                    f_hash = compute_file_hash(img_file)
                    if f_hash in global_seen_hashes:
                        duplicates_count += 1
                        continue
                    global_seen_hashes[f_hash] = img_file
                    class_images[cls_name].append(img_file)
                except Exception as e:
                    corrupt_count += 1
                    print(f"  Warning: Corrupt image detected and skipped: {img_file} ({e})")

    print(f"  -> Total raw images scanned : {total_scanned}")
    print(f"  -> Exact duplicate images   : {duplicates_count}")
    print(f"  -> Corrupt/unreadable images: {corrupt_count}")
    print(f"  -> Unique verified images   : {len(global_seen_hashes)}")

    print("\nUnique image counts per class:")
    for cls_name, imgs in class_images.items():
        print(f"  - {cls_name:<38}: {len(imgs)} images")

    # 2. Sample 2,000 images per class reproducibly
    print(f"\n[2/4] Sampling {TARGET_PER_CLASS} images per class (reproducible seed={SEED})...")
    sampled_records = []
    
    for cls_name, imgs in class_images.items():
        count = len(imgs)
        if count < TARGET_PER_CLASS:
            print(f"  NOTE: Class '{cls_name}' has {count} images (< {TARGET_PER_CLASS}). Using all available.")
            chosen = imgs
        else:
            # Deterministic sort before shuffle
            sorted_imgs = sorted(imgs, key=lambda p: str(p))
            indices = np.random.RandomState(SEED).permutation(count)[:TARGET_PER_CLASS]
            chosen = [sorted_imgs[i] for i in indices]

        # Stratified Split: 80% train, 10% val, 10% test
        n_total = len(chosen)
        n_train = int(round(n_total * TRAIN_RATIO))
        n_val = int(round(n_total * VAL_RATIO))
        n_test = n_total - n_train - n_val

        shuffled_chosen = [chosen[i] for i in np.random.RandomState(SEED + 1).permutation(n_total)]
        train_files = shuffled_chosen[:n_train]
        val_files = shuffled_chosen[n_train:n_train + n_val]
        test_files = shuffled_chosen[n_train + n_val:]

        for f in train_files:
            sampled_records.append({"image_path": str(f.resolve()), "label": CLASS_TO_IDX[cls_name], "class_name": cls_name, "split": "train"})
        for f in val_files:
            sampled_records.append({"image_path": str(f.resolve()), "label": CLASS_TO_IDX[cls_name], "class_name": cls_name, "split": "val"})
        for f in test_files:
            sampled_records.append({"image_path": str(f.resolve()), "label": CLASS_TO_IDX[cls_name], "class_name": cls_name, "split": "test"})

    df = pd.DataFrame(sampled_records)
    print(f"\n[3/4] Validating Stratified Split Summary:")
    split_summary = df.groupby(["class_name", "split"]).size().unstack(fill_value=0)
    print(split_summary)
    
    total_by_split = df["split"].value_counts().to_dict()
    print(f"\nTotal by split: {total_by_split}")
    print(f"Total working dataset size: {len(df)}")

    # 3. Export Manifest CSV & Summary JSON
    manifest_csv = output_dir / "manifest.csv"
    summary_json = output_dir / "dataset_summary.json"
    label_json = models_dir / "label_mapping.json"

    print(f"\n[4/4] Writing manifests and label mappings...")
    df.to_csv(manifest_csv, index=False)
    print(f"  -> Saved manifest to: {manifest_csv}")

    summary_data = {
        "total_images": len(df),
        "target_working_dataset": TARGET_PER_CLASS * 10,
        "seed": SEED,
        "classes": list(CLASS_TO_IDX.keys()),
        "num_classes": len(CLASS_TO_IDX),
        "split_counts": total_by_split,
        "split_ratios": {"train": TRAIN_RATIO, "val": VAL_RATIO, "test": TEST_RATIO},
        "per_class_summary": split_summary.to_dict()
    }
    with open(summary_json, "w", encoding="utf-8") as f:
        json.dump(summary_data, f, indent=2, ensure_ascii=False)
    print(f"  -> Saved summary to: {summary_json}")

    label_mapping_data = {
        "class_to_idx": CLASS_TO_IDX,
        "idx_to_class": IDX_TO_CLASS,
        "num_classes": len(CLASS_TO_IDX)
    }
    with open(label_json, "w", encoding="utf-8") as f:
        json.dump(label_mapping_data, f, indent=2, ensure_ascii=False)
    print(f"  -> Saved label mapping to: {label_json}")

    print("\n" + "=" * 60)
    print("PHASE 2 - COMPLETE (PASS)")
    print("=" * 60)

if __name__ == "__main__":
    raw_path = Path("ml/dataset/raw/tomato")
    splits_path = Path("ml/dataset/splits")
    models_path = Path("ml/models")
    prepare_dataset(raw_path, splits_path, models_path)

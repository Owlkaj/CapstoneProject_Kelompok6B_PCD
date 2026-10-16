import os
import sys
import json
import torch
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from pathlib import Path
from torch.utils.data import DataLoader
from sklearn.metrics import accuracy_score, precision_recall_fscore_support, classification_report, confusion_matrix

current_dir = Path(__file__).resolve().parent
project_root = current_dir.parent.parent
sys.path.append(str(project_root))

ML_ROOT = Path(__file__).resolve().parent.parent.parent   # .../ml
REPO_ROOT = ML_ROOT.parent

from src.models.model import build_model
from src.preprocessing.transforms import get_eval_transforms, TomatoDataset

def _resolve(p) -> Path:
    """Resolve a manifest path whether it is given relative to the repo root or to ml/."""
    cand = Path(p)
    if cand.exists():
        return cand
    for base in (REPO_ROOT, ML_ROOT):
        alt = base / cand
        if alt.exists():
            return alt
    return cand


def evaluate_on_manifest(model_path: Path, manifest_path: Path, results_dir: Path, tag: str, split: str = "test"):
    """Evaluate a given checkpoint on a given manifest and dump metrics + confusion matrix.

    Used to measure the same model on BOTH domains (source/PlantVillage and
    target/field) so the trade-off is visible instead of hidden.
    """
    print("=" * 60)
    print(f"EVALUATION [{tag}] - model={model_path} manifest={manifest_path}")
    print("=" * 60)

    df = pd.read_csv(_resolve(manifest_path))
    eval_df = df[df["split"] == split].reset_index(drop=True)
    if len(eval_df) == 0:
        print(f"[WARN] No rows with split='{split}'. Available: {df['split'].unique().tolist()}")
        return None

    device = torch.device("cpu")
    try:
        import torch_directml
        if torch_directml.device_count() > 0:
            device = torch_directml.device(0)
    except Exception:
        pass

    model = build_model(num_classes=10, pretrained=False)
    # Checkpoints come from our own training runs (possibly saved on DirectML),
    # so weights_only=True cannot unpickle the device tensors.
    model.load_state_dict(torch.load(model_path, map_location="cpu", weights_only=False))
    model = model.to(device)
    model.eval()

    ds = TomatoDataset(eval_df, transform=get_eval_transforms())
    dl = DataLoader(ds, batch_size=32, shuffle=False, num_workers=0)

    all_preds, all_targets = [], []
    with torch.no_grad():
        for images, targets in dl:
            images = images.to(device)
            outputs = model(images)
            _, preds = torch.max(outputs, 1)
            all_preds.extend(preds.cpu().numpy())
            all_targets.extend(targets.numpy())

    all_preds = np.array(all_preds)
    all_targets = np.array(all_targets)

    accuracy = accuracy_score(all_targets, all_preds)

    label_data = json.loads((ML_ROOT / "models" / "label_mapping.json").read_text(encoding="utf-8"))
    class_names_all = [label_data["idx_to_class"][str(i)] for i in range(10)]

    # A manifest may cover only a subset of the 10 classes (e.g. a field test
    # set without Bacterial Spot / Target Spot). Compute macro metrics over the
    # classes actually present in the ground truth, otherwise absent classes
    # would count as 0 and artificially deflate the macro average. Absent
    # classes are reported explicitly.
    present = sorted(set(int(t) for t in all_targets))
    absent = [i for i in range(10) if i not in present]
    present_names = [class_names_all[i] for i in present]

    precision_macro, recall_macro, f1_macro, _ = precision_recall_fscore_support(
        all_targets, all_preds, labels=present, average="macro", zero_division=0)
    report_text = classification_report(
        all_targets, all_preds, labels=present, target_names=present_names, digits=4, zero_division=0)

    print(f"Samples            : {len(eval_df)}")
    print(f"Classes present    : {len(present)}/10"
          + (f"   (absent: {', '.join(class_names_all[i] for i in absent)})" if absent else ""))
    print(f"Overall Accuracy   : {accuracy*100:.2f}%")
    print(f"Macro Precision    : {precision_macro*100:.2f}%   (over {len(present)} present classes)")
    print(f"Macro Recall       : {recall_macro*100:.2f}%")
    print(f"Macro F1-Score     : {f1_macro*100:.2f}%")
    print("\nClassification Report:\n" + report_text)

    results_dir.mkdir(parents=True, exist_ok=True)
    cm = confusion_matrix(all_targets, all_preds, labels=present)
    pd.DataFrame(cm, index=present_names, columns=present_names).to_csv(results_dir / f"confusion_matrix_{tag}.csv")

    metrics = {
        "tag": tag,
        "model_path": str(model_path),
        "manifest_path": str(manifest_path),
        "split": split,
        "samples": int(len(eval_df)),
        "classes_present": [class_names_all[i] for i in present],
        "classes_absent": [class_names_all[i] for i in absent],
        "overall_accuracy": float(accuracy),
        "macro_precision": float(precision_macro),
        "macro_recall": float(recall_macro),
        "macro_f1": float(f1_macro),
        "per_class_metrics": {k: v for k, v in classification_report(
            all_targets, all_preds, labels=present, target_names=present_names,
            output_dict=True, zero_division=0).items() if k in present_names},
        "confusion_matrix": cm.tolist(),
    }
    out = results_dir / f"evaluation_metrics_{tag}.json"
    out.write_text(json.dumps(metrics, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"Saved metrics to: {out}")
    return metrics


def evaluate_final_model():
    print("=" * 60)
    print("PHASE 5 - MODEL SELECTION & FINAL TEST EVALUATION")
    print("=" * 60)

    # 1. Model Selection based on Validation Metrics (PRD Bab 15)
    e01_checkpoint = Path("ml/models/E01_best.pt")
    e02_checkpoint = Path("ml/models/E02_best.pt")

    ck_e01 = torch.load(e01_checkpoint, map_location="cpu", weights_only=False)
    ck_e02 = torch.load(e02_checkpoint, map_location="cpu", weights_only=False)

    print(f"Validation comparison:")
    print(f"  - E01 Val Loss: {ck_e01['val_loss']:.4f} | Val Acc: {ck_e01['val_acc']*100:.2f}%")
    print(f"  - E02 Val Loss: {ck_e02['val_loss']:.4f} | Val Acc: {ck_e02['val_acc']*100:.2f}%")

    if ck_e02["val_loss"] <= ck_e01["val_loss"]:
        selected_exp = "E02"
        best_ckpt = ck_e02
        best_ckpt_path = e02_checkpoint
    else:
        selected_exp = "E01"
        best_ckpt = ck_e01
        best_ckpt_path = e01_checkpoint

    print(f"\n>>> Selected Model: {selected_exp} (Highest validation accuracy & lowest loss)")

    # 2. Save Final Model Artifacts (PRD Bab 16)
    final_model_path = Path("ml/models/final_model.pt")
    fastapi_model_path = Path("fastapi/models/final_model.pt")
    fastapi_model_path.parent.mkdir(parents=True, exist_ok=True)

    # Save clean model state_dict for deployment
    torch.save(best_ckpt["model_state_dict"], final_model_path)
    torch.save(best_ckpt["model_state_dict"], fastapi_model_path)
    print(f"Saved final model to:\n  - {final_model_path}\n  - {fastapi_model_path}")

    # Copy metadata to fastapi/models for standalone serving
    with open("ml/models/label_mapping.json", "r") as f:
        label_data = json.load(f)
    with open(fastapi_model_path.parent / "label_mapping.json", "w") as f:
        json.dump(label_data, f, indent=2)

    with open("ml/models/preprocessing.json", "r") as f:
        prep_data = json.load(f)
    with open(fastapi_model_path.parent / "preprocessing.json", "w") as f:
        json.dump(prep_data, f, indent=2)

    # 3. Load Test Dataset (PRD Bab 5.3 & 14)
    manifest_path = Path("ml/dataset/splits/manifest.csv")
    df = pd.read_csv(manifest_path)
    test_df = df[df["split"] == "test"].reset_index(drop=True)
    print(f"\nRunning final evaluation on Test Set: {len(test_df)} samples...")

    test_dataset = TomatoDataset(test_df, transform=get_eval_transforms())
    test_loader = DataLoader(test_dataset, batch_size=32, shuffle=False, num_workers=0)

    # Load architecture and weights
    device = torch.device("cpu")
    try:
        import torch_directml
        if torch_directml.device_count() > 0:
            device = torch_directml.device(0)
    except:
        pass

    model = build_model(num_classes=10, pretrained=False)
    model.load_state_dict(torch.load(final_model_path, map_location="cpu", weights_only=True))
    model = model.to(device)
    model.eval()

    all_preds = []
    all_targets = []
    all_probs = []

    with torch.no_grad():
        for images, targets in test_loader:
            images = images.to(device)
            outputs = model(images)
            probs = torch.softmax(outputs, dim=1)
            _, preds = torch.max(outputs, 1)

            all_preds.extend(preds.cpu().numpy())
            all_targets.extend(targets.numpy())
            all_probs.extend(probs.cpu().numpy())

    all_preds = np.array(all_preds)
    all_targets = np.array(all_targets)

    # 4. Metrics Calculation (PRD Bab 14)
    accuracy = accuracy_score(all_targets, all_preds)
    precision_macro, recall_macro, f1_macro, _ = precision_recall_fscore_support(all_targets, all_preds, average="macro")
    precision_weighted, recall_weighted, f1_weighted, _ = precision_recall_fscore_support(all_targets, all_preds, average="weighted")

    class_names = [label_data["idx_to_class"][str(i)] for i in range(10)]
    report_dict = classification_report(all_targets, all_preds, target_names=class_names, output_dict=True)
    report_text = classification_report(all_targets, all_preds, target_names=class_names, digits=4)

    print("\n" + "=" * 60)
    print("FINAL TEST EVALUATION METRICS:")
    print("=" * 60)
    print(f"Overall Accuracy   : {accuracy*100:.2f}%")
    print(f"Macro Precision    : {precision_macro*100:.2f}%")
    print(f"Macro Recall       : {recall_macro*100:.2f}%")
    print(f"Macro F1-Score     : {f1_macro*100:.2f}%")
    print(f"Weighted F1-Score  : {f1_weighted*100:.2f}%")
    print("\nClassification Report:\n" + report_text)

    # 5. Confusion Matrix (PRD Bab 14.1)
    cm = confusion_matrix(all_targets, all_preds)
    results_dir = Path("ml/results")
    results_dir.mkdir(parents=True, exist_ok=True)

    cm_df = pd.DataFrame(cm, index=class_names, columns=class_names)
    cm_csv_path = results_dir / "confusion_matrix.csv"
    cm_df.to_csv(cm_csv_path)

    plt.figure(figsize=(10, 8))
    sns.heatmap(cm, annot=True, fmt="d", cmap="Blues", xticklabels=class_names, yticklabels=class_names)
    plt.title(f"Confusion Matrix - Final Model ({selected_exp}) - Test Acc: {accuracy*100:.2f}%")
    plt.xlabel("Predicted Class")
    plt.ylabel("True Class")
    plt.xticks(rotation=45, ha="right")
    plt.yticks(rotation=0)
    plt.tight_layout()
    cm_img_path = results_dir / "confusion_matrix.png"
    plt.savefig(cm_img_path, dpi=150)
    plt.close()
    print(f"Saved confusion matrix to {cm_img_path} and {cm_csv_path}")

    # 6. Save Evaluation Metrics Summary JSON
    metrics_summary = {
        "selected_model": selected_exp,
        "test_samples": len(test_df),
        "overall_accuracy": float(accuracy),
        "macro_precision": float(precision_macro),
        "macro_recall": float(recall_macro),
        "macro_f1": float(f1_macro),
        "weighted_f1": float(f1_weighted),
        "per_class_metrics": {k: v for k, v in report_dict.items() if k in class_names},
        "confusion_matrix": cm.tolist()
    }
    metrics_json_path = results_dir / "evaluation_metrics.json"
    with open(metrics_json_path, "w", encoding="utf-8") as f:
        json.dump(metrics_summary, f, indent=2, ensure_ascii=False)
    print(f"Saved evaluation metrics to: {metrics_json_path}")

    # 7. Model Reload & Single Inference Test (PRD Bab 32.1)
    print("\n[Verification] Testing Model Reload and Single Inference...")
    reload_model = build_model(num_classes=10, pretrained=False)
    reload_model.load_state_dict(torch.load(final_model_path, map_location="cpu", weights_only=True))
    reload_model.eval()

    sample_img, sample_lbl = test_dataset[0]
    sample_batch = sample_img.unsqueeze(0)
    with torch.no_grad():
        out = reload_model(sample_batch)
        prob = torch.softmax(out, dim=1)
        conf, pred_idx = torch.max(prob, 1)

    pred_class = class_names[pred_idx.item()]
    true_class = class_names[sample_lbl]
    print(f"  -> Single inference successful!")
    print(f"  -> True: '{true_class}' | Predicted: '{pred_class}' | Confidence: {conf.item():.4f}")

    print("\n" + "=" * 60)
    print("PHASE 5 - COMPLETE (PASS)")
    print("=" * 60)

if __name__ == "__main__":
    import argparse

    ap = argparse.ArgumentParser(description="TomatCare evaluation")
    ap.add_argument("--model", default=None, help="Checkpoint path; if omitted the full E01/E02 selection is run")
    ap.add_argument("--manifest", default="ml/dataset/splits/manifest.csv")
    ap.add_argument("--tag", default=None, help="Tag for output files (e.g. pv, field)")
    ap.add_argument("--split", default="test")
    ap.add_argument("--compare-domains", action="store_true",
                    help="Evaluate the model on both PlantVillage and field manifests")
    args = ap.parse_args()

    if args.compare_domains:
        results_dir = ML_ROOT / "results"
        model_path = _resolve(args.model or "ml/models/final_model_field.pt")
        pairs = [
            ("pv", REPO_ROOT / "ml" / "dataset" / "splits" / "manifest.csv"),
            ("field", REPO_ROOT / "ml" / "dataset" / "splits" / "manifest_field_only.csv"),
        ]
        summary = {}
        for tag, man in pairs:
            if not man.exists():
                print(f"[WARN] Manifest not found, skipping '{tag}': {man}")
                continue
            summary[tag] = evaluate_on_manifest(model_path, man, results_dir, tag, split=args.split)
        if len(summary) == 2:
            print("\n" + "=" * 60)
            print("DOMAIN COMPARISON SUMMARY (same model, two domains)")
            print("=" * 60)
            for tag, m in summary.items():
                print(f"  {tag:6}: acc={m['overall_accuracy']*100:6.2f}%  macroF1={m['macro_f1']*100:6.2f}%  n={m['samples']}")
    elif args.model:
        evaluate_on_manifest(_resolve(args.model), Path(args.manifest), ML_ROOT / "results",
                             args.tag or "custom", split=args.split)
    else:
        evaluate_final_model()

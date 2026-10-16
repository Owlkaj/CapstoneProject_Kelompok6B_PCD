import os
import sys
import time
import copy
import yaml
import torch
import torch.nn as nn
from torch.utils.data import DataLoader
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
from pathlib import Path

# Add project root to sys.path so imports work
current_dir = Path(__file__).resolve().parent
project_root = current_dir.parent.parent
sys.path.append(str(project_root))

# Repo-relative anchors so the script works whether it is launched from the repo
# root or from inside ml/ (python -m src.training.train).
ML_ROOT = Path(__file__).resolve().parent.parent.parent   # .../ml
REPO_ROOT = ML_ROOT.parent

from src.models.model import build_model, freeze_backbone, unfreeze_upper_layers
from src.preprocessing.transforms import get_train_transforms, get_eval_transforms, TomatoDataset

def get_device():
    try:
        import torch_directml
        if torch_directml.device_count() > 0:
            dev = torch_directml.device(0)
            name = torch_directml.device_name(0)
            print(f"Using DirectML Device: {dev} ({name})")
            return dev, "directml"
    except Exception as e:
        print(f"DirectML not available ({e}), falling back to CPU.")
    return torch.device("cpu"), "cpu"

def plot_history(history_df, exp_id, output_dir: Path):
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # 1. Loss curve
    plt.figure(figsize=(8, 5))
    plt.plot(history_df["epoch"], history_df["train_loss"], label="Train Loss", marker="o")
    plt.plot(history_df["epoch"], history_df["val_loss"], label="Val Loss", marker="s")
    plt.title(f"{exp_id} - Loss Curve")
    plt.xlabel("Epoch")
    plt.ylabel("Loss")
    plt.grid(True)
    plt.legend()
    loss_path = output_dir / f"{exp_id}_loss.png"
    plt.savefig(loss_path, dpi=150, bbox_inches="tight")
    plt.close()

    # 2. Accuracy curve
    plt.figure(figsize=(8, 5))
    plt.plot(history_df["epoch"], history_df["train_acc"], label="Train Accuracy", marker="o")
    plt.plot(history_df["epoch"], history_df["val_acc"], label="Val Accuracy", marker="s")
    plt.title(f"{exp_id} - Accuracy Curve")
    plt.xlabel("Epoch")
    plt.ylabel("Accuracy")
    plt.grid(True)
    plt.legend()
    acc_path = output_dir / f"{exp_id}_accuracy.png"
    plt.savefig(acc_path, dpi=150, bbox_inches="tight")
    plt.close()

    print(f"Saved training curves to {loss_path} and {acc_path}")

def train_epoch(model, dataloader, criterion, optimizer, device):
    model.train()
    running_loss = 0.0
    correct = 0
    total = 0

    for batch_idx, (images, targets) in enumerate(dataloader):
        images, targets = images.to(device), targets.to(device)

        optimizer.zero_grad()
        outputs = model(images)
        loss = criterion(outputs, targets)
        loss.backward()
        optimizer.step()

        running_loss += loss.item() * images.size(0)
        _, preds = torch.max(outputs, 1)
        correct += (preds == targets).sum().item()
        total += targets.size(0)

        if (batch_idx + 1) % 100 == 0 or (batch_idx + 1) == len(dataloader):
            curr_acc = correct / total
            curr_loss = running_loss / total
            print(f"  Batch [{batch_idx+1}/{len(dataloader)}] - Loss: {curr_loss:.4f} - Acc: {curr_acc*100:.2f}%")

    epoch_loss = running_loss / total
    epoch_acc = correct / total
    return epoch_loss, epoch_acc

def evaluate(model, dataloader, criterion, device):
    model.eval()
    running_loss = 0.0
    correct = 0
    total = 0

    with torch.no_grad():
        for images, targets in dataloader:
            images, targets = images.to(device), targets.to(device)
            outputs = model(images)
            loss = criterion(outputs, targets)

            running_loss += loss.item() * images.size(0)
            _, preds = torch.max(outputs, 1)
            correct += (preds == targets).sum().item()
            total += targets.size(0)

    val_loss = running_loss / total
    val_acc = correct / total
    return val_loss, val_acc

def run_experiment(exp_id: str, config: dict, starting_model=None, manifest_override=None, source_mix_manifest=None):
    print("\n" + "=" * 60)
    print(f"RUNNING EXPERIMENT: {exp_id}")
    print("=" * 60)

    exp_cfg = config["experiments"][exp_id]
    batch_size = exp_cfg["batch_size"]
    lr = float(exp_cfg["learning_rate"])
    epochs = int(exp_cfg["epochs"])
    patience = int(exp_cfg["early_stopping_patience"])
    weight_decay = float(exp_cfg.get("weight_decay", 0.01))

    # Data loaders
    def _resolve(p: str) -> Path:
        cand = Path(p)
        if cand.exists():
            return cand
        for base in (REPO_ROOT, ML_ROOT):
            alt = base / cand
            if alt.exists():
                return alt
        return cand

    manifest_path = _resolve(manifest_override or config["dataset"]["manifest_path"])
    df = pd.read_csv(manifest_path)

    train_df = df[df["split"] == "train"]
    val_df = df[df["split"] == "val"]

    # Optional: mix in a fraction of the source-domain images to limit
    # catastrophic forgetting during target-domain fine-tuning (Skema B).
    mix_ratio = float(exp_cfg.get("source_mix_ratio", 0.0) or 0.0)
    if source_mix_manifest and mix_ratio > 0.0:
        src_df = pd.read_csv(_resolve(source_mix_manifest))
        src_train = src_df[src_df["split"] == "train"]
        n_mix = int(round(len(train_df) * mix_ratio))
        if n_mix > 0 and len(src_train) > 0:
            src_train = src_train.sample(n=min(n_mix, len(src_train)), random_state=config.get("seed", 42))
            train_df = pd.concat([train_df, src_train], ignore_index=True)
            src_val = src_df[src_df["split"] == "val"]
            n_val_mix = int(round(len(val_df) * mix_ratio))
            if n_val_mix > 0 and len(src_val) > 0:
                src_val = src_val.sample(n=min(n_val_mix, len(src_val)), random_state=config.get("seed", 42))
                val_df = pd.concat([val_df, src_val], ignore_index=True)
            print(f"Source mix enabled: +{len(src_train)} train / +{len(src_val)} val from {source_mix_manifest}")

    train_dataset = TomatoDataset(train_df, transform=get_train_transforms())
    val_dataset = TomatoDataset(val_df, transform=get_eval_transforms())

    # DirectML works best with num_workers=0 on Windows
    train_loader = DataLoader(train_dataset, batch_size=batch_size, shuffle=True, num_workers=0)
    val_loader = DataLoader(val_dataset, batch_size=batch_size, shuffle=False, num_workers=0)

    print(f"Training samples  : {len(train_dataset)} ({len(train_loader)} batches)")
    print(f"Validation samples: {len(val_dataset)} ({len(val_loader)} batches)")

    device, device_type = get_device()

    # Build or load model
    if starting_model is not None:
        model = starting_model
    else:
        model = build_model(num_classes=10, pretrained=True)

    if exp_id == "E01":
        model = freeze_backbone(model)
    elif exp_id == "E02":
        model = unfreeze_upper_layers(model, num_stages_to_unfreeze=exp_cfg.get("unfrozen_stages", 2))

    model = model.to(device)

    criterion = nn.CrossEntropyLoss()
    optimizer = torch.optim.AdamW(filter(lambda p: p.requires_grad, model.parameters()), lr=lr, weight_decay=weight_decay)

    best_val_loss = float("inf")
    best_val_acc = 0.0
    best_model_wts = copy.deepcopy(model.state_dict())
    patience_counter = 0

    history = []
    models_dir = ML_ROOT / "models"
    history_dir = ML_ROOT / "results" / "training_history"
    models_dir.mkdir(parents=True, exist_ok=True)
    history_dir.mkdir(parents=True, exist_ok=True)

    checkpoint_path = models_dir / f"{exp_id}_best.pt"

    start_time = time.time()
    for epoch in range(1, epochs + 1):
        ep_start = time.time()
        print(f"\nEpoch [{epoch}/{epochs}] - LR: {lr:.6f}")
        train_loss, train_acc = train_epoch(model, train_loader, criterion, optimizer, device)
        val_loss, val_acc = evaluate(model, val_loader, criterion, device)
        ep_time = time.time() - ep_start

        print(f"  -> Train Loss: {train_loss:.4f} | Train Acc: {train_acc*100:.2f}%")
        print(f"  -> Val Loss  : {val_loss:.4f} | Val Acc  : {val_acc*100:.2f}% | Time: {ep_time:.1f}s")

        history.append({
            "epoch": epoch,
            "train_loss": train_loss,
            "train_acc": train_acc,
            "val_loss": val_loss,
            "val_acc": val_acc,
            "time_sec": ep_time
        })

        # Check for improvement (monitor validation loss as primary, accuracy as secondary)
        if val_loss < best_val_loss:
            best_val_loss = val_loss
            best_val_acc = val_acc
            best_model_wts = copy.deepcopy(model.state_dict())
            patience_counter = 0
            
            # Save checkpoint
            torch.save({
                "epoch": epoch,
                "model_state_dict": best_model_wts,
                "optimizer_state_dict": optimizer.state_dict(),
                "val_loss": best_val_loss,
                "val_acc": best_val_acc,
                "experiment_id": exp_id,
                "config": exp_cfg
            }, checkpoint_path)
            print(f"  >>> Checkpoint saved: {checkpoint_path} (Best Val Loss: {best_val_loss:.4f}, Acc: {best_val_acc*100:.2f}%)")
        else:
            patience_counter += 1
            print(f"  No improvement for {patience_counter} consecutive epoch(s). (Patience: {patience})")
            if patience_counter >= patience:
                print(f"\n[Early Stopping Triggered] Stopping training at epoch {epoch}.")
                break

    total_time = time.time() - start_time
    print(f"\nExperiment {exp_id} completed in {total_time/60:.2f} minutes.")
    print(f"Best Validation Loss: {best_val_loss:.4f} | Best Validation Accuracy: {best_val_acc*100:.2f}%")

    # Save history
    history_df = pd.DataFrame(history)
    history_csv = history_dir / f"{exp_id}_history.csv"
    history_df.to_csv(history_csv, index=False)
    print(f"Saved history CSV to {history_csv}")

    plot_history(history_df, exp_id, history_dir)

    # Reload best weights
    model.load_state_dict(best_model_wts)
    return model, best_val_loss, best_val_acc

def main():
    import argparse

    parser = argparse.ArgumentParser(description="TomatCare training pipeline (Skema B)")
    parser.add_argument("--stage", choices=["pv", "field"], default="pv",
                        help="pv = E01->E02 on PlantVillage; field = E03 target-domain fine-tuning")
    parser.add_argument("--field-manifest", default="ml/dataset/splits/manifest_field.csv",
                        help="Manifest for the target-domain (field) fine-tuning stage")
    parser.add_argument("--source-manifest", default=None,
                        help="Optional source-domain manifest mixed into E03 (see source_mix_ratio)")
    parser.add_argument("--mix-ratio", type=float, default=None,
                        help="Override source_mix_ratio for E03 (e.g. 1.0 for a ~50/50 mix)")
    parser.add_argument("--out-model", default="ml/models/final_model_field.pt",
                        help="Path to save the fine-tuned model for --stage field")
    parser.add_argument("--epochs", type=int, default=None, help="Override epoch count (smoke tests)")
    args = parser.parse_args()

    config_path = ML_ROOT / "models" / "training_config.yaml"
    with open(config_path, "r") as f:
        config = yaml.safe_load(f)

    if args.epochs is not None:
        for exp in config.get("experiments", {}).values():
            exp["epochs"] = int(args.epochs)
        print(f"[override] semua eksperimen disetel ke {args.epochs} epoch")

    if args.stage == "field":
        print(">>> Starting Stage 3: E03 Field / Target-Domain Fine-Tuning")
        if args.mix_ratio is not None:
            config["experiments"]["E03"]["source_mix_ratio"] = args.mix_ratio
        ckpt_key = "checkpoint_e02"
        e02_ckpt = Path(config.get("dataset", {}).get(ckpt_key, str(ML_ROOT / "models" / "E02_best.pt")))
        if not e02_ckpt.exists():
            raise SystemExit(f"[ERROR] E02 checkpoint not found: {e02_ckpt}. Run --stage pv first.")

        model = build_model(num_classes=10, pretrained=False)
        checkpoint = torch.load(e02_ckpt, map_location="cpu", weights_only=False)
        model.load_state_dict(checkpoint["model_state_dict"])
        print(f"Bootstrapped from {e02_ckpt} (val_acc={checkpoint.get('val_acc', float('nan')):.4f})")
        print(f"Mix ratio: {config['experiments']['E03'].get('source_mix_ratio', 0.0)} "
              f"(source manifest: {args.source_manifest})")

        model_e03, e03_loss, e03_acc = run_experiment(
            "E03",
            config,
            starting_model=model,
            manifest_override=args.field_manifest,
            source_mix_manifest=args.source_manifest,
        )

        out_path = Path(args.out_model)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        # Save CPU tensors so downstream tools (FastAPI) can load with weights_only=True.
        model_e03.to("cpu")
        torch.save({k: v.detach().cpu() for k, v in model_e03.state_dict().items()}, out_path)
        print("\n" + "=" * 60)
        print(f"STAGE 3 FINISHED - E03 Best Val Acc: {e03_acc*100:.2f}% (Loss: {e03_loss:.4f})")
        print(f"Saved target-domain weights to: {out_path}")
        print("=" * 60)
        return

    # 1. Run E01: Transfer Learning Baseline
    print(">>> Starting Step 1: E01 Transfer Learning Baseline")
    model_e01, e01_loss, e01_acc = run_experiment("E01", config)

    # 2. Run E02: Fine-Tuning starting from best E01
    print("\n>>> Starting Step 2: E02 Fine-Tuning (Starting from best E01)")
    model_e02, e02_loss, e02_acc = run_experiment("E02", config, starting_model=model_e01)

    print("\n" + "=" * 60)
    print("TRAINING STAGE FINISHED")
    print(f"E01 Best Val Acc: {e01_acc*100:.2f}% (Loss: {e01_loss:.4f})")
    print(f"E02 Best Val Acc: {e02_acc*100:.2f}% (Loss: {e02_loss:.4f})")
    print("=" * 60)

if __name__ == "__main__":
    main()

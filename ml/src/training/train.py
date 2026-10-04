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

def run_experiment(exp_id: str, config: dict, starting_model=None):
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
    manifest_path = Path(config["dataset"]["manifest_path"])
    df = pd.read_csv(manifest_path)

    train_df = df[df["split"] == "train"]
    val_df = df[df["split"] == "val"]

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
    models_dir = Path("ml/models")
    history_dir = Path("ml/results/training_history")
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
    config_path = Path("ml/models/training_config.yaml")
    with open(config_path, "r") as f:
        config = yaml.safe_load(f)

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

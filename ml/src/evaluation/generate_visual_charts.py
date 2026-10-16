"""
Script to generate intuitive, presentation-ready visual charts for model evaluation:
1. confusion_matrix_baseline_pv.png - Heatmap for PlantVillage test set (96.60% acc)
2. confusion_matrix_baseline_field_clean.png - Heatmap for Field test set (66.83% acc)
3. confusion_matrix_comparison.png - Side-by-side comparison showing domain shift
4. baseline_pv_metrics_chart.png - Per-class Precision, Recall, F1 for baseline PV
5. domain_comparison_barchart.png - Comparative benchmark (Lab vs Lapangan vs Fine-Tuned)
"""

import json
from pathlib import Path
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns

RESULTS_DIR = Path(__file__).resolve().parents[2] / "results"

ID_NAMES = {
    "Bacterial Spot": "Bercak Bakteri",
    "Early Blight": "Bercak Kering",
    "Healthy": "Daun Sehat",
    "Late Blight": "Hawar Daun",
    "Leaf Mold": "Kapang Daun",
    "Tomato Mosaic Virus": "Virus Mosaik",
    "Septoria Leaf Spot": "Bercak Septoria",
    "Spider Mites": "Tungau Laba-laba",
    "Target Spot": "Bercak Sasaran",
    "Tomato Yellow Leaf Curl Virus (TYLCV)": "Virus Kuning Keriting"
}

def setup_matplotlib():
    plt.rcParams['font.sans-serif'] = 'DejaVu Sans'
    plt.rcParams['axes.edgecolor'] = '#cbd5e1'
    plt.rcParams['axes.linewidth'] = 0.8

def plot_single_cm(csv_file: Path, title: str, subtitle: str, output_file: Path, cmap: str = "Blues"):
    df_cm = pd.read_csv(csv_file, index_col=0)
    
    # Label formatting with Indonesian names
    display_labels = [f"{ID_NAMES.get(c, c)}\n({c})" for c in df_cm.index]
    
    total_samples = df_cm.values.sum()
    correct_samples = np.trace(df_cm.values)
    acc = (correct_samples / total_samples) * 100

    fig, ax = plt.subplots(figsize=(12, 10), dpi=300)
    
    sns.heatmap(
        df_cm, 
        annot=True, 
        fmt="d", 
        cmap=cmap, 
        cbar=True,
        xticklabels=display_labels, 
        yticklabels=display_labels,
        linewidths=0.5,
        linecolor="#e2e8f0",
        annot_kws={"size": 10, "weight": "bold"},
        ax=ax
    )
    
    ax.set_title(f"{title}\n{subtitle} | Akurasi: {acc:.2f}% ({correct_samples}/{total_samples} benar)", 
                 fontsize=13, fontweight='bold', pad=15, color='#0f172a')
    ax.set_xlabel("Prediksi Model (Predicted Class)", fontsize=11, fontweight='bold', labelpad=10, color='#334155')
    ax.set_ylabel("Kelas Sebenarnya (True Class)", fontsize=11, fontweight='bold', labelpad=10, color='#334155')
    
    plt.xticks(rotation=35, ha='right', fontsize=9)
    plt.yticks(rotation=0, fontsize=9)
    plt.tight_layout()
    plt.savefig(output_file, dpi=300)
    plt.close()
    print(f"[OK] Saved heatmap: {output_file.name}")

def plot_cm_comparison(pv_csv: Path, field_csv: Path, output_file: Path):
    df_pv = pd.read_csv(pv_csv, index_col=0)
    df_field = pd.read_csv(field_csv, index_col=0)
    
    # Short Indonesian names for cleaner 2-column layout
    short_labels = [ID_NAMES.get(c, c) for c in df_pv.index]
    
    acc_pv = (np.trace(df_pv.values) / df_pv.values.sum()) * 100
    acc_field = (np.trace(df_field.values) / df_field.values.sum()) * 100

    fig, axes = plt.subplots(1, 2, figsize=(22, 10), dpi=300)
    
    # Plot 1: PlantVillage
    sns.heatmap(
        df_pv, 
        annot=True, 
        fmt="d", 
        cmap="Blues", 
        cbar=False,
        xticklabels=short_labels, 
        yticklabels=short_labels,
        linewidths=0.5,
        linecolor="#e2e8f0",
        annot_kws={"size": 9, "weight": "bold"},
        ax=axes[0]
    )
    axes[0].set_title(f"1. Dataset Uji Laboratorium (PlantVillage)\nAkurasi: {acc_pv:.2f}% (1.932 / 2.000 benar)\nDiagonal terisi penuh = Prediksi Sangat Akurat", 
                      fontsize=12, fontweight='bold', pad=12, color='#1e3a8a')
    axes[0].set_xlabel("Prediksi Model", fontsize=11, fontweight='bold', labelpad=8)
    axes[0].set_ylabel("Label Sebenarnya", fontsize=11, fontweight='bold', labelpad=8)
    axes[0].tick_params(axis='x', rotation=35, labelsize=9)
    axes[0].tick_params(axis='y', rotation=0, labelsize=9)

    # Plot 2: Field Clean
    sns.heatmap(
        df_field, 
        annot=True, 
        fmt="d", 
        cmap="Oranges", 
        cbar=False,
        xticklabels=short_labels, 
        yticklabels=short_labels,
        linewidths=0.5,
        linecolor="#e2e8f0",
        annot_kws={"size": 9, "weight": "bold"},
        ax=axes[1]
    )
    axes[1].set_title(f"2. Dataset Uji Foto Lapangan Nyata (Field Test)\nAkurasi: {acc_field:.2f}% (139 / 208 benar)\nBanyak angka di luar diagonal = Efek Domain Shift", 
                      fontsize=12, fontweight='bold', pad=12, color='#9a3412')
    axes[1].set_xlabel("Prediksi Model", fontsize=11, fontweight='bold', labelpad=8)
    axes[1].set_ylabel("Label Sebenarnya", fontsize=11, fontweight='bold', labelpad=8)
    axes[1].tick_params(axis='x', rotation=35, labelsize=9)
    axes[1].tick_params(axis='y', rotation=0, labelsize=9)

    plt.suptitle("PERBANDINGAN VISUAL CONFUSION MATRIX: LABORATORIUM VS FOTO KEBUN NYATA\nMemvisualisasikan Fenomena Domain Shift pada Model Baseline", 
                 fontsize=15, fontweight='bold', y=0.98, color='#0f172a')
    plt.tight_layout(rect=[0, 0, 1, 0.95])
    plt.savefig(output_file, dpi=300)
    plt.close()
    print(f"[OK] Saved comparison: {output_file.name}")

def plot_baseline_pv_metrics(metrics_json: Path, output_file: Path):
    with open(metrics_json, "r", encoding="utf-8") as f:
        data = json.load(f)

    rows = []
    for cls_name, m in data["per_class_metrics"].items():
        rows.append({
            "name": f"{ID_NAMES.get(cls_name, cls_name)}\n({cls_name})",
            "precision": m["precision"] * 100,
            "recall": m["recall"] * 100,
            "f1": m["f1-score"] * 100
        })
    df = pd.DataFrame(rows)

    fig, ax = plt.subplots(figsize=(14, 7), dpi=300)
    x = np.arange(len(df))
    width = 0.26

    r1 = ax.bar(x - width, df['precision'], width, label='Precision (%)', color='#2563eb', alpha=0.9)
    r2 = ax.bar(x, df['recall'], width, label='Recall (%)', color='#059669', alpha=0.9)
    r3 = ax.bar(x + width, df['f1'], width, label='F1-Score (%)', color='#7c3aed', alpha=0.9)

    ax.set_ylabel('Persentase Skor (%)', fontsize=12, fontweight='bold', labelpad=10)
    ax.set_title('Performa Per Kelas Model Baseline pada Dataset PlantVillage (2.000 Citra Uji)\nAkurasi Total: 96.60% | Macro F1: 96.60%',
                 fontsize=14, fontweight='bold', pad=15, color='#0f172a')
    ax.set_xticks(x)
    ax.set_xticklabels(df['name'], fontsize=9, rotation=25, ha='right')
    ax.set_ylim(85, 103)
    ax.grid(axis='y', linestyle='--', alpha=0.5)
    ax.legend(loc='lower right', frameon=True, facecolor='white', framealpha=0.95, fontsize=10)

    # Annotate F1 scores
    for rect in r3:
        h = rect.get_height()
        ax.annotate(f'{h:.1f}%',
                    xy=(rect.get_x() + rect.get_width() / 2, h),
                    xytext=(0, 4), textcoords="offset points",
                    ha='center', va='bottom', fontsize=8, fontweight='bold', color='#4c1d95')

    plt.tight_layout()
    plt.savefig(output_file, dpi=300)
    plt.close()
    print(f"[OK] Saved baseline PV bar chart: {output_file.name}")

def plot_benchmark_comparison(pv_json: Path, field_baseline_json: Path, field_ft_json: Path, output_file: Path):
    with open(pv_json, "r", encoding="utf-8") as f:
        data_pv = json.load(f)
    with open(field_baseline_json, "r", encoding="utf-8") as f:
        data_field_base = json.load(f)
    with open(field_ft_json, "r", encoding="utf-8") as f:
        data_field_ft = json.load(f)

    classes = list(data_pv["per_class_metrics"].keys())
    
    rows = []
    for c in classes:
        f1_pv = data_pv["per_class_metrics"].get(c, {}).get("f1-score", 0) * 100
        f1_fb = data_field_base["per_class_metrics"].get(c, {}).get("f1-score", 0) * 100
        f1_ft = data_field_ft["per_class_metrics"].get(c, {}).get("f1-score", 0) * 100
        rows.append({
            "class": f"{ID_NAMES.get(c, c)}",
            "PV_Baseline": f1_pv,
            "Field_Baseline": f1_fb,
            "Field_FineTuned": f1_ft
        })
    df = pd.DataFrame(rows)

    fig, ax = plt.subplots(figsize=(15, 8), dpi=300)
    x = np.arange(len(df))
    width = 0.26

    r1 = ax.bar(x - width, df['PV_Baseline'], width, 
                label=f'1. Baseline di Lab (PlantVillage) [Akurasi {data_pv["overall_accuracy"]*100:.1f}%]', 
                color='#3b82f6', alpha=0.9)
    r2 = ax.bar(x, df['Field_Baseline'], width, 
                label=f'2. Baseline di Foto Kebun Nyata [Akurasi {data_field_base["overall_accuracy"]*100:.1f}%] - Drop Domain Shift', 
                color='#ef4444', alpha=0.85)
    r3 = ax.bar(x + width, df['Field_FineTuned'], width, 
                label=f'3. Model Fine-Tuned di Foto Kebun [Akurasi {data_field_ft["overall_accuracy"]*100:.1f}%] - Pulih Kembali', 
                color='#10b981', alpha=0.9)

    ax.set_ylabel('F1-Score (%)', fontsize=12, fontweight='bold', labelpad=10)
    ax.set_title('PERBANDINGAN F1-SCORE: DARI LABORATORIUM MENUJU KEBUN NYATA\nMemperlihatkan Penurunan Akibat Domain Shift dan Pemulihan Pasca Fine-Tuning',
                 fontsize=13, fontweight='bold', pad=15, color='#0f172a')
    ax.set_xticks(x)
    ax.set_xticklabels(df['class'], fontsize=10, rotation=25, ha='right', fontweight='medium')
    ax.set_ylim(0, 115)
    ax.grid(axis='y', linestyle='--', alpha=0.5)
    ax.legend(loc='upper right', frameon=True, facecolor='white', framealpha=0.95, fontsize=10)

    # Highlight recover improvement on bars
    for i, (_, row) in enumerate(df.iterrows()):
        val_fb = row['Field_Baseline']
        val_ft = row['Field_FineTuned']
        diff = val_ft - val_fb
        if diff > 0:
            ax.annotate(f'+{diff:.0f}%',
                        xy=(x[i] + width, val_ft),
                        xytext=(0, 4), textcoords="offset points",
                        ha='center', va='bottom', fontsize=8, fontweight='bold', color='#065f46')

    plt.tight_layout()
    plt.savefig(output_file, dpi=300)
    plt.close()
    print(f"[OK] Saved domain comparison bar chart: {output_file.name}")

def main():
    setup_matplotlib()
    
    # 1. Heatmap Baseline PlantVillage
    pv_cm = RESULTS_DIR / "confusion_matrix_baseline_pv.csv"
    if pv_cm.exists():
        plot_single_cm(
            pv_cm, 
            "Confusion Matrix — Model Baseline (Dataset Laboratorium PlantVillage)",
            "Himpunan Uji Test Set: 2.000 Citra (200 Citra / Kelas)",
            RESULTS_DIR / "confusion_matrix_baseline_pv.png",
            cmap="Blues"
        )

    # 2. Heatmap Baseline Field Clean
    field_cm = RESULTS_DIR / "confusion_matrix_baseline_field_clean.csv"
    if field_cm.exists():
        plot_single_cm(
            field_cm, 
            "Confusion Matrix — Model Baseline (Dataset Foto Kebun Nyata / Field Test)",
            "Himpunan Uji Petani Lapangan: 208 Citra (Menguji Efek Domain Shift)",
            RESULTS_DIR / "confusion_matrix_baseline_field_clean.png",
            cmap="Oranges"
        )

    # 3. Side-by-side Confusion Matrix comparison
    if pv_cm.exists() and field_cm.exists():
        plot_cm_comparison(
            pv_cm, 
            field_cm, 
            RESULTS_DIR / "confusion_matrix_comparison.png"
        )

    # 4. Baseline PV metrics chart (Precision, Recall, F1)
    pv_json = RESULTS_DIR / "evaluation_metrics_baseline_pv.json"
    if pv_json.exists():
        plot_baseline_pv_metrics(
            pv_json, 
            RESULTS_DIR / "baseline_pv_metrics_chart.png"
        )

    # 5. Benchmark Comparison: Lab vs Field Baseline vs Field Fine-tuned
    field_base_json = RESULTS_DIR / "evaluation_metrics_baseline_field_clean.json"
    field_ft_json = RESULTS_DIR / "evaluation_metrics_fieldB_clean.json"
    if pv_json.exists() and field_base_json.exists() and field_ft_json.exists():
        plot_benchmark_comparison(
            pv_json,
            field_base_json,
            field_ft_json,
            RESULTS_DIR / "domain_comparison_barchart.png"
        )

    print("\n[SUCCESS] Semua grafik visual berhasil dibuat di folder ml/results/!")

if __name__ == "__main__":
    main()

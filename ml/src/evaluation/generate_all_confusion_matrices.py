"""
Script untuk men-generate gambar visualisasi heatmap Confusion Matrix beresolusi tinggi (300 DPI)
untuk model klasifikasi penyakit daun tomat (TomatCare).
Mencakup model operasional terkini (YOLO3 Mixed), uji silang lintas domain, dan visualisasi komparatif.
"""

import os
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
    if not csv_file.exists():
        print(f"[SKIP] File not found: {csv_file.name}")
        return
        
    df_cm = pd.read_csv(csv_file, index_col=0)
    display_labels = [f"{ID_NAMES.get(c, c)}\n({c})" for c in df_cm.index]
    
    total_samples = int(df_cm.values.sum())
    correct_samples = int(np.trace(df_cm.values))
    acc = (correct_samples / total_samples) * 100 if total_samples > 0 else 0

    n_classes = len(df_cm.index)
    fig_size = (12, 10) if n_classes >= 8 else (8, 7)
    annot_size = 10 if n_classes >= 8 else 12

    fig, ax = plt.subplots(figsize=fig_size, dpi=300)
    
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
        annot_kws={"size": annot_size, "weight": "bold"},
        ax=ax
    )
    
    ax.set_title(f"{title}\n{subtitle} | Akurasi: {acc:.2f}% ({correct_samples:,}/{total_samples:,} benar)", 
                 fontsize=12, fontweight='bold', pad=15, color='#0f172a')
    ax.set_xlabel("Prediksi Model (Predicted Class)", fontsize=11, fontweight='bold', labelpad=10, color='#334155')
    ax.set_ylabel("Kelas Sebenarnya (True Class)", fontsize=11, fontweight='bold', labelpad=10, color='#334155')
    
    plt.xticks(rotation=35, ha='right', fontsize=9)
    plt.yticks(rotation=0, fontsize=9)
    plt.tight_layout()
    plt.savefig(output_file, dpi=300)
    plt.close()
    print(f"[OK] Generated: {output_file.name}")

def plot_side_by_side(csv1: Path, csv2: Path, title1: str, title2: str, main_title: str, output_file: Path, cmap1="Blues", cmap2="Greens"):
    df1 = pd.read_csv(csv1, index_col=0)
    df2 = pd.read_csv(csv2, index_col=0)

    acc1 = (np.trace(df1.values) / df1.values.sum()) * 100
    acc2 = (np.trace(df2.values) / df2.values.sum()) * 100

    labels1 = [f"{ID_NAMES.get(c, c)}" for c in df1.index]
    labels2 = [f"{ID_NAMES.get(c, c)}" for c in df2.index]

    fig, axes = plt.subplots(1, 2, figsize=(22, 10), dpi=300)

    # Plot 1
    sns.heatmap(df1, annot=True, fmt="d", cmap=cmap1, cbar=False,
                xticklabels=labels1, yticklabels=labels1, linewidths=0.5, linecolor="#e2e8f0",
                annot_kws={"size": 9, "weight": "bold"}, ax=axes[0])
    axes[0].set_title(f"{title1}\nAkurasi: {acc1:.2f}% ({int(np.trace(df1.values)):,}/{int(df1.values.sum()):,} benar)",
                      fontsize=12, fontweight='bold', pad=12, color='#1e3a8a')
    axes[0].set_xlabel("Prediksi Model", fontsize=11, fontweight='bold', labelpad=8)
    axes[0].set_ylabel("Label Sebenarnya", fontsize=11, fontweight='bold', labelpad=8)
    axes[0].tick_params(axis='x', rotation=35, labelsize=9)
    axes[0].tick_params(axis='y', rotation=0, labelsize=9)

    # Plot 2
    sns.heatmap(df2, annot=True, fmt="d", cmap=cmap2, cbar=False,
                xticklabels=labels2, yticklabels=labels2, linewidths=0.5, linecolor="#e2e8f0",
                annot_kws={"size": 9, "weight": "bold"}, ax=axes[1])
    axes[1].set_title(f"{title2}\nAkurasi: {acc2:.2f}% ({int(np.trace(df2.values)):,}/{int(df2.values.sum()):,} benar)",
                      fontsize=12, fontweight='bold', pad=12, color='#065f46')
    axes[1].set_xlabel("Prediksi Model", fontsize=11, fontweight='bold', labelpad=8)
    axes[1].set_ylabel("Label Sebenarnya", fontsize=11, fontweight='bold', labelpad=8)
    axes[1].tick_params(axis='x', rotation=35, labelsize=9)
    axes[1].tick_params(axis='y', rotation=0, labelsize=9)

    plt.suptitle(main_title, fontsize=15, fontweight='bold', y=0.98, color='#0f172a')
    plt.tight_layout(rect=[0, 0, 1, 0.95])
    plt.savefig(output_file, dpi=300)
    plt.close()
    print(f"[OK] Generated Side-by-Side: {output_file.name}")

def plot_triplet(csv1: Path, csv2: Path, csv3: Path, titles: list, main_title: str, output_file: Path, cmaps=["Reds", "Blues", "Greens"]):
    dfs = [pd.read_csv(csv1, index_col=0), pd.read_csv(csv2, index_col=0), pd.read_csv(csv3, index_col=0)]
    
    fig, axes = plt.subplots(1, 3, figsize=(24, 7), dpi=300)
    
    for i, (df, ax, cmap, title) in enumerate(zip(dfs, axes, cmaps, titles)):
        total = int(df.values.sum())
        corr = int(np.trace(df.values))
        acc = (corr / total) * 100 if total > 0 else 0
        labels = [f"{ID_NAMES.get(c, c)}" for c in df.index]
        
        sns.heatmap(df, annot=True, fmt="d", cmap=cmap, cbar=False,
                    xticklabels=labels, yticklabels=labels, linewidths=0.5, linecolor="#e2e8f0",
                    annot_kws={"size": 10, "weight": "bold"}, ax=ax)
        ax.set_title(f"{title}\nAkurasi: {acc:.2f}% ({corr:,}/{total:,} benar)",
                     fontsize=11, fontweight='bold', pad=10)
        ax.set_xlabel("Prediksi Model", fontsize=10, fontweight='bold', labelpad=6)
        ax.set_ylabel("Label Sebenarnya", fontsize=10, fontweight='bold', labelpad=6)
        ax.tick_params(axis='x', rotation=30, labelsize=8)
        ax.tick_params(axis='y', rotation=0, labelsize=8)

    plt.suptitle(main_title, fontsize=14, fontweight='bold', y=0.99, color='#0f172a')
    plt.tight_layout(rect=[0, 0, 1, 0.94])
    plt.savefig(output_file, dpi=300)
    plt.close()
    print(f"[OK] Generated Triplet: {output_file.name}")

def main():
    setup_matplotlib()
    print("Mulai membuat grafik visualisasi Confusion Matrix...")

    # =========================================================================
    # 1. MODEL FINAL DEPLOYED (model_yolo3_mixed.pt)
    # =========================================================================
    # 1a. Final Model on PlantVillage (10 kelas, 2.000 citra)
    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_final_yolo3_pv.csv",
        "Confusion Matrix — Model Operasional Final (PlantVillage Test Set)",
        "Model Ter-deploy (model_yolo3_mixed.pt) | 10 Kelas Laboratorium | 2.000 Citra Uji",
        RESULTS_DIR / "confusion_matrix_final_yolo3_pv.png",
        cmap="Blues"
    )

    # 1b. Final Model on Field YOLO3 (8 kelas, 1.160 citra)
    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_final_yolo3_field.csv",
        "Confusion Matrix — Model Operasional Final (Field YOLO3 Test Set)",
        "Model Ter-deploy (model_yolo3_mixed.pt) | 8 Kelas Foto Lapangan | 1.160 Citra Uji",
        RESULTS_DIR / "confusion_matrix_final_yolo3_field.png",
        cmap="Greens"
    )

    # 1c. Side-by-Side Final Model (PlantVillage vs Field)
    plot_side_by_side(
        RESULTS_DIR / "confusion_matrix_final_yolo3_pv.csv",
        RESULTS_DIR / "confusion_matrix_final_yolo3_field.csv",
        "1. Uji Laboratorium (PlantVillage - 10 Kelas)",
        "2. Uji Foto Lapangan (YOLO3 Crops - 8 Kelas)",
        "PERFORMA MODEL OPERASIONAL FINAL (model_yolo3_mixed.pt) DI DUA DOMAIN\nMembuktikan Akurasi Sangat Tinggi dan Seimbang Baik di Lab (96.70%) Maupun di Lapangan (96.64%)",
        RESULTS_DIR / "confusion_matrix_final_deployed_comparison.png",
        cmap1="Blues", cmap2="Greens"
    )

    # =========================================================================
    # 2. GENERALISASI LINTAS DATASET (Cross-Dataset Evaluation pada Dataset 2 - 3 Kelas)
    # =========================================================================
    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_cross_baseline_on_ds2.csv",
        "Confusion Matrix — Uji Silang: Baseline E02 pada Dataset Eksternal 2",
        "Evaluasi Generalisasi Lintas Domain (3 Kelas: Healthy, Leaf Mold, TYLCV)",
        RESULTS_DIR / "confusion_matrix_cross_baseline_on_ds2.png",
        cmap="Reds"
    )

    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_cross_fieldmixed_on_ds2.csv",
        "Confusion Matrix — Uji Silang: Model FieldMixed v1 pada Dataset Eksternal 2",
        "Evaluasi Generalisasi Lintas Domain (3 Kelas: Healthy, Leaf Mold, TYLCV)",
        RESULTS_DIR / "confusion_matrix_cross_fieldmixed_on_ds2.png",
        cmap="Blues"
    )

    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_cross_yolo3_on_ds2.csv",
        "Confusion Matrix — Uji Silang: Model Final YOLO3 pada Dataset Eksternal 2",
        "Evaluasi Generalisasi Lintas Domain (3 Kelas: Healthy, Leaf Mold, TYLCV)",
        RESULTS_DIR / "confusion_matrix_cross_yolo3_on_ds2.png",
        cmap="Greens"
    )

    plot_triplet(
        RESULTS_DIR / "confusion_matrix_cross_baseline_on_ds2.csv",
        RESULTS_DIR / "confusion_matrix_cross_fieldmixed_on_ds2.csv",
        RESULTS_DIR / "confusion_matrix_cross_yolo3_on_ds2.csv",
        [
            "1. Baseline E02 (Hanya Dilatih Lab)",
            "2. Model FieldMixed v1 (Fine-Tune Lapangan 1)",
            "3. Model Final YOLO3 (Fine-Tune YOLO-OBB)"
        ],
        "UJI GENERALISASI LINTAS DATASET (CROSS-DATASET EVALUATION PADA DATASET 2)\nMembuktikan Lonjakan Akurasi dari 69.16% -> 73.16% -> 89.18% pada Data Eksternal yang Tidak Pernah Dilatih",
        RESULTS_DIR / "confusion_matrix_cross_ds2_comparison.png",
        cmaps=["Reds", "Blues", "Greens"]
    )

    # =========================================================================
    # 3. EVOLUSI MODEL PADA FIELD TEST SET (Dataset 3 - 8 Kelas)
    # =========================================================================
    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_baseline_yolo3_field.csv",
        "Confusion Matrix — Baseline E02 pada Test Set Field 3 (8 Kelas)",
        "Evaluasi Model Baseline Asli pada Himpunan Uji Lapangan YOLO3 (1.145 Citra)",
        RESULTS_DIR / "confusion_matrix_baseline_yolo3_field.png",
        cmap="Oranges"
    )

    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_fieldmixed_yolo3_field.csv",
        "Confusion Matrix — Model FieldMixed v1 pada Test Set Field 3 (8 Kelas)",
        "Evaluasi Model Iterasi 1 pada Himpunan Uji Lapangan YOLO3 (1.114 Citra)",
        RESULTS_DIR / "confusion_matrix_fieldmixed_yolo3_field.png",
        cmap="Purples"
    )

    # Perbandingan 3 Model pada Field YOLO3
    if (RESULTS_DIR / "confusion_matrix_baseline_yolo3_field.csv").exists() and \
       (RESULTS_DIR / "confusion_matrix_fieldmixed_yolo3_field.csv").exists() and \
       (RESULTS_DIR / "confusion_matrix_final_yolo3_field.csv").exists():
        plot_triplet(
            RESULTS_DIR / "confusion_matrix_baseline_yolo3_field.csv",
            RESULTS_DIR / "confusion_matrix_fieldmixed_yolo3_field.csv",
            RESULTS_DIR / "confusion_matrix_final_yolo3_field.csv",
            [
                "1. Baseline E02",
                "2. Model FieldMixed v1",
                "3. Model Final YOLO3 (Deployed)"
            ],
            "EVOLUSI PERFORMA PADA HIMPUNAN UJI LAPANGAN YOLO3 (8 KELAS)\nPerkembangan Akurasi: Baseline (87.42%) -> FieldMixed v1 (90.84%) -> Model Final (96.64%)",
            RESULTS_DIR / "confusion_matrix_field_evolution_comparison.png",
            cmaps=["Oranges", "Purples", "Greens"]
        )

    # =========================================================================
    # 4. MODEL ITERASI SKEMA B PERTAMA (Field Clean 208 Citra)
    # =========================================================================
    plot_single_cm(
        RESULTS_DIR / "confusion_matrix_fieldB_clean.csv",
        "Confusion Matrix — Model Varian B (Dataset Lapangan 1 Bersih)",
        "Model Campuran Skema B Iterasi 1 | 208 Citra Uji Lapangan",
        RESULTS_DIR / "confusion_matrix_fieldB_clean.png",
        cmap="Greens"
    )

    print("\n[SELESAI] Seluruh grafik visualisasi Confusion Matrix berhasil diperbarui dan dibuat!")

if __name__ == "__main__":
    main()

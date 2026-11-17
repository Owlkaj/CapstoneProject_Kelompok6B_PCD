# 📁 Panduan Singkat Direktori `ml/results/`

Seluruh hasil evaluasi disusun rapi ke dalam folder sesuai **model dan jenis pengetesannya**.  
Untuk membaca ringkasan lengkap, buka [**`EVALUATION_REPORT.md`**](./EVALUATION_REPORT.md), atau buka [**`evaluation_dashboard.html`**](./evaluation_dashboard.html) di browser untuk tampilan visual interaktif.

## Peta Folder `ml/results/`

1. [**`01_baseline_e02/`**](./01_baseline_e02/) — **Model Baseline Awal (`E02_best.pt`, hanya dilatih di PlantVillage)**:
   - Uji Laboratorium (*PlantVillage*, 2.000 citra, 10 kelas): **96,60%**
   - Uji Lapangan *Group-Disjoint* (*Crop Daun Lapangan*, 1.058 citra, 8 kelas): **74,39%**
   - Berisi file JSON metrik, CSV/PNG confusion matrix, serta grafik metrik baseline.

2. [**`02_final_model/`**](./02_final_model/) — **Model Produksi Final (`final_model.pt`, Group-Disjoint)**:
   - Uji Laboratorium (*PlantVillage*, 2.000 citra, 10 kelas): **97,45%**
   - Uji Lapangan *Group-Disjoint* (*Crop Daun Lapangan*, 1.058 citra, 8 kelas): **93,01%** (**96,62%** pasca *Confidence Gating*)
   - Kalibrasi *Confidence Gating* (`t = 0.60` pada *val split* $n = 1.099$): [`gating_calibration.json`](./02_final_model/gating_calibration.json)

3. [**`03_comparisons/`**](./03_comparisons/) — **Grafik Perbandingan Antar Domain & Model**:
   - [`confusion_matrix_final_deployed_comparison.png`](./03_comparisons/confusion_matrix_final_deployed_comparison.png) *(Perbandingan Lab vs Lapangan Model Final)*
   - [`domain_comparison_barchart.png`](./03_comparisons/domain_comparison_barchart.png) *(Grafik batang F1-Score Baseline vs Model Final)*

4. [**`training_history/`**](./training_history/) — Log CSV dan grafik kurva *Loss/Accuracy* per epoch untuk tahap `E01`, `E02`, dan `E03`.

5. [**`archive_pre_groupfix/`**](./archive_pre_groupfix/) — Arsip hasil pengujian lama sebelum perbaikan *group-aware split*.

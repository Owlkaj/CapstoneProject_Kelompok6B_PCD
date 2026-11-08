# 📁 Panduan Singkat Direktori `ml/results/`

Silakan buka [**`EVALUATION_REPORT.md`**](./EVALUATION_REPORT.md) untuk laporan lengkap dan tabel penjelasan seluruh berkas, atau buka [**`evaluation_dashboard.html`**](./evaluation_dashboard.html) di browser untuk tampilan visual interaktif.

## Ringkasan Cepat Berkas Utama

1. **Model Produksi Final (`model_yolo3_grouped.pt` — Group-Disjoint, 8 Nov 2026)**:
   - **Uji Laboratorium (PlantVillage, 2.000 citra, 10 kelas — Akurasi 97,45%)**:
     - [`evaluation_metrics_final_yolo3_grouped_pv.json`](./evaluation_metrics_final_yolo3_grouped_pv.json)
     - [`confusion_matrix_final_yolo3_grouped_pv.csv`](./confusion_matrix_final_yolo3_grouped_pv.csv) & [`.png`](./confusion_matrix_final_yolo3_grouped_pv.png)
   - **Uji Lapangan Group-Disjoint (YOLO3 Field, 1.058 citra, 8 kelas — Akurasi 93,01%)**:
     - [`evaluation_metrics_final_yolo3_grouped_field.json`](./evaluation_metrics_final_yolo3_grouped_field.json)
     - [`confusion_matrix_final_yolo3_grouped_field.csv`](./confusion_matrix_final_yolo3_grouped_field.csv) & [`.png`](./confusion_matrix_final_yolo3_grouped_field.png)
   - **Uji Lintas-Dataset Independen (Dataset 2, 653 citra, 3 kelas — Akurasi 81,32%)**:
     - [`evaluation_metrics_cross_yolo3_grouped_on_ds2.json`](./evaluation_metrics_cross_yolo3_grouped_on_ds2.json)
     - [`confusion_matrix_cross_yolo3_grouped_on_ds2.csv`](./confusion_matrix_cross_yolo3_grouped_on_ds2.csv) & [`.png`](./confusion_matrix_cross_yolo3_grouped_on_ds2.png)
   - **Grafik Perbandingan Utama**:
     - [`confusion_matrix_final_deployed_comparison.png`](./confusion_matrix_final_deployed_comparison.png) *(Perbandingan berdampingan Lab vs Lapangan)*
     - [`domain_comparison_barchart.png`](./domain_comparison_barchart.png) *(Grafik batang F1-Score Baseline vs Model Final)*

2. **Model Pembanding Baseline (`E02_best.pt` — Hanya Dilatih di PlantVillage)**:
   - Lab (96,60%): [`evaluation_metrics_baseline_pv.json`](./evaluation_metrics_baseline_pv.json) & [`confusion_matrix_baseline_pv.csv`](./confusion_matrix_baseline_pv.csv)
   - Lapangan Group-Disjoint (74,39%): [`evaluation_metrics_baseline_grouped_field.json`](./evaluation_metrics_baseline_grouped_field.json) & [`confusion_matrix_baseline_grouped_field.csv`](./confusion_matrix_baseline_grouped_field.csv)
   - Lintas-Dataset DS2 (46,71%): [`evaluation_metrics_cross_baseline_on_ds2.json`](./evaluation_metrics_cross_baseline_on_ds2.json) & [`confusion_matrix_cross_baseline_on_ds2.csv`](./confusion_matrix_cross_baseline_on_ds2.csv)

3. **Kalibrasi Confidence Gating (`t = 0.60`)**:
   - [`gating_calibration.json`](./gating_calibration.json) *(Dikalibrasi pada validation split group-disjoint $n = 1.099$)*

4. **Subfolder**:
   - [`training_history/`](./training_history/) — Log CSV dan grafik kurva *Loss/Accuracy* per epoch untuk tahap `E01`, `E02`, dan `E03`.
   - [`archive_pre_groupfix/`](./archive_pre_groupfix/) — Arsip hasil eksperimen terdahulu sebelum perbaikan *group-aware split*.

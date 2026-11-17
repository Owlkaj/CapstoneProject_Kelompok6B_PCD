# 📏 `01_baseline_e02/` — Hasil Pengetesan Model Baseline (`E02_best.pt`)

Folder ini berisi seluruh hasil pengujian **Model Baseline E02** (EfficientNet-B0 yang baru dilatih menggunakan foto laboratorium *PlantVillage* saja, **belum** dilatih adaptasi lapangan). Folder ini menjadi **titik pembanding** untuk mengukur seberapa besar peningkatan setelah model dilatih ulang dengan data crop daun lapangan.

## Daftar Berkas per Skenario Pengetesan

### 1. Uji Laboratorium (`PlantVillage Test Set` · 2.000 citra · 10 kelas)
- **`evaluation_metrics_baseline_pv.json`** — Skor lengkap akurasi, precision, recall, dan F1-Score per kelas (**Akurasi: 96,60%**).
- **`confusion_matrix_baseline_pv.csv`** & **`.png`** — Tabel & gambar heatmap tebakan benar vs salah di data laboratorium.
- **`baseline_pv_metrics_chart.png`** — Grafik batang performa per kelas pada pengujian laboratorium.
- *Berkas alias ringkasan lama untuk uji Lab E02 yang sama:* `evaluation_metrics.json`, `confusion_matrix.csv`, `confusion_matrix.png`, `metrics_summary.csv`, `metrics_barchart.png`.

### 2. Uji Lapangan Group-Disjoint (`Field Bordered Crop Test` · 1.058 citra · 8 kelas)
- **`evaluation_metrics_baseline_grouped_field.json`** — Menunjukkan penurunan akurasi saat model lab langsung diuji pada foto kebun nyata (**Akurasi: 74,39%**, Macro F1: 74,36%).
- **`confusion_matrix_baseline_grouped_field.csv`** — Matriks kesalahan model baseline pada foto lapangan.

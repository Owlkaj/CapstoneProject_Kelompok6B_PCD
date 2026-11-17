# 🏆 `02_final_model/` — Hasil Pengetesan Model Produksi Final (`final_model.pt`)

Folder ini berisi seluruh hasil pengujian **Model Produksi Final** yang aktif dipakai di sistem aplikasi saat ini (`fastapi/models/final_model.pt`). Model ini merupakan hasil adaptasi domain lapangan (Tahap E03) pada gabungan data laboratorium *PlantVillage* + foto daun kebun nyata hasil pemotongan area daun (*bordered bounding-box crop*) dengan pembagian **Group-Disjoint** (bebas kebocoran foto asal antar-split).

## Daftar Berkas per Skenario Pengetesan

### 1. Uji Laboratorium (`PlantVillage Test Set` · 2.000 citra · 10 kelas)
- **`evaluation_metrics_final_pv.json`** — Metrik lengkap di domain studio (**Akurasi: 97,45%**, Macro F1: 97,45%).
- **`confusion_matrix_final_pv.csv`** & **`.png`** — Tabel & heatmap hasil uji laboratorium model final.

### 2. Uji Lapangan Group-Disjoint (`Field Bordered Crop Test` · 1.058 citra · 8 kelas)
- **`evaluation_metrics_final_field.json`** — Metrik utama di foto lapangan kebun nyata (**Akurasi: 93,01%**, Macro F1: 93,43%; naik **+18,62 pp** dari Baseline). Setelah disaring *Confidence Gating*, akurasi prediksi yang diterima mencapai **96,62%**.
- **`confusion_matrix_final_field.csv`** & **`.png`** — Tabel & heatmap hasil uji lapangan model final.

### 3. Kalibrasi Confidence Gating
- **`gating_calibration.json`** — Hasil kalibrasi ambang batas keyakinan model pada validation split *group-disjoint* (`n = 1.099`), yang menetapkan threshold produksi **`t = 0.60`**.

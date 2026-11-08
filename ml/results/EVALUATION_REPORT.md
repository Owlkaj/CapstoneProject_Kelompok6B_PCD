# 📊 Laporan Lengkap Evaluasi Model & Panduan Navigasi Hasil (`ml/results/`)

> **Status Terakhir Diperbarui:** 8 November 2026  
> **Model Produksi Aktif:** `ml/models/model_yolo3_grouped.pt` (di-deploy ke `fastapi/models/final_model.pt`)  
> **Dashboard Visual Interaktif:** Buka [`evaluation_dashboard.html`](./evaluation_dashboard.html) langsung di browser.

Dokumen ini berfungsi sebagai **indeks utama dan laporan komprehensif** untuk seluruh berkas hasil evaluasi di folder `ml/results/`, sehingga Anda dapat langsung mengetahui **setiap file itu laporan tentang bagian apa, model mana, dan dataset uji yang mana**.

---

## 🗺️ 1. Peta Navigasi Berkas di `ml/results/` (File Ini Tentang Apa?)

Untuk memudahkan pembacaan, seluruh berkas di folder `ml/results/` dikelompokkan ke dalam **4 kategori utama** (sedangkan file eksperimen lama sebelum perbaikan *group-aware split* telah dipindahkan rapi ke subfolder [`archive_pre_groupfix/`](./archive_pre_groupfix/)):

### Kategori 1 — 🏆 Hasil Evaluasi Model Produksi Final (`model_yolo3_grouped.pt` / Tahap E03)
*Ini adalah model akhir yang digunakan pada sistem aplikasi saat ini (dilatih pada gabungan PlantVillage + Lapangan YOLO3 dengan pembagian **Group-Disjoint** bebas kebocoran gambar asal).*

| Nama Berkas | Bagian / Skenario Evaluasi | Dataset & Jumlah Sampel | Hasil Utama |
|---|---|---|---|
| [`evaluation_metrics_final_yolo3_grouped_pv.json`](./evaluation_metrics_final_yolo3_grouped_pv.json)<br>[`confusion_matrix_final_yolo3_grouped_pv.csv`](./confusion_matrix_final_yolo3_grouped_pv.csv)<br>[`confusion_matrix_final_yolo3_grouped_pv.png`](./confusion_matrix_final_yolo3_grouped_pv.png) | **1A. Uji Laboratorium (In-Domain Studio)**<br>Mengukur apakah model final tetap akurat pada foto laboratorium bersih (10 kelas). | **PlantVillage Test Set**<br>2.000 citra (200/kelas, 10 kelas) | **Akurasi: 97,45%**<br>Macro F1: **97,45%** |
| [`evaluation_metrics_final_yolo3_grouped_field.json`](./evaluation_metrics_final_yolo3_grouped_field.json)<br>[`confusion_matrix_final_yolo3_grouped_field.csv`](./confusion_matrix_final_yolo3_grouped_field.csv)<br>[`confusion_matrix_final_yolo3_grouped_field.png`](./confusion_matrix_final_yolo3_grouped_field.png) | **1B. Uji Lapangan (Group-Disjoint Field Test)**<br>Mengukur performa pada crop daun kebun nyata di mana tidak ada satu pun daun/gambar asal yang sama dengan data latih. | **YOLO3 Field Clean Test**<br>1.058 citra (8 kelas lapangan) | **Akurasi: 93,01%**<br>Macro F1: **93,43%**<br>*(96,62% pasca gating)* |
| [`evaluation_metrics_cross_yolo3_grouped_on_ds2.json`](./evaluation_metrics_cross_yolo3_grouped_on_ds2.json)<br>[`confusion_matrix_cross_yolo3_grouped_on_ds2.csv`](./confusion_matrix_cross_yolo3_grouped_on_ds2.csv)<br>[`confusion_matrix_cross_yolo3_grouped_on_ds2.png`](./confusion_matrix_cross_yolo3_grouped_on_ds2.png) | **1C. Uji Generalisasi Lintas-Dataset (External DS2)**<br>Mengukur kemampuan generalisasi pada dataset kebun independen (`Skripsi_TomatoDisease v3`) yang **sama sekali tidak pernah dipakai saat training**. | **Dataset 2 (External YOLO)**<br>653 citra (3 kelas: *Healthy*, *Leaf Mold*, *TYLCV*) | **Akurasi: 81,32%**<br>Macro F1: **86,01%** |
| [`confusion_matrix_final_deployed_comparison.png`](./confusion_matrix_final_deployed_comparison.png) | **Visualisasi Perbandingan Model Final**<br>Gambar berdampingan *Confusion Matrix* Model Final di Laboratorium (97,45%) vs Lapangan (93,01%). | 2.000 Lab vs 1.058 Lapangan | Grafik PNG resolusi tinggi |
| [`domain_comparison_barchart.png`](./domain_comparison_barchart.png) | **Grafik Batang Efek Domain Shift & Pemulihan**<br>Membandingkan F1-Score per kelas: Baseline di Lab → Jatuh di Lapangan → Pulih setelah Fine-Tuning E03. | Perbandingan 3 skenario | Grafik PNG resolusi tinggi |

---

### Kategori 2 — 📏 Hasil Evaluasi Model Baseline (`E02_best.pt` — Sebelum Adaptasi Lapangan)
*Berkas ini digunakan sebagai **titik pembanding (baseline)** untuk membuktikan seberapa besar peningkatan akurasi lapangan setelah dilakukan Domain Adaptation (Tahap E03).*

| Nama Berkas | Bagian / Skenario Pembanding | Dataset & Jumlah Sampel | Hasil Baseline |
|---|---|---|---|
| [`evaluation_metrics_baseline_pv.json`](./evaluation_metrics_baseline_pv.json)<br>[`confusion_matrix_baseline_pv.csv`](./confusion_matrix_baseline_pv.csv)<br>[`confusion_matrix_baseline_pv.png`](./confusion_matrix_baseline_pv.png)<br>[`baseline_pv_metrics_chart.png`](./baseline_pv_metrics_chart.png) | **2A. Baseline E02 di Laboratorium**<br>(Juga identik dengan [`evaluation_metrics.json`](./evaluation_metrics.json), [`confusion_matrix.csv`](./confusion_matrix.csv), [`metrics_summary.csv`](./metrics_summary.csv)). | **PlantVillage Test Set**<br>2.000 citra (10 kelas) | **Akurasi: 96,60%**<br>Macro F1: **96,60%** |
| [`evaluation_metrics_baseline_grouped_field.json`](./evaluation_metrics_baseline_grouped_field.json)<br>[`confusion_matrix_baseline_grouped_field.csv`](./confusion_matrix_baseline_grouped_field.csv) | **2B. Baseline E02 di Lapangan Group-Disjoint**<br>Membuktikan penurunan akurasi (*domain shift*) saat model laboratorium diuji langsung pada citra lapangan. | **YOLO3 Field Clean Test**<br>1.058 citra (8 kelas) | **Akurasi: 74,39%**<br>Macro F1: **74,36%**<br>*(Model final naik +18,62 pp)* |
| [`evaluation_metrics_cross_baseline_on_ds2.json`](./evaluation_metrics_cross_baseline_on_ds2.json)<br>[`confusion_matrix_cross_baseline_on_ds2.csv`](./confusion_matrix_cross_baseline_on_ds2.csv)<br>[`confusion_matrix_cross_baseline_on_ds2.png`](./confusion_matrix_cross_baseline_on_ds2.png) | **2C. Baseline E02 di Lintas-Dataset (DS2)**<br>Membuktikan bahwa tanpa latihan lapangan, model baseline gagal mengenali dataset eksternal. | **Dataset 2 (External YOLO)**<br>653 citra (3 kelas) | **Akurasi: 46,71%**<br>Macro F1: **52,48%**<br>*(Model final naik +34,61 pp)* |

---

### Kategori 3 — 🛡️ Kalibrasi Confidence Gating
| Nama Berkas | Keterangan Laporan | Dataset Kalibrasi | Ambang Terpilih |
|---|---|---|---|
| [`gating_calibration.json`](./gating_calibration.json) | **Kalibrasi Ambang Batas Keyakinan Model**<br>Berisi statistik distribusi `confidence`, `margin`, `sharpness`, `brightness`, dan `min_side` antara prediksi benar vs salah, serta tabel sapuan (*sweep*) ambang batas `0.30 – 0.90`. | **Validation Split Group-Disjoint**<br>(`manifest_field_only_clean.csv`, `split=val`, **1.099 citra**) | **`t = 0.60`**<br>Menangkap 60,9% kesalahan pada `val` (akurasi diterima naik 93,72% → **97,36%**) |

---

### Kategori 4 — 📈 Riwayat Pelatihan per Epoch ([`training_history/`](./training_history/))
| Nama Berkas di `training_history/` | Tahap Eksperimen | Ringkasan |
|---|---|---|
| [`E01_history.csv`](./training_history/E01_history.csv) · [`E01_accuracy.png`](./training_history/E01_accuracy.png) · [`E01_loss.png`](./training_history/E01_loss.png) | **Tahap E01 — Feature Extraction (Head Only)**<br>Backbone EfficientNet-B0 dibekukan (*frozen*), melatih classifier head selama 10 epoch pada PlantVillage. | Val Acc Terbaik: **90,70%**<br>Val Loss: `0.3127` |
| [`E02_history.csv`](./training_history/E02_history.csv) · [`E02_accuracy.png`](./training_history/E02_accuracy.png) · [`E02_loss.png`](./training_history/E02_loss.png) | **Tahap E02 — Fine-Tuning Laboratorium (PlantVillage)**<br>Membuka *unfreeze* stage atas backbone dari checkpoint E01 selama 10 epoch (`lr = 1e-4`). | Val Acc Terbaik: **95,60%**<br>Val Loss: `0.1353` |
| [`E03_history.csv`](./training_history/E03_history.csv) · [`E03_accuracy.png`](./training_history/E03_accuracy.png) · [`E03_loss.png`](./training_history/E03_loss.png) | **Tahap E03 — Domain Adaptation Lapangan (Group-Disjoint)**<br>Fine-tuning dari `E02_best.pt` pada campuran 9.600 citra lapangan + 9.600 PlantVillage (`lr = 1e-5`, 3 epoch). | Val Acc Terbaik: **94,72%**<br>Val Loss: `0.1567` |

---

## 📌 2. Ringkasan Eksekutif Perbandingan Model (Baseline vs Model Final)

| Skenario Pengujian | Karakteristik Data Uji | Jumlah Sampel | Baseline E02 (Lab Only) | **Model Produksi Final (`model_yolo3_grouped.pt`)** | Peningkatan ($\Delta$) |
|---|---|:---:|:---:|:---:|:---:|
| **1. Laboratorium (PlantVillage)** | Daun tunggal latar polos, 10 kelas | 2.000 citra | 96,60% (F1: 96,60%) | **97,45%** (F1: 97,45%) | **+0,85 pp** |
| **2. Lapangan (Group-Disjoint)** | Crop daun kebun nyata, 8 kelas | 1.058 citra | 74,39% (F1: 74,36%) | **93,01%** (F1: 93,43%)<br>*(**96,62%** dengan Gating `t=0.60`)* | **+18,62 pp** |
| **3. Lintas-Dataset (External DS2)** | Kebun independen luar, 3 kelas | 653 citra | 46,71% (F1: 52,48%) | **81,32%** (F1: 86,01%) | **+34,61 pp** |

---

## 🔬 3. Rincian Performa Per Kelas — Model Produksi Final

### A. Domain Laboratorium (`PlantVillage Test Set` — 2.000 Citra, 10 Kelas)
*Sumber berkas: [`evaluation_metrics_final_yolo3_grouped_pv.json`](./evaluation_metrics_final_yolo3_grouped_pv.json) & [`confusion_matrix_final_yolo3_grouped_pv.csv`](./confusion_matrix_final_yolo3_grouped_pv.csv)*

| No | Kelas Teknis | Nama Indonesia | Precision | Recall | F1-Score | Sampel Uji |
|:---:|---|---|:---:|:---:|:---:|:---:|
| 1 | `Bacterial Spot` | Bercak Bakteri | 95,57% | 97,00% | **96,28%** | 200 |
| 2 | `Early Blight` | Bercak Kering | 95,07% | 96,50% | **95,78%** | 200 |
| 3 | `Healthy` | Daun Sehat | 96,14% | 99,50% | **97,79%** | 200 |
| 4 | `Late Blight` | Hawar Daun | 99,48% | 95,00% | **97,19%** | 200 |
| 5 | `Leaf Mold` | Kapang Daun | 98,49% | 98,00% | **98,25%** | 200 |
| 6 | `Tomato Mosaic Virus` | Virus Mosaik | 98,50% | 98,50% | **98,50%** | 200 |
| 7 | `Septoria Leaf Spot` | Bercak Septoria | 95,61% | 98,00% | **96,79%** | 200 |
| 8 | `Spider Mites` | Tungau Laba-laba | 98,03% | 99,50% | **98,76%** | 200 |
| 9 | `Target Spot` | Bercak Sasaran | 99,47% | 94,50% | **96,92%** | 200 |
| 10 | `Tomato Yellow Leaf Curl Virus (TYLCV)` | Virus Kuning Keriting | 98,49% | 98,00% | **98,25%** | 200 |
| — | **Rata-rata Makro (Macro Average)** | — | **97,49%** | **97,45%** | **97,45%** | **2.000** |

---

### B. Domain Lapangan Group-Disjoint (`YOLO3 Field Clean Test Set` — 1.058 Citra, 8 Kelas)
*Sumber berkas: [`evaluation_metrics_final_yolo3_grouped_field.json`](./evaluation_metrics_final_yolo3_grouped_field.json) & [`confusion_matrix_final_yolo3_grouped_field.csv`](./confusion_matrix_final_yolo3_grouped_field.csv)*

> **Catatan Kelas:** Dataset lapangan YOLO3 mencakup 8 kelas. Kelas `Bacterial Spot` dan `Target Spot` tidak tersedia pada dataset lapangan tersebut sehingga tidak dihitung dalam rata-rata makro lapangan agar metrik tidak terdistorsi.

| No | Kelas Teknis | Nama Indonesia | Precision | Recall | F1-Score (Final) | F1 Baseline E02 | Sampel Uji |
|:---:|---|---|:---:|:---:|:---:|:---:|:---:|
| 1 | `Early Blight` | Bercak Kering | 86,96% | 88,24% | **87,59%** | 53,51% | 136 |
| 2 | `Healthy` | Daun Sehat | 93,62% | 92,31% | **92,96%** | 76,56% | 143 |
| 3 | `Late Blight` | Hawar Daun | 95,20% | 92,25% | **93,70%** | 78,42% | 129 |
| 4 | `Leaf Mold` | Kapang Daun | 93,98% | 93,28% | **93,63%** | 75,09% | 134 |
| 5 | `Tomato Mosaic Virus` | Virus Mosaik | 93,50% | 99,14% | **96,23%** | 81,03% | 116 |
| 6 | `Septoria Leaf Spot` | Bercak Septoria | 95,04% | 93,71% | **94,37%** | 73,93% | 143 |
| 7 | `Spider Mites` | Tungau Laba-laba | 97,17% | 96,26% | **96,71%** | 81,38% | 107 |
| 8 | `Tomato Yellow Leaf Curl Virus (TYLCV)` | Virus Kuning Keriting | 93,79% | 90,67% | **92,20%** | 74,91% | 150 |
| — | **Rata-rata Makro (8 Kelas Hadir)** | — | **93,66%** | **93,23%** | **93,43%** | **74,36%** | **1.058** |

---

### C. Domain Lintas-Dataset Independen (`Dataset 2 External` — 653 Citra, 3 Kelas)
*Sumber berkas: [`evaluation_metrics_cross_yolo3_grouped_on_ds2.json`](./evaluation_metrics_cross_yolo3_grouped_on_ds2.json) & [`confusion_matrix_cross_yolo3_grouped_on_ds2.csv`](./confusion_matrix_cross_yolo3_grouped_on_ds2.csv)*

> **Cara Membaca `confusion_matrix_cross_yolo3_grouped_on_ds2.csv`:**  
> Dataset 2 berasal dari proyek Roboflow berbeda (`Skripsi_TomatoDisease v3`) yang hanya memiliki 3 kelas (`Healthy`, `Leaf Mold`, `TYLCV`) dengan total **653 citra**. Karena model memprediksi ke dalam ruang 10 kelas, sebanyak **531 dari 653 citra (81,32%)** diprediksi tepat (212 *Healthy* + 151 *Leaf Mold* + 168 *TYLCV*), sedangkan sebagian kecil citra diprediksi sebagai kelas bercak lain di luar 3 kelas tersebut.

| No | Kelas Teknis | Nama Indonesia | Precision | Recall | F1-Score (Final) | F1 Baseline E02 | Sampel Uji |
|:---:|---|---|:---:|:---:|:---:|:---:|:---:|
| 1 | `Healthy` | Daun Sehat | 90,21% | 89,83% | **90,02%** | 71,79% | 236 |
| 2 | `Leaf Mold` | Kapang Daun | 96,18% | 68,95% | **80,32%** | 41,26% | 219 |
| 3 | `Tomato Yellow Leaf Curl Virus (TYLCV)` | Virus Kuning Keriting | 89,84% | 84,85% | **87,27%** | 44,40% | 198 |
| — | **Rata-rata Makro (3 Kelas Hadir)** | — | **92,08%** | **81,21%** | **86,01%** | **52,48%** | **653** |

---

## 🛡️ 4. Ringkasan Kalibrasi Confidence Gating (`gating_calibration.json`)

Kalibrasi dilakukan pada **Validation Split Group-Disjoint** (`manifest_field_only_clean.csv`, `split=val`, $n = 1.099$, akurasi dasar 93,72%) agar pemilihan ambang batas bebas dari kebocoran data uji (*test leakage*):
* **Ambang Terpilih (`GATE_MIN_CONFIDENCE`)**: **`0.60`** (menggantikan ambang lama `0.55`).
* **Hasil pada Validation Split ($n = 1.099$)**: Menangkap **42 dari 69 kesalahan (60,9%)** (`45/69` atau **65,2%** pada gerbang multi-sinyal penuh) dan meningkatkan akurasi prediksi yang diterima menjadi **97,36%** (**97,54%** pada gerbang penuh).
* **Verifikasi pada Held-Out Test Split ($n = 1.058$)**: Menangkap **41 dari 74 kesalahan (55,4%)** (`45/74` atau **60,8%** pada gerbang penuh) dan meningkatkan akurasi prediksi yang diterima dari **93,01% → 96,62%** (**96,82%** pada gerbang penuh).

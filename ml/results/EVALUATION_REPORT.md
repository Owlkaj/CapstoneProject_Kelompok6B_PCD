# 📊 Laporan Hasil Evaluasi Model Deep Learning — TomatCare

Dokumen ini menyajikan rekapitulasi performa model klasifikasi penyakit daun tomat pada himpunan data uji independen (*test set*).

---

## 📌 Ringkasan Eksekutif

| Parameter | Hasil Evaluasi | Keterangan |
|---|:---:|---|
| **Model Terpilih** | **E02** | EfficientNet-B0 (Transfer Learning + Fine-Tuning Stage 6-7) |
| **Total Sampel Test Set** | **2,000 citra** | 200 citra independen per kelas (100% *unseen data*) |
| **Akurasi Keseluruhan (Accuracy)** | **96.6%** | 1.932 citra diprediksi dengan tepat |
| **Rata-rata Precision (Macro)** | **96.65%** | Tingkat ketepatan saat model memprediksi suatu kelas |
| **Rata-rata Recall (Macro)** | **96.6%** | Kemampuan model menemukan seluruh kasus penyakit |
| **Rata-rata F1-Score (Macro)** | **96.6%** | Keseimbangan harmonis Precision dan Recall |

---

## 🍃 Tabel Performa Per Kelas Penyakit

| No | Kelas Teknis | Nama Indonesia | Precision | Recall | F1-Score | Sampel Uji | Status |
|:---:|---|---|:---:|:---:|:---:|:---:|:---:|
| 1 | `Bacterial Spot` | Bercak Bakteri | 92.42% | 97.50% | **94.89%** | 200 | ✅ Sangat Baik (>94%) |
| 2 | `Early Blight` | Bercak Kering | 95.92% | 94.00% | **94.95%** | 200 | ✅ Sangat Baik (>94%) |
| 3 | `Healthy` | Daun Sehat | 95.65% | 99.00% | **97.30%** | 200 | 🌟 Luar Biasa (>97%) |
| 4 | `Late Blight` | Hawar Daun | 97.41% | 94.00% | **95.67%** | 200 | ✅ Sangat Baik (>94%) |
| 5 | `Leaf Mold` | Kapang Daun | 94.61% | 96.50% | **95.54%** | 200 | ✅ Sangat Baik (>94%) |
| 6 | `Tomato Mosaic Virus` | Virus Mosaik | 98.50% | 98.50% | **98.50%** | 200 | 🌟 Luar Biasa (>97%) |
| 7 | `Septoria Leaf Spot` | Bercak Septoria | 96.46% | 95.50% | **95.98%** | 200 | ✅ Sangat Baik (>94%) |
| 8 | `Spider Mites` | Tungau Laba-laba | 97.06% | 99.00% | **98.02%** | 200 | 🌟 Luar Biasa (>97%) |
| 9 | `Target Spot` | Bercak Sasaran | 99.47% | 94.00% | **96.66%** | 200 | ✅ Sangat Baik (>94%) |
| 10 | `Tomato Yellow Leaf Curl Virus (TYLCV)` | Virus Kuning Keriting | 98.99% | 98.00% | **98.49%** | 200 | 🌟 Luar Biasa (>97%) |

---

## 📈 File Visualisasi & Data Pendukung

1. **Grafik Batang Performa**: [`metrics_barchart.png`](file:///D:/casptone/sistem/ml/results/metrics_barchart.png)
2. **Matriks Kebingungan (Confusion Matrix)**: [`confusion_matrix.png`](file:///D:/casptone/sistem/ml/results/confusion_matrix.png)
3. **Data Tabular Excel/CSV**: [`metrics_summary.csv`](file:///D:/casptone/sistem/ml/results/metrics_summary.csv)
4. **Dashboard HTML Interaktif**: [`evaluation_dashboard.html`](file:///D:/casptone/sistem/ml/results/evaluation_dashboard.html) *(bisa dibuka langsung di browser)*

---

## 💡 Analisis Temuan Kunci

1. **Kelas dengan Performa Tertinggi**:
   - `Tomato Mosaic Virus` (**F1: 98.50%**) dan `Tomato Yellow Leaf Curl Virus` (**F1: 98.49%**) memiliki tingkat akurasi mendekati sempurna karena pola visual kerusakan virus yang sangat khas.
   - `Healthy` (Daun Sehat) mencapai Recall **99.00%**, menandakan tanaman sehat hampir tidak pernah salah didiagnosis sebagai tanaman sakit.

2. **Daya Tangkap Penyakit (Recall)**:
   - `Spider Mites` (99.00%) dan `Bacterial Spot` (97.50%) memiliki tingkat recall yang sangat tinggi, krusial agar petani tidak melewatkan serangan hama/patogen sejak fase awal.

3. **Stabilitas Model**:
   - Selisih antara Macro F1 (96.6%) dan Weighted F1 (96.60%) adalah 0.00%, membuktikan model memiliki keadilan prediksi yang sangat seimbang di seluruh 10 kelas tanpa bias kelas tertentu.

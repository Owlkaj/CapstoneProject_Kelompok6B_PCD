# 🗄️ Arsip Hasil Eksperimen Terdahulu (`archive_pre_groupfix/`)

Folder ini menyimpan berkas evaluasi dari **eksperimen tahap awal dan sebelum perbaikan *group-aware split* (8 November 2026)** agar folder utama `ml/results/` tetap rapi dan hanya memuat laporan resmi terkini.

## Mengapa Berkas Ini Diarsipkan?

1. **Eksperimen Tahap Awal (208 citra lapangan non-YOLO)**:
   - `*_field.*`, `*_fieldA_clean.*`, `*_fieldB_clean.*`, `*_baseline_field*`
   - Merupakan eksperimen awal pada dataset 208 citra uji lapangan sebelum beralih ke ekstraksi crop kotak anotasi YOLOv8-OBB.

2. **Eksperimen Crop Daun Sebelum Perbaikan *Group-Aware Split* (16 Oktober 2026)**:
   - `*_final_yolo3_field.*` (96,64%), `*_final_yolo3_pv.*`, `*_baseline_yolo3_field.*`, `*_fieldmixed_yolo3_field.*`, `*_yoloonly_yolo3_field.*`, `E03_history.pre_groupfix_20261108_211438.csv`.
   - Pada pembagian lama, beberapa crop daun yang berasal dari satu foto kebun yang sama dapat terpecah ke *train* dan *test* sekaligus. Setelah diperbaiki menggunakan `StratifiedGroupKFold` (`group_id` gambar asal), evaluasi resmi yang berlaku berada di [`02_final_model/`](../02_final_model/) (`*_final_*`).


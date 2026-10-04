# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Machine Learning Sistem Klasifikasi Penyakit Daun Tomat

**Versi:** 3.0  
**Status:** Final / Agent-Ready / Siap Implementasi End-to-End  
**Fokus:** Machine Learning + REST API + Web Application + Supabase  
**Model:** EfficientNetB0  
**Framework:** PyTorch  
**Metode:** Transfer Learning + Fine-Tuning  
**Dataset:** Kaggle – Tomato Leaf Disease Image Classification  
**Target Dataset Kerja:** 20.000 gambar (2.000 gambar per kelas)

---

# 0. Aturan Implementasi, Source of Truth, dan AI Agent Protocol

Dokumen ini adalah **single source of truth teknis** untuk implementasi sistem klasifikasi penyakit daun tomat. PRD ini ditujukan bukan hanya untuk developer manusia, tetapi juga sebagai spesifikasi kerja untuk AI coding agent.

## 0.1 Aturan Prioritas

Gunakan aturan berikut ketika mengimplementasikan, menguji, atau mengubah sistem:

1. Requirement yang ditulis sebagai **MUST / wajib** tidak boleh diubah tanpa persetujuan pengguna dan pembaruan PRD.
2. Kontrak API, nama field database, nama cookie, nama bucket, endpoint, dan nama environment variable yang telah ditetapkan harus dipertahankan persis.
3. Bab ML (3–17) menjadi acuan untuk dataset, preprocessing, training, evaluation, model, dan inference.
4. Bab integrasi aplikasi (18–37) menjadi acuan untuk FastAPI, Laravel, session-cookie, Supabase Database, Supabase Storage, security, testing, dan deployment.
5. Jika detail implementasi belum ditentukan, agent **tidak boleh menciptakan requirement baru**. Agent memilih implementasi yang paling sederhana dan kompatibel, lalu mendokumentasikan keputusan implementasi tersebut.
6. Angka eksperimen yang bersifat baseline bukan jaminan hasil akhir. Nilai aktual harus berasal dari eksperimen yang benar-benar dijalankan.
7. Jangan membuat tabel, endpoint, field, konfigurasi, atau alur alternatif yang menduplikasi kontrak yang sudah ada.
8. Jika implementasi membutuhkan perubahan terhadap requirement, architecture, contract, atau behavior yang ditetapkan PRD, agent harus berhenti pada bagian yang terdampak dan meminta persetujuan pengguna.

## 0.2 Istilah Normatif

- **MUST / wajib** = harus dipenuhi.
- **SHOULD / sebaiknya** = rekomendasi implementasi; dapat disesuaikan jika ada alasan teknis yang terdokumentasi dan tidak melanggar MUST requirement.
- **MAY / opsional** = tidak wajib.
- **BLOCKED** = agent tidak boleh melanjutkan task yang bergantung pada keputusan/informasi yang belum tersedia.
- **DONE** = task telah memenuhi acceptance criteria dan memiliki bukti validasi.

## 0.3 Tingkat Kewenangan AI Agent

AI agent bekerja dengan tiga tingkat kewenangan berikut.

### Level 1 — Agent bebas menentukan

Agent boleh menentukan sendiri detail implementasi yang:

- tidak mengubah requirement PRD;
- tidak mengubah architecture;
- tidak mengubah API/database/storage contract;
- tidak mengubah behavior yang diwajibkan;
- merupakan detail teknis umum yang diperlukan agar implementasi berjalan.

Contoh: penamaan fungsi internal, pembagian helper/service internal, urutan statement dalam fungsi, dan detail kode yang tidak terlihat sebagai contract eksternal.

### Level 2 — Agent boleh menentukan dengan dokumentasi

Agent boleh memilih implementasi teknis yang masih kompatibel dengan PRD, tetapi keputusan tersebut harus dicatat jika memengaruhi dependency, konfigurasi, struktur internal utama, atau cara deployment.

Contoh: memilih library pendukung kecil yang diperlukan, menentukan pola dependency injection internal, atau memilih mekanisme retry yang sesuai contract.

### Level 3 — Wajib meminta persetujuan pengguna

Agent **MUST NOT** mengambil keputusan sendiri jika perubahan menyangkut:

- requirement MUST;
- tujuan atau scope proyek;
- EfficientNetB0 atau model utama yang ditetapkan;
- framework utama PyTorch, FastAPI, Laravel, atau Supabase;
- architecture antar-komponen;
- API endpoint/request/response contract;
- nama tabel, field, foreign key, constraint, cookie, bucket, atau environment variable yang telah ditetapkan;
- mekanisme session tanpa login;
- perubahan security boundary;
- perubahan alur data end-to-end;
- penambahan authentication/login;
- perubahan yang membuat komponen lain harus diubah contract-nya.

## 0.4 Aturan Saat Requirement Tidak Jelas atau Konflik

Jika agent menemukan requirement yang ambigu, tidak lengkap, atau saling bertentangan:

1. Agent harus mengidentifikasi bagian yang ambigu/konflik secara eksplisit.
2. Agent tidak boleh memilih solusi yang mengubah architecture atau contract hanya berdasarkan asumsi.
3. Jika masalah dapat diselesaikan dengan detail implementasi yang tidak mengubah requirement, agent boleh memilih solusi Level 1 atau Level 2 dan mendokumentasikannya.
4. Jika diperlukan keputusan produk, architecture, contract, security, atau behavior, agent menandai task sebagai **BLOCKED** dan meminta keputusan pengguna.
5. Agent tidak boleh melanjutkan task yang bergantung langsung pada keputusan tersebut seolah-olah keputusan sudah dibuat.
6. Setelah keputusan diberikan, keputusan tersebut harus dicatat agar implementasi berikutnya konsisten.

Jika dua bagian PRD tampak bertentangan, jangan diam-diam memilih salah satunya. Tandai konflik dan minta klarifikasi kecuali prioritasnya sudah ditentukan secara eksplisit oleh PRD.

## 0.5 Aturan Perubahan Requirement dan Kode

- Code implementation harus mengikuti PRD; bukan sebaliknya.
- Agent tidak boleh mengubah PRD hanya agar kode yang sudah dibuat terlihat benar.
- Jika ditemukan kesalahan/typo pada PRD yang memengaruhi implementation contract, agent harus meminta konfirmasi sebelum mengubah contract.
- Refactoring internal diperbolehkan selama behavior dan contract yang diwajibkan tetap sama.
- Perubahan dependency yang mengubah architecture atau deployment behavior memerlukan persetujuan pengguna.
- Setiap perubahan lintas komponen harus divalidasi kembali terhadap contract komponen terkait.

## 0.6 Aturan Validasi dan Klaim Selesai

Agent **MUST NOT** menyatakan task atau phase selesai hanya karena kode telah ditulis.

Sebuah task hanya dapat berstatus **DONE** jika:

1. implementasi telah dibuat;
2. acceptance criteria task terpenuhi;
3. validation/test yang relevan telah dijalankan;
4. hasil validasi tidak menunjukkan kegagalan yang belum ditangani; dan
5. bukti hasil validasi dicatat secara ringkas.

Jika test tidak dapat dijalankan karena environment, credential, dataset, service, atau dependency belum tersedia, status harus **BLOCKED** atau **PARTIALLY VALIDATED**, bukan DONE.

Agent dilarang mengarang hasil training, accuracy, test result, API response, database result, atau E2E success yang belum benar-benar diperoleh.

## 0.7 Aturan Dependency dan Tooling

Agent boleh menambahkan dependency yang benar-benar diperlukan untuk implementasi detail selama dependency tersebut tidak mengubah architecture utama dan keputusan tersebut didokumentasikan.

Penambahan framework utama, service eksternal baru, database alternatif, authentication system, atau komponen yang mengubah architecture harus mendapat persetujuan pengguna terlebih dahulu.

## 0.8 Aturan Eksekusi Bertahap

Implementasi harus mengikuti dependency antar-phase pada **Bab 33 — Checklist Implementasi End-to-End**.

Agent harus:

1. membaca PRD sebelum memulai phase;
2. memeriksa prerequisite phase;
3. mengerjakan task phase yang sedang aktif;
4. menjalankan acceptance criteria dan validation;
5. hanya melanjutkan jika phase memenuhi exit criteria;
6. mencatat blocker atau keputusan yang diperlukan;
7. tidak mengerjakan phase yang bergantung pada artifact yang belum valid.

Agent tidak diwajibkan meminta konfirmasi pengguna setelah setiap task yang berhasil. Persetujuan pengguna hanya diperlukan ketika masuk Level 3, terjadi blocker, atau ditemukan perubahan requirement/architecture/contract.

## 0.9 Aturan Konsistensi Antar-Komponen

Setiap perubahan harus diperiksa terhadap seluruh komponen yang bergantung padanya:

```text
Machine Learning
      ↓
Model Artifact + Label Mapping + Preprocessing
      ↓
FastAPI Contract
      ↓
Laravel Integration
      ↓
Supabase Database + Storage
      ↓
End-to-End UI / History
```

Agent tidak boleh menyelesaikan masalah pada satu layer dengan cara yang membuat contract atau behavior layer lain menjadi tidak valid.

## 0.10 Single Checklist dan Single Contract

- Bab 31 adalah sumber contract API/database yang harus diikuti.
- Bab 32 adalah sumber minimum integration testing.
- Bab 33 adalah **satu-satunya checklist implementasi**.
- Bab 34 adalah sumber Definition of Done end-to-end.
- Jika informasi yang sama muncul di beberapa bab, informasi tersebut harus tetap konsisten; jangan membuat versi alternatif dengan nilai berbeda.

# 1. Informasi Proyek

## 1.1 Nama Proyek

**Machine Learning Sistem Klasifikasi Penyakit Daun Tomat**

## 1.2 Deskripsi

Sistem ini merupakan bagian Machine Learning dari aplikasi klasifikasi penyakit daun tomat berbasis citra. Sistem menerima gambar daun tomat sebagai input, melakukan preprocessing, kemudian menggunakan model EfficientNetB0 untuk memprediksi kelas penyakit.

Model dikembangkan menggunakan pendekatan transfer learning dan dilanjutkan dengan fine-tuning untuk meningkatkan kemampuan model dalam mengenali karakteristik visual masing-masing kelas.

Output utama sistem adalah:
- kelas penyakit hasil prediksi;
- confidence score;
- model terlatih yang dapat digunakan kembali untuk inference;
- metrik evaluasi dan confusion matrix.

## 1.3 Tujuan

Tujuan pengembangan Machine Learning:

1. Membuat model klasifikasi citra daun tomat dengan 10 kelas.
2. Menggunakan EfficientNetB0 sebagai arsitektur utama.
3. Memanfaatkan transfer learning untuk mempercepat proses pelatihan.
4. Melakukan fine-tuning terhadap bagian model yang relevan.
5. Menghasilkan model yang dapat digunakan untuk inference pada gambar baru.
6. Menyediakan evaluasi yang terukur menggunakan metrik klasifikasi.
7. Menyediakan endpoint inference melalui FastAPI agar dapat diintegrasikan dengan aplikasi web.

---

# 2. Ruang Lingkup

## 2.1 Termasuk dalam Proyek

- Persiapan dataset.
- Pemeriksaan kualitas dataset.
- Pemilihan subset dataset kerja.
- Pembagian dataset train, validation, dan test.
- Preprocessing citra.
- Augmentasi data pada training set.
- Implementasi EfficientNetB0.
- Transfer learning.
- Fine-tuning.
- Training dan validation.
- Early stopping.
- Checkpoint model.
- Evaluasi model.
- Confusion matrix.
- Classification report.
- Per-class metrics.
- Penyimpanan model final.
- Inference gambar baru.
- API inference menggunakan FastAPI.
- Dokumentasi eksperimen dan reproducibility.

## 2.2 Tidak Termasuk

- Training model di Laravel.
- Pembuatan model dari awal tanpa pretrained weights.
- Pengumpulan responden untuk pelabelan baru.
- Pengembangan perangkat keras khusus.
- Diagnosis medis tanaman secara langsung.
- Pengambilan keputusan agronomis otomatis di luar klasifikasi citra.

---

# 3. Dataset

## 3.1 Sumber Dataset

Dataset utama menggunakan dataset Kaggle:

**Tomato Leaf Disease Image Classification**

Sumber:
https://www.kaggle.com/datasets/cyrilrajc/tomato-leaf-disease-image-classification

Dataset digunakan sebagai sumber data citra untuk proses pengembangan dan evaluasi model.

## 3.2 Kelas

Sistem menggunakan 10 kelas:

1. Bacterial Spot
2. Early Blight
3. Healthy
4. Late Blight
5. Leaf Mold
6. Tomato Mosaic Virus
7. Septoria Leaf Spot
8. Spider Mites
9. Target Spot
10. Tomato Yellow Leaf Curl Virus (TYLCV)

Label harus disimpan dalam mapping yang konsisten antara training, evaluation, model storage, dan inference.

Contoh:

```text
0 -> Bacterial Spot
1 -> Early Blight
2 -> Healthy
3 -> Late Blight
4 -> Leaf Mold
5 -> Tomato Mosaic Virus
6 -> Septoria Leaf Spot
7 -> Spider Mites
8 -> Target Spot
9 -> Tomato Yellow Leaf Curl Virus (TYLCV)
```

## 3.3 Target Dataset Kerja

Target dataset kerja adalah:

**20.000 gambar total**

Dengan distribusi:

| Kelas | Target |
|---|---:|
| Bacterial Spot | 2.000 |
| Early Blight | 2.000 |
| Healthy | 2.000 |
| Late Blight | 2.000 |
| Leaf Mold | 2.000 |
| Tomato Mosaic Virus | 2.000 |
| Septoria Leaf Spot | 2.000 |
| Spider Mites | 2.000 |
| Target Spot | 2.000 |
| Tomato Yellow Leaf Curl Virus (TYLCV) | 2.000 |
| **Total** | **20.000** |

Target tersebut merupakan **working dataset** yang diperoleh melalui **sampling/stratified subset secara reproducible** dari open dataset Kaggle *Tomato Leaf Disease Image Classification* oleh **cyrilrajc**.

Dengan demikian, angka 20.000 gambar merupakan target working dataset untuk eksperimen, bukan klaim bahwa dataset mentah Kaggle secara persis berisi 20.000 gambar yang siap digunakan. Prosedur sampling harus mempertahankan representasi 10 kelas dan dicatat pada manifest/dataset preparation record.

Jika suatu kelas memiliki lebih dari 2.000 gambar, data dapat di-sampling secara reproducible.

Jika suatu kelas memiliki kurang dari 2.000 gambar setelah proses pemeriksaan dan pembersihan, jumlah target kelas tersebut tidak boleh dipalsukan menggunakan duplikasi persis. Jumlah aktual harus didokumentasikan atau target dataset disesuaikan.

---

# 4. Pemeriksaan Dataset

Sebelum training, dataset wajib diperiksa.

## 4.1 Pemeriksaan Struktur

Periksa:

- jumlah kelas;
- nama kelas;
- jumlah gambar per kelas;
- format file;
- ukuran gambar;
- file yang tidak dapat dibuka;
- file kosong/rusak;
- gambar yang tidak sesuai kelas.

## 4.2 Pemeriksaan Duplikasi

Lakukan pemeriksaan duplicate atau near-duplicate apabila memungkinkan.

Tujuannya adalah mengurangi kemungkinan gambar yang sama atau hampir sama masuk ke subset yang berbeda dan menyebabkan data leakage.

## 4.3 Manifest Dataset

Buat manifest yang minimal berisi:

```text
image_path
label
class_name
split
```

Contoh:

```text
dataset/processed/Bacterial Spot/img001.jpg,Bacterial Spot,0,train
```

Manifest digunakan agar pembagian data dapat direproduksi.

---

# 5. Pembagian Dataset

Target pembagian:

| Split | Persentase | Jumlah |
|---|---:|---:|
| Train | 80% | 16.000 |
| Validation | 10% | 2.000 |
| Test | 10% | 2.000 |
| **Total** | **100%** | **20.000** |

Pembagian harus dilakukan secara stratified sehingga distribusi kelas tetap terjaga.

## 5.1 Train Set

Digunakan untuk:
- pembelajaran model;
- augmentasi;
- optimasi parameter.

## 5.2 Validation Set

Digunakan untuk:
- monitoring training;
- pemilihan hyperparameter;
- early stopping;
- pemilihan checkpoint/model.

## 5.3 Test Set

Test set harus disimpan sebagai data yang tidak digunakan selama pemilihan model.

Test set hanya digunakan untuk evaluasi akhir setelah model final dipilih berdasarkan validation set.

---

# 6. Preprocessing

Semua gambar diproses menjadi:

- RGB;
- ukuran 224 × 224 pixel.

## 6.1 Resize

Input model:

```text
224 × 224 × 3
```

## 6.2 Normalisasi

Normalisasi harus mengikuti preprocessing yang sesuai dengan pretrained EfficientNetB0 weights yang digunakan.

Konfigurasi normalisasi harus disimpan agar preprocessing training dan inference identik.

## 6.3 Konsistensi

Preprocessing harus konsisten pada:

- training;
- validation;
- test;
- inference.

Perbedaan preprocessing antara training dan inference harus dihindari.

---

# 7. Data Augmentation

Augmentasi hanya diterapkan pada training set.

Augmentasi yang digunakan sebagai baseline:

- Random Horizontal Flip;
- Light Rotation;
- Random Resized Crop;
- Light Brightness/Contrast Adjustment.

Augmentasi harus tetap realistis terhadap kondisi daun tomat dan tidak mengubah label secara semantik.

Validation dan test tidak menggunakan augmentasi training.

---

# 8. Arsitektur Model

## 8.1 Model Utama

Model yang digunakan:

**EfficientNetB0**

Model menggunakan pretrained weights sebagai titik awal transfer learning.

## 8.2 Modifikasi Classifier

Classifier bawaan diganti agar menghasilkan:

```text
10 output classes
```

Output layer harus sesuai dengan jumlah kelas dataset.

---

# 9. Transfer Learning

Eksperimen pertama menggunakan transfer learning.

## 9.1 E01 – Baseline Transfer Learning

Tahapan:

1. Load EfficientNetB0 pretrained.
2. Ganti classifier menjadi 10 kelas.
3. Freeze backbone.
4. Train classifier.
5. Monitor validation loss dan validation metrics.
6. Simpan checkpoint terbaik.
7. Terapkan early stopping.

Baseline:

| Parameter | Nilai |
|---|---|
| Experiment | E01 |
| Model | EfficientNetB0 |
| Backbone | Frozen |
| Optimizer | AdamW |
| Learning Rate | 1e-3 |
| Batch Size | 16 |
| Epoch | 10–15 |
| Early Stopping Patience | 3 |
| Loss | CrossEntropyLoss |

Batch size dapat diturunkan apabila GPU mengalami out-of-memory.

Urutan fallback:

```text
16 -> 8 -> 4 -> 2
```

---

# 10. Fine-Tuning

Fine-tuning dilakukan setelah baseline transfer learning selesai.

## 10.1 E02 – Fine-Tuning

Tahapan:

1. Load checkpoint terbaik dari E01.
2. Unfreeze bagian atas backbone yang dipilih.
3. Gunakan learning rate lebih kecil.
4. Train kembali model.
5. Monitor validation metrics.
6. Simpan checkpoint terbaik.
7. Terapkan early stopping.

Baseline:

| Parameter | Nilai |
|---|---|
| Experiment | E02 |
| Starting Model | Best E01 |
| Optimizer | AdamW |
| Learning Rate | sekitar 1e-5 – 3e-5 |
| Batch Size | 16 atau fallback |
| Epoch | 10–20 |
| Early Stopping Patience | 3 |
| Loss | CrossEntropyLoss |

Jumlah layer yang di-unfreeze merupakan bagian dari eksperimen dan harus dicatat.

---

## 10.2 Experiment Tracking

Setiap eksperimen harus memiliki ID dan catatan konfigurasi.

### E01 — Transfer Learning Baseline

```text
EfficientNetB0
Backbone frozen
AdamW
LR = 1e-3
Batch = 16
Epoch = 10–15
```

### E02 — Fine-Tuning

```text
Start from best E01
Selected upper layers unfrozen
AdamW
LR = 1e-5 – 3e-5
Batch = 16 atau fallback
Epoch = 10–20
```

Eksperimen tambahan hanya dilakukan bila diperlukan dan harus dicatat dengan konfigurasi yang sama lengkapnya.

---

# 11. Hardware dan Environment

Target environment:

- OS: Windows 11
- GPU: AMD Radeon RX 6550M
- VRAM: 4 GB
- Python: 3.10
- Framework: PyTorch
- Model library: torchvision
- GPU acceleration: DirectML jika kompatibel
- CPU fallback: wajib tersedia

## 11.1 DirectML

Karena GPU menggunakan AMD, DirectML menjadi jalur akselerasi yang perlu diuji.

Sebelum training penuh:

1. Pastikan PyTorch dapat berjalan.
2. Uji DirectML.
3. Uji tensor sederhana.
4. Uji mini-batch EfficientNetB0.
5. Pastikan forward dan backward pass berjalan.
6. Jika gagal atau tidak stabil, gunakan CPU fallback.

Kompatibilitas tidak boleh diasumsikan sebelum pengujian.

---

# 12. Training

Training loop harus mencakup:

1. Load batch.
2. Forward pass.
3. Hitung loss.
4. Backward pass.
5. Optimizer step.
6. Hitung training metrics.
7. Validation setelah epoch.
8. Simpan history.
9. Checkpoint jika validation membaik.
10. Early stopping jika tidak ada improvement.

## 12.1 Checkpoint

Checkpoint minimal menyimpan:

- model state;
- optimizer state;
- epoch;
- validation loss;
- validation metric;
- konfigurasi eksperimen.

Contoh:

```text
models/
├── E01_best.pt
└── E02_best.pt
```

---

# 13. Monitoring Training

Simpan history:

- training loss;
- validation loss;
- training accuracy;
- validation accuracy;
- epoch;
- learning rate apabila digunakan.

History disimpan untuk membuat grafik:

- Loss Curve;
- Accuracy Curve.

Contoh:

```text
results/
└── training_history/
    ├── E01_history.csv
    ├── E01_loss.png
    ├── E01_accuracy.png
    ├── E02_history.csv
    ├── E02_loss.png
    └── E02_accuracy.png
```

---

# 14. Evaluation

Evaluasi dilakukan pada test set setelah model final dipilih.

Metrik:

- Accuracy;
- Precision;
- Recall;
- F1-score;
- Loss;
- Confusion Matrix;
- Classification Report;
- Per-class metrics.

## 14.1 Confusion Matrix

Confusion matrix digunakan untuk melihat pola prediksi antar kelas.

Analisis minimal mencakup:

- kelas yang paling sering diprediksi benar;
- kelas yang paling sering tertukar;
- pasangan kelas dengan confusion tinggi;
- distribusi kesalahan antar kelas.

## 14.2 Classification Report

Classification report minimal memuat:

- precision;
- recall;
- F1-score;
- support.

Per-class result harus disimpan.

---

# 15. Model Selection

Pemilihan model dilakukan menggunakan validation set.

Contoh:

```text
E01 -> Transfer Learning
E02 -> Fine-Tuning
```

Model terbaik dipilih berdasarkan metrik validation yang telah ditentukan.

Test set tidak digunakan untuk memilih model.

Setelah model final ditentukan, test set digunakan satu kali untuk evaluasi final.

---

# 16. Penyimpanan Model Final

Model final disimpan dalam format yang dapat dimuat kembali untuk inference.

Contoh:

```text
models/
└── final_model.pt
```

Selain weights, simpan metadata:

```text
model/
├── final_model.pt
├── label_mapping.json
├── preprocessing.json
└── training_config.yaml
```

Metadata minimal:

- nama model;
- jumlah kelas;
- label mapping;
- input size;
- preprocessing;
- normalization;
- augmentation;
- pretrained weights;
- hyperparameters;
- experiment ID;
- seed;
- versi library;
- device.

---

# 17. Inference

Inference menerima satu gambar daun tomat.

Pipeline:

```text
Input Image
    ↓
Validation File
    ↓
RGB Conversion
    ↓
Resize 224×224
    ↓
Normalization
    ↓
EfficientNetB0
    ↓
Logits
    ↓
Softmax
    ↓
Predicted Class + Confidence
```

## 17.1 Output

Contoh:

```json
{
  "class": "Early Blight",
  "confidence": 0.93
}
```

Confidence merupakan probabilitas hasil softmax dan bukan jaminan bahwa prediksi selalu benar.

---

# 18. FastAPI

Model Machine Learning disediakan melalui FastAPI sebagai service inference terpisah dari Laravel.

## 18.1 Peran Service

FastAPI bertanggung jawab untuk:

- memuat `final_model.pt` saat service dimulai;
- memvalidasi file citra yang diterima;
- menjalankan preprocessing yang identik dengan pipeline training/inference;
- menjalankan inference EfficientNetB0;
- menghitung probabilitas softmax;
- mengembalikan kelas, confidence, class ID, dan waktu inference;
- tidak menyimpan riwayat pengguna dan tidak mengelola session/cookie.

Laravel tetap menjadi application layer yang berinteraksi dengan pengguna, mengelola session ID, Supabase, dan tampilan web.

## 18.2 Endpoint

Endpoint utama:

```text
POST /predict
```

Request:

```text
Content-Type: multipart/form-data
Form field:
  image=<file>
```

Format input utama:

```text
.jpg
.jpeg
.png
```

## 18.3 Kontrak Request

Field wajib:

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `image` | file/binary | Ya | Citra daun tomat yang akan diklasifikasikan |

FastAPI harus memvalidasi:

- file tersedia;
- MIME type/ekstensi yang diizinkan;
- file dapat dibaca sebagai citra;
- ukuran file maksimum sesuai konfigurasi aplikasi;
- citra dapat dikonversi ke RGB.

Jika validasi gagal, API mengembalikan HTTP 4xx dengan pesan error yang dapat ditangani Laravel.

## 18.4 Kontrak Response

Response sukses menggunakan HTTP `200 OK` dan JSON:

```json
{
  "class": "Early Blight",
  "confidence": 0.93,
  "class_id": "Early Blight",
  "inference_time_ms": 142
}
```

Keterangan:

| Field | Tipe | Keterangan |
|---|---|---|
| `class` | string | Nama kelas hasil prediksi |
| `confidence` | float | Probabilitas softmax untuk kelas terpilih, rentang 0–1 |
| `class_id` | string | Identifier kelas yang konsisten dengan label mapping; pada kontrak saat ini menggunakan nama kelas |
| `inference_time_ms` | integer/float | Waktu inference model dalam milidetik |

`confidence` bukan jaminan bahwa prediksi selalu benar.

## 18.5 Error Response

Minimal dukung pola berikut:

```json
{
  "detail": "Invalid image file"
}
```

Contoh status:

| HTTP Status | Kondisi |
|---|---|
| 400 | Request/file tidak valid |
| 413 | File melebihi batas ukuran |
| 415 | Format/MIME type tidak didukung |
| 422 | Field `image` tidak tersedia atau tidak valid |
| 500 | Kesalahan internal inference |
| 503 | Model/service belum siap |

Detail error internal tidak boleh mengekspos path filesystem atau informasi sensitif.

## 18.6 Lifecycle Model

Saat startup FastAPI:

1. Baca konfigurasi model.
2. Load arsitektur EfficientNetB0.
3. Load `final_model.pt`.
4. Load `label_mapping.json`.
5. Load `preprocessing.json`.
6. Pindahkan model ke device yang tersedia.
7. Set model ke evaluation mode (`eval()`).
8. Jalankan health/readiness check.

Model sebaiknya dimuat sekali saat startup, bukan setiap request, agar overhead inference tidak bertambah karena loading weights berulang.

## 18.7 Integrasi Web

Alur dasar:

```text
User
  ↓
Laravel Web Application
  ↓
POST multipart/form-data
  ↓
FastAPI /predict
  ↓
EfficientNetB0
  ↓
Prediction JSON
  ↓
Laravel
  ↓
Supabase Storage + Supabase Database
  ↓
Web UI / Riwayat
```

Laravel tidak digunakan untuk training Machine Learning.

---

# 19. Arsitektur Sistem End-to-End

Sistem terdiri dari empat lapisan utama:

1. **Machine Learning Layer** — training, evaluation, model artifact, dan inference logic.
2. **FastAPI Inference Layer** — REST API untuk menjalankan model.
3. **Laravel Application Layer** — web UI, session/cookie, validasi input, orkestrasi API, penyimpanan riwayat.
4. **Supabase Layer** — PostgreSQL Database untuk metadata/riwayat dan Storage untuk berkas citra.

## 19.1 Arsitektur Komponen

```text
┌───────────────────────────────┐
│           Browser             │
│ Upload Image / History Page   │
└───────────────┬───────────────┘
                │ HTTP/HTTPS
                ▼
┌───────────────────────────────┐
│       Laravel Application     │
│ - Web UI / Controller         │
│ - tomato_session_id Cookie    │
│ - Session Middleware          │
│ - FastAPI Client              │
│ - Supabase Client             │
└───────────┬───────────┬───────┘
            │           │
            │ REST      │ Supabase API
            ▼           ▼
┌────────────────┐  ┌─────────────────────────┐
│    FastAPI     │  │        Supabase         │
│  POST /predict │  │ PostgreSQL + Storage    │
│  EfficientNet  │  │ detection-images bucket │
└────────────────┘  └─────────────────────────┘
```

## 19.2 Prinsip Tanggung Jawab Komponen

| Komponen | Tanggung Jawab |
|---|---|
| Browser | Mengirim citra dan menampilkan hasil/riwayat |
| Laravel | Session ID, validasi aplikasi, orkestrasi request, upload Storage, penyimpanan riwayat, rendering UI |
| FastAPI | Inference model dan response prediction |
| EfficientNetB0 | Klasifikasi citra |
| Supabase PostgreSQL | Data penyakit, metadata model, session, dan riwayat deteksi |
| Supabase Storage | Penyimpanan citra input |
| Cookie | Identitas pseudonim browser untuk mengambil riwayat tanpa login |

---

# 20. Integrasi Laravel Application

Laravel merupakan application layer utama dan tidak menjalankan training model.

## 20.1 Modul Laravel

Modul minimum:

```text
Laravel
├── Controllers
│   ├── DetectionController
│   └── HistoryController
├── Middleware
│   └── EnsureTomatoSession
├── Services
│   ├── FastApiService
│   └── SupabaseService
├── Requests
│   └── DetectionRequest
├── Models
│   ├── Penyakit
│   ├── ModelCnn
│   ├── UserSession
│   └── RiwayatDeteksi
├── Views
│   ├── detection/
│   └── history/
└── routes/
    └── web.php
```

Nama kelas dapat disesuaikan dengan konvensi project Laravel yang digunakan.

## 20.2 Alur Request Deteksi

1. Pengguna membuka halaman deteksi.
2. Middleware memeriksa cookie `tomato_session_id`.
3. Jika cookie belum ada, Laravel membuat UUID baru.
4. UUID disimpan ke cookie dan tabel `user_sessions`.
5. Pengguna mengunggah citra.
6. Laravel memvalidasi file.
7. Laravel mengirim file ke FastAPI `POST /predict`.
8. Laravel menerima JSON hasil inference.
9. Laravel mengunggah citra asli ke bucket Supabase Storage `detection-images`.
10. Supabase mengembalikan Public URL.
11. Laravel menyimpan record pada `riwayat_deteksi`.
12. Laravel menampilkan diagnosis, confidence, dan informasi hasil kepada pengguna.

## 20.3 Urutan Penyimpanan

Urutan wajib:

```text
FastAPI response diterima
        ↓
Upload citra ke Supabase Storage
        ↓
Dapatkan Public URL
        ↓
INSERT riwayat_deteksi
        ↓
Render hasil diagnosis
```

Jika upload Storage gagal, record riwayat tidak boleh dibuat dengan `citra_input` kosong. Jika INSERT database gagal setelah upload berhasil, aplikasi harus mencatat error dan menerapkan strategi cleanup/retry sesuai kebutuhan agar orphaned object dapat ditangani.

---

# 21. Middleware Session & Cookie Laravel

Sistem tidak memerlukan registrasi/login untuk fitur riwayat. Identitas riwayat menggunakan UUID yang disimpan pada cookie browser.

## 21.1 Cookie

Nama cookie:

```text
tomato_session_id
```

Nilai:

```text
UUID v4
```

Contoh:

```text
550e8400-e29b-41d4-a716-446655440000
```

Cookie harus memiliki konfigurasi keamanan sesuai deployment, minimal mempertimbangkan:

- `HttpOnly=true` agar tidak dapat diakses JavaScript;
- `Secure=true` pada HTTPS/production;
- `SameSite=Lax` atau sesuai kebutuhan deployment;
- masa berlaku sesuai kebijakan aplikasi.

Cookie tidak boleh berisi data diagnosis atau data pribadi; hanya identifier session pseudonim.

## 21.2 Middleware `EnsureTomatoSession`

Middleware berjalan pada route yang memerlukan session, minimal halaman deteksi dan riwayat.

Pseudo-flow:

```text
Request
  ↓
Read tomato_session_id
  ↓
Ada?
 ├─ Ya → Validasi UUID → upsert/check user_sessions → lanjut
 └─ Tidak → Generate UUID → Set Cookie → INSERT user_sessions → lanjut
```

Aturan:

1. Jika cookie tidak ada, generate UUID baru.
2. Simpan `session_id` ke `user_sessions`.
3. Set cookie `tomato_session_id`.
4. Jika cookie ada, gunakan UUID tersebut.
5. Update `last_accessed_at` ketika session digunakan.
6. Jangan membuat session baru pada setiap request bila cookie valid.
7. Semua query riwayat wajib difilter menggunakan `session_id` dari server-side request context, bukan `session_id` bebas dari parameter pengguna.

## 21.3 Keamanan Akses Riwayat

Endpoint riwayat harus berbentuk konsep:

```text
GET /history
GET /history/{id_riwayat}
```

Untuk detail riwayat, Laravel wajib memastikan:

```sql
WHERE id_riwayat = :id
  AND session_id = :current_session_id
```

Dengan demikian pengguna hanya dapat melihat riwayat yang terkait dengan cookie browser miliknya.

Catatan: mekanisme ini merupakan **pseudonymous browser/session tracking**, bukan autentikasi identitas. Penghapusan cookie akan membuat browser memperoleh session ID baru dan riwayat lama tidak otomatis muncul kembali.

---

# 22. Supabase Database PostgreSQL

Supabase PostgreSQL menyimpan master data penyakit, metadata model, session browser, dan riwayat deteksi.

## 22.1 Entitas

```text
penyakit
   │
   └───────────────┐
                   │
model_cnn          │
   │               │
   └───────┐       │
           ▼       ▼
       riwayat_deteksi
           ▲
           │
      user_sessions
```

Relasi:

- `user_sessions` 1:N `riwayat_deteksi`
- `penyakit` 1:N `riwayat_deteksi`
- `model_cnn` 1:N `riwayat_deteksi`

## 22.2 DDL Schema

DDL berikut ditujukan untuk PostgreSQL/Supabase. Nama tabel/kolom mengikuti spesifikasi PRD.

```sql
-- =========================================================
-- 1. MASTER PENYAKIT
-- =========================================================

CREATE TABLE IF NOT EXISTS public.penyakit (
    id_penyakit BIGSERIAL PRIMARY KEY,
    nama_penyakit VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT
);

-- =========================================================
-- 2. MASTER MODEL CNN
-- =========================================================

CREATE TABLE IF NOT EXISTS public.model_cnn (
    id_model BIGSERIAL PRIMARY KEY,
    nama_model VARCHAR(100) NOT NULL,
    arsitektur VARCHAR(100) NOT NULL,
    file_bobot TEXT NOT NULL,
    akurasi NUMERIC(6,5),
    status VARCHAR(20) NOT NULL DEFAULT 'inactive',
    CONSTRAINT model_cnn_status_check
        CHECK (status IN ('active', 'inactive', 'archived')),
    CONSTRAINT model_cnn_akurasi_check
        CHECK (akurasi IS NULL OR (akurasi >= 0 AND akurasi <= 1))
);

-- =========================================================
-- 3. SESSION BROWSER TANPA LOGIN
-- =========================================================

CREATE TABLE IF NOT EXISTS public.user_sessions (
    session_id UUID PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    last_accessed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- =========================================================
-- 4. RIWAYAT DETEKSI
-- =========================================================

CREATE TABLE IF NOT EXISTS public.riwayat_deteksi (
    id_riwayat BIGSERIAL PRIMARY KEY,
    session_id UUID NOT NULL,
    id_penyakit BIGINT NOT NULL,
    id_model BIGINT NOT NULL,
    citra_input TEXT NOT NULL,
    confidence_score NUMERIC(6,5) NOT NULL,
    tanggal_deteksi TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT fk_riwayat_session
        FOREIGN KEY (session_id)
        REFERENCES public.user_sessions(session_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_riwayat_penyakit
        FOREIGN KEY (id_penyakit)
        REFERENCES public.penyakit(id_penyakit)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_riwayat_model
        FOREIGN KEY (id_model)
        REFERENCES public.model_cnn(id_model)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT riwayat_confidence_check
        CHECK (confidence_score >= 0 AND confidence_score <= 1)
);

CREATE INDEX IF NOT EXISTS idx_riwayat_session
    ON public.riwayat_deteksi(session_id);

CREATE INDEX IF NOT EXISTS idx_riwayat_tanggal
    ON public.riwayat_deteksi(tanggal_deteksi DESC);

CREATE INDEX IF NOT EXISTS idx_riwayat_penyakit
    ON public.riwayat_deteksi(id_penyakit);

CREATE INDEX IF NOT EXISTS idx_riwayat_model
    ON public.riwayat_deteksi(id_model);
```

## 22.3 Seed 10 Kelas Penyakit

Master data harus memuat tepat 10 kelas target:

```sql
INSERT INTO public.penyakit (nama_penyakit, deskripsi) VALUES
('Bacterial Spot', 'Kelas penyakit daun tomat Bacterial Spot.'),
('Early Blight', 'Kelas penyakit daun tomat Early Blight.'),
('Healthy', 'Daun tomat sehat.'),
('Late Blight', 'Kelas penyakit daun tomat Late Blight.'),
('Leaf Mold', 'Kelas penyakit daun tomat Leaf Mold.'),
('Tomato Mosaic Virus', 'Kelas penyakit Tomato Mosaic Virus.'),
('Septoria Leaf Spot', 'Kelas penyakit Septoria Leaf Spot.'),
('Spider Mites', 'Kelas kerusakan/serangan Spider Mites.'),
('Target Spot', 'Kelas penyakit daun tomat Target Spot.'),
('Tomato Yellow Leaf Curl Virus (TYLCV)', 'Kelas penyakit Tomato Yellow Leaf Curl Virus.')
ON CONFLICT (nama_penyakit) DO NOTHING;
```

Urutan `id_penyakit` tidak boleh diasumsikan sama dengan class index model. Mapping model tetap harus berasal dari `label_mapping.json`.

## 22.4 Data Model CNN

Contoh record model aktif:

```text
nama_model   = EfficientNetB0-Final
arsitektur   = EfficientNetB0
file_bobot   = models/final_model.pt
akurasi      = <hasil evaluasi test aktual>
status       = active
```

Nilai `akurasi` harus diisi berdasarkan hasil eksperimen aktual, bukan angka target.

## 22.5 Kebijakan Data

- `session_id` menjadi foreign key untuk seluruh riwayat.
- `citra_input` menyimpan Public URL Storage.
- `confidence_score` menyimpan nilai 0–1.
- `tanggal_deteksi` menggunakan timezone-aware timestamp.
- Model yang sudah pernah dipakai dalam riwayat tidak boleh dihapus secara sembarangan; gunakan status `archived` jika diperlukan untuk menjaga integritas histori.

---

# 23. Supabase Storage

## 23.1 Bucket

Gunakan bucket:

```text
detection-images
```

Konfigurasi awal yang diminta:

```text
Public: ON
```

Bucket digunakan untuk menyimpan citra input yang diproses sistem.

## 23.2 Struktur Object

Gunakan path yang memudahkan isolasi session:

```text
detection-images/
└── {session_id}/
    └── {uuid}.{extension}
```

Contoh:

```text
detection-images/
└── 550e8400-e29b-41d4-a716-446655440000/
    └── 8d7b3f4a-2c2b-4f18-a1a4-2e1f2d3c4b5a.jpg
```

Nama file harus dihasilkan server, bukan mempercayai nama file asli pengguna.

## 23.3 Public URL

Setelah upload berhasil, Laravel memperoleh Public URL object.

Nilai tersebut disimpan pada:

```text
riwayat_deteksi.citra_input
```

Contoh konseptual:

```text
https://<supabase-project>.supabase.co/storage/v1/object/public/detection-images/<session_id>/<uuid>.jpg
```

URL aktual harus dibentuk menggunakan konfigurasi project Supabase, bukan hard-coded.

## 23.4 Kebijakan Storage

Walaupun bucket bersifat public untuk kebutuhan Public URL, aplikasi tetap harus:

- membatasi MIME type dan ukuran upload pada application layer;
- menghasilkan object path dari UUID/session ID;
- tidak menerima arbitrary path dari client;
- tidak menyimpan secret/service-role key di browser;
- menggunakan Supabase server-side credentials sesuai kebutuhan operasi upload.

---

# 24. Integrasi Laravel ↔ Supabase

## 24.1 Environment Laravel

File `.env` minimal memuat:

```env
SUPABASE_URL=https://<project-ref>.supabase.co
SUPABASE_ANON_KEY=<supabase-anon-key>
FASTAPI_URL=http://127.0.0.1:8000
```

`SUPABASE_ANON_KEY` dan konfigurasi Supabase harus diakses melalui Laravel server-side configuration. Jangan menaruh service-role key di source code atau frontend.

## 24.2 Konfigurasi `config/services.php`

Contoh:

```php
'supabase' => [
    'url' => env('SUPABASE_URL'),
    'anon_key' => env('SUPABASE_ANON_KEY'),
],

'fastapi' => [
    'url' => env('FASTAPI_URL', 'http://127.0.0.1:8000'),
],
```

Nama konfigurasi dapat disesuaikan dengan struktur aplikasi.

## 24.3 HTTP Client FastAPI

Laravel menggunakan HTTP client untuk mengirim multipart request:

```text
POST {FASTAPI_URL}/predict
Content-Type: multipart/form-data
field: image
```

Timeout harus dikonfigurasi agar request inference tidak menggantung tanpa batas.

Contoh kebijakan:

```text
connect timeout: 5 detik
request timeout: 60 detik
```

Nilai final dapat disesuaikan dengan karakteristik environment.

## 24.4 Validasi Laravel

Sebelum memanggil FastAPI:

```text
required
image
mimes: jpg,jpeg,png
max: <configured limit>
```

Laravel juga harus memeriksa bahwa file benar-benar dapat dibaca sebagai gambar.

---

# 25. Alur Data End-to-End

## 25.1 Sequence Deteksi

```text
User
  │
  │ Upload image
  ▼
Laravel
  │
  ├── EnsureTomatoSession
  │      └── create/read tomato_session_id
  │
  ├── Validate image
  │
  ├── POST /predict ───────────────► FastAPI
  │                                  │
  │                                  ├── Read image
  │                                  ├── Preprocess
  │                                  ├── EfficientNetB0
  │                                  └── JSON prediction
  │
  ◄── Prediction JSON ──────────────┘
  │
  ├── Upload image ────────────────► Supabase Storage
  │                                  │
  ◄── Public URL ───────────────────┘
  │
  ├── INSERT riwayat_deteksi ─────► Supabase PostgreSQL
  │
  └── Render result + confidence
  ▼
User
```

## 25.2 Data yang Mengalir

| Tahap | Data |
|---|---|
| Browser → Laravel | File gambar |
| Laravel → FastAPI | Multipart file `image` |
| FastAPI → Laravel | `class`, `confidence`, `class_id`, `inference_time_ms` |
| Laravel → Storage | File gambar |
| Storage → Laravel | Public URL |
| Laravel → PostgreSQL | `session_id`, `id_penyakit`, `id_model`, `citra_input`, `confidence_score`, `tanggal_deteksi` |
| PostgreSQL → Laravel | Record riwayat |
| Laravel → Browser | Hasil diagnosis + metadata |

---

# 26. Riwayat Deteksi Tanpa Login

## 26.1 Endpoint Web

Route yang disarankan:

```text
GET  /detect
POST /detect
GET  /history
GET  /history/{id_riwayat}
```

## 26.2 Query Riwayat

Daftar riwayat harus mengambil session ID dari middleware/server context:

```sql
SELECT
    r.id_riwayat,
    p.nama_penyakit,
    m.nama_model,
    r.citra_input,
    r.confidence_score,
    r.tanggal_deteksi
FROM public.riwayat_deteksi r
JOIN public.penyakit p
    ON p.id_penyakit = r.id_penyakit
JOIN public.model_cnn m
    ON m.id_model = r.id_model
WHERE r.session_id = :session_id
ORDER BY r.tanggal_deteksi DESC;
```

Untuk detail satu record:

```sql
SELECT
    r.id_riwayat,
    r.session_id,
    p.nama_penyakit,
    p.deskripsi,
    m.nama_model,
    m.arsitektur,
    r.citra_input,
    r.confidence_score,
    r.tanggal_deteksi
FROM public.riwayat_deteksi r
JOIN public.penyakit p
    ON p.id_penyakit = r.id_penyakit
JOIN public.model_cnn m
    ON m.id_model = r.id_model
WHERE r.id_riwayat = :id_riwayat
  AND r.session_id = :session_id;
```

`session_id` dari client tidak boleh diterima sebagai parameter otorisasi yang dipercaya. Nilai otoritatif harus berasal dari cookie yang sudah divalidasi oleh middleware.

---

# 27. Environment dan Deployment Development

## 27.1 Local Development

Environment utama:

```text
OS          : Windows 11
Python      : 3.10
ML          : PyTorch
Model       : torchvision EfficientNetB0
GPU         : AMD Radeon RX 6550M
VRAM        : 4 GB
Acceleration: DirectML jika kompatibel
Fallback    : CPU
Web         : Laravel
Inference   : FastAPI
Database    : Supabase PostgreSQL
Storage     : Supabase Storage
```

DirectML wajib diuji dengan mini-batch sebelum training penuh. Jika tidak stabil, gunakan CPU fallback.

## 27.2 `.env` Laravel

```env
APP_NAME=TomatoDiseaseDetection
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

SUPABASE_URL=https://<project-ref>.supabase.co
SUPABASE_ANON_KEY=<supabase-anon-key>

FASTAPI_URL=http://127.0.0.1:8001
```

Port FastAPI dapat disesuaikan. Nilai pada environment harus menjadi single source of truth bagi Laravel.

## 27.3 Environment FastAPI

Contoh konfigurasi yang direkomendasikan:

```env
MODEL_PATH=./models/final_model.pt
LABEL_MAPPING_PATH=./model/label_mapping.json
PREPROCESSING_CONFIG_PATH=./model/preprocessing.json
DEVICE=auto
MAX_UPLOAD_SIZE_MB=10
```

Jika implementasi menggunakan file konfigurasi Python/YAML, nama variabel dapat dipetakan sesuai struktur service.

---

## 27.4 Konfigurasi Terpusat dan Reproducibility

Gunakan konfigurasi terpusat agar eksperimen dan deployment dapat direproduksi.

Contoh konfigurasi ML:

```yaml
seed: 42

dataset:
  total_images: 20000
  images_per_class: 2000
  train_ratio: 0.8
  validation_ratio: 0.1
  test_ratio: 0.1

model:
  name: efficientnet_b0
  num_classes: 10
  pretrained: true
  input_size: 224

training:
  optimizer: AdamW
  batch_size: 16
  learning_rate: 0.001
  epochs: 15
  early_stopping_patience: 3

fine_tuning:
  learning_rate: 0.00001
  epochs: 20
  early_stopping_patience: 3
```

Nilai konfigurasi yang benar-benar digunakan pada eksperimen harus dicatat; contoh di atas adalah baseline dan bukan hasil eksperimen.

Minimal catat:

- Python version;
- PyTorch dan torchvision version;
- torch-directml version jika digunakan;
- OS dan GPU;
- dataset source dan keadaan/version dataset;
- seed;
- split ratio;
- label mapping;
- preprocessing;
- augmentation;
- model architecture dan pretrained weights;
- optimizer;
- learning rate;
- batch size;
- epoch dan early stopping;
- experiment ID;
- checkpoint;
- evaluation metrics.

Seed baseline:

```text
42
```

---

# 28. Observability, Logging, dan Error Handling

## 28.1 Laravel Logging

Log minimal:

- request ID;
- waktu request;
- status FastAPI;
- inference time dari response FastAPI;
- upload Storage success/failure;
- database insert success/failure;
- error validation.

Jangan log isi gambar, secret key, cookie value lengkap, atau kredensial.

## 28.2 FastAPI Logging

Log minimal:

- endpoint;
- status response;
- ukuran file;
- inference duration;
- error category.

Jangan menyimpan citra pengguna ke log.

## 28.3 Health Check

FastAPI sebaiknya menyediakan:

```text
GET /health
```

Response konseptual:

```json
{
  "status": "ok",
  "model_loaded": true
}
```

Endpoint health tidak melakukan inference terhadap file pengguna.

## 28.4 Timeout dan Retry

Laravel harus menangani:

- FastAPI timeout;
- FastAPI 4xx;
- FastAPI 5xx;
- Storage upload failure;
- Database failure.

Retry hanya diterapkan pada operasi yang aman untuk diulang. Upload/insert harus dirancang agar tidak menghasilkan duplikasi riwayat.

---

# 29. Security dan Data Integrity

## 29.1 Secret Management

- Jangan commit `.env`.
- Jangan menaruh service-role key Supabase di frontend.
- Gunakan environment variables.
- Jangan menaruh secret di Git.
- Rotasi key jika terjadi kebocoran.

## 29.2 File Upload Security

- Batasi extension dan MIME type.
- Batasi ukuran file.
- Generate nama object sendiri.
- Jangan menggunakan path dari pengguna.
- Validasi bahwa file benar-benar image.
- Jangan mengeksekusi file upload sebagai kode.

## 29.3 Session Security

Cookie `tomato_session_id` merupakan identifier pseudonim dan bukan kredensial autentikasi. Gunakan UUID acak, Secure/HttpOnly/SameSite sesuai environment, serta hindari memasukkan data sensitif ke cookie.

## 29.4 Database Integrity

Foreign key harus tetap aktif. Record riwayat hanya boleh dibuat setelah:

1. `session_id` valid;
2. `id_penyakit` ditemukan;
3. `id_model` ditemukan;
4. `citra_input` tersedia;
5. `confidence_score` berada pada rentang 0–1.

---

## 29.5 Risiko Teknis dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| VRAM 4 GB tidak cukup | Turunkan batch size 16 → 8 → 4 → 2 |
| DirectML tidak kompatibel | Uji mini-batch terlebih dahulu, sediakan CPU fallback |
| Dataset tidak seimbang | Dokumentasikan jumlah aktual dan gunakan sampling yang reproducible |
| Data rusak | Validasi file sebelum training |
| Data leakage | Stratified split dan pemeriksaan duplicate/near-duplicate |
| Overfitting | Augmentasi, validation monitoring, early stopping |
| Training terlalu lama | Transfer learning terlebih dahulu |
| Fine-tuning tidak stabil | Gunakan learning rate lebih kecil |
| Preprocessing tidak konsisten | Simpan konfigurasi preprocessing dan gunakan pipeline yang sama |
| Model sulit direproduksi | Simpan seed, konfigurasi, versi library, dan checkpoint |

---

# 30. Struktur Project End-to-End

Project dimulai dari repository kosong. Struktur berikut menjadi baseline repository awal. Agent boleh menambah file/folder internal yang diperlukan selama tidak mengubah batas tanggung jawab komponen atau contract PRD.

Struktur keseluruhan yang direkomendasikan:

```text
tomato-disease-system/
│
├── ml/
│   ├── dataset/
│   │   ├── raw/
│   │   ├── processed/
│   │   └── splits/
│   ├── src/
│   │   ├── config/
│   │   ├── data/
│   │   ├── preprocessing/
│   │   ├── models/
│   │   ├── training/
│   │   ├── evaluation/
│   │   └── inference/
│   ├── models/
│   │   ├── final_model.pt
│   │   ├── label_mapping.json
│   │   ├── preprocessing.json
│   │   └── training_config.yaml
│   ├── results/
│   ├── experiments/
│   ├── requirements.txt
│   └── README.md
│
├── fastapi/
│   ├── app/
│   │   ├── main.py
│   │   ├── routes/
│   │   ├── services/
│   │   ├── schemas/
│   │   └── core/
│   ├── models/
│   ├── requirements.txt
│   └── .env
│
└── laravel/
    ├── app/
    │   ├── Http/Controllers/
    │   ├── Http/Middleware/
    │   ├── Models/
    │   ├── Services/
    │   └── Http/Requests/
    ├── config/
    ├── database/
    │   └── migrations/
    ├── resources/views/
    ├── routes/
    └── .env
```

---

# 31. API dan Database Contract

## 31.1 Prediction Contract

```text
POST /predict
Content-Type: multipart/form-data

image: <binary>
```

Response:

```json
{
  "class": "Early Blight",
  "confidence": 0.93,
  "class_id": "Early Blight",
  "inference_time_ms": 142
}
```

## 31.2 Mapping Prediction ke Database

Laravel melakukan mapping:

```text
FastAPI.class
    ↓
penyakit.nama_penyakit
    ↓
penyakit.id_penyakit
```

Kemudian menyimpan:

```text
session_id
id_penyakit
id_model
citra_input
confidence_score
tanggal_deteksi
```

Jika `class` dari FastAPI tidak ditemukan pada master `penyakit`, Laravel harus menganggap response tidak konsisten dan tidak membuat record riwayat.

## 31.3 Active Model

Laravel MUST menentukan `id_model` berdasarkan model yang sedang digunakan oleh service FastAPI. Nilai tersebut harus konsisten dengan metadata deployment. Agent tidak boleh memilih `id_model` secara hard-coded jika hal tersebut dapat menyebabkan ketidaksesuaian dengan model deployment aktif.

---

# 32. Pengujian Integrasi

Pengujian minimum:

## 32.1 Machine Learning

- [ ] Model dapat di-load.
- [ ] Label mapping memiliki 10 kelas.
- [ ] Preprocessing inference sama dengan konfigurasi model.
- [ ] Inference satu gambar berhasil.

## 32.2 FastAPI

- [ ] `GET /health` berhasil.
- [ ] `POST /predict` dengan JPG berhasil.
- [ ] `POST /predict` dengan PNG berhasil.
- [ ] File kosong ditolak.
- [ ] Format tidak didukung ditolak.
- [ ] Response memiliki empat field kontrak.
- [ ] `inference_time_ms` tersedia.

## 32.3 Laravel

- [ ] Cookie baru dibuat untuk browser baru.
- [ ] UUID tersimpan pada `user_sessions`.
- [ ] Request berikutnya memakai UUID yang sama.
- [ ] `last_accessed_at` diperbarui.
- [ ] Upload gambar tervalidasi.
- [ ] Laravel dapat memanggil FastAPI.
- [ ] Public URL Storage diperoleh.
- [ ] Riwayat tersimpan.
- [ ] Riwayat hanya menampilkan session aktif.

## 32.4 Supabase

- [ ] Tabel berhasil dibuat.
- [ ] 10 kelas penyakit tersedia.
- [ ] Model aktif tersedia.
- [ ] Bucket `detection-images` tersedia dan public.
- [ ] Upload menghasilkan Public URL.
- [ ] Foreign key bekerja.
- [ ] Confidence constraint bekerja.

## 32.5 End-to-End

Skenario utama:

```text
Browser baru
  ↓
GET /detect
  ↓
Cookie dibuat
  ↓
POST /detect
  ↓
FastAPI prediction
  ↓
Storage upload
  ↓
Database insert
  ↓
Result displayed
  ↓
GET /history
  ↓
Record tampil
```

Skenario isolasi:

```text
Browser A → history A
Browser B → history B
```

Browser B tidak boleh menerima record milik Browser A hanya karena mengetahui ID record.

---

# 33. Checklist Implementasi End-to-End

Gunakan checklist ini sebagai satu-satunya checklist implementasi. Jangan membuat checklist paralel dengan nama atau isi yang sama.

## Tahap 1 — Environment

- [ ] Install Python 3.10.
- [ ] Buat virtual environment.
- [ ] Install PyTorch.
- [ ] Install torchvision.
- [ ] Install torch-directml.
- [ ] Test PyTorch.
- [ ] Test DirectML.
- [ ] Test GPU RX 6550M.
- [ ] Test mini-batch EfficientNetB0.
- [ ] Siapkan CPU fallback.
- [ ] Simpan requirements.

## Tahap 2 — Dataset

- [ ] Download dataset Kaggle.
- [ ] Periksa struktur folder.
- [ ] Verifikasi 10 kelas.
- [ ] Hitung jumlah gambar per kelas.
- [ ] Periksa file rusak dan format gambar.
- [ ] Periksa duplicate/near-duplicate.
- [ ] Bentuk working dataset.
- [ ] Target 2.000 gambar per kelas jika tersedia.
- [ ] Dokumentasikan jumlah aktual bila target tidak dapat dipenuhi.
- [ ] Buat split 80/10/10 secara stratified.
- [ ] Pastikan tidak ada leakage.
- [ ] Simpan manifest.

## Tahap 3 — Preprocessing dan Augmentation

- [ ] RGB conversion.
- [ ] Resize 224×224.
- [ ] Normalization sesuai pretrained weights.
- [ ] Pipeline train/validation/test/inference konsisten.
- [ ] Horizontal flip.
- [ ] Light rotation.
- [ ] Random resized crop.
- [ ] Light brightness/contrast.
- [ ] Pastikan augmentation hanya pada training.

## Tahap 4 — Model dan Training

- [ ] Load EfficientNetB0 pretrained.
- [ ] Ganti classifier menjadi 10 kelas.
- [ ] Siapkan CrossEntropyLoss.
- [ ] Siapkan AdamW.
- [ ] Siapkan checkpoint dan early stopping.
- [ ] Jalankan E01 transfer learning.
- [ ] Simpan training history dan best checkpoint.
- [ ] Jalankan E02 fine-tuning dari best E01.
- [ ] Simpan konfigurasi dan experiment ID.

## Tahap 5 — Evaluation dan Final Model

- [ ] Pilih model final berdasarkan validation set.
- [ ] Jalankan test evaluation satu kali setelah model final dipilih.
- [ ] Hitung accuracy, precision, recall, F1-score.
- [ ] Buat confusion matrix dan classification report.
- [ ] Simpan per-class metrics.
- [ ] Simpan `final_model.pt`.
- [ ] Simpan label mapping.
- [ ] Simpan preprocessing config.
- [ ] Simpan training config.
- [ ] Test reload model.

## Tahap 6 — FastAPI

- [ ] Buat FastAPI project.
- [ ] Load final model saat startup.
- [ ] Buat `POST /predict`.
- [ ] Validasi upload.
- [ ] Terima `multipart/form-data` field `image`.
- [ ] Jalankan inference.
- [ ] Return empat field response sesuai kontrak.
- [ ] Implement error handling.
- [ ] Implement health/readiness check.
- [ ] Test API.

## Tahap 7 — Supabase Database

- [ ] Buat project Supabase.
- [ ] Jalankan DDL `penyakit`.
- [ ] Jalankan DDL `model_cnn`.
- [ ] Jalankan DDL `user_sessions`.
- [ ] Jalankan DDL `riwayat_deteksi`.
- [ ] Pastikan foreign key dan index tersedia.
- [ ] Seed 10 kelas.
- [ ] Insert metadata model aktif.

## Tahap 8 — Supabase Storage

- [ ] Buat bucket `detection-images`.
- [ ] Set bucket public sesuai PRD.
- [ ] Uji upload object.
- [ ] Uji Public URL.
- [ ] Uji path `{session_id}/{uuid}.{ext}`.
- [ ] Pastikan file upload tervalidasi.

## Tahap 9 — Laravel Session

- [ ] Implement `EnsureTomatoSession`.
- [ ] Generate UUID v4 jika cookie belum ada.
- [ ] Set cookie `tomato_session_id`.
- [ ] Insert/update `user_sessions`.
- [ ] Update `last_accessed_at`.
- [ ] Lindungi route history berdasarkan session.

## Tahap 10 — Laravel ↔ FastAPI

- [ ] Set `FASTAPI_URL`.
- [ ] Implement HTTP client.
- [ ] Kirim multipart field `image`.
- [ ] Parse `class`, `confidence`, `class_id`, `inference_time_ms`.
- [ ] Implement timeout dan error handling.
- [ ] Test `/predict`.

## Tahap 11 — Laravel ↔ Supabase

- [ ] Set `SUPABASE_URL`.
- [ ] Set `SUPABASE_ANON_KEY`.
- [ ] Implement Storage upload.
- [ ] Ambil Public URL.
- [ ] Lookup `id_penyakit`.
- [ ] Lookup `id_model`.
- [ ] Insert `riwayat_deteksi`.
- [ ] Tampilkan hasil diagnosis.

## Tahap 12 — Dokumentasi dan Reproducibility

- [ ] README.
- [ ] Requirements.
- [ ] Dataset source dan preparation.
- [ ] Model configuration.
- [ ] Experiment E01 dan E02.
- [ ] Evaluation results.
- [ ] Confusion matrix.
- [ ] Model final.
- [ ] Cara menjalankan inference.
- [ ] Cara menjalankan FastAPI.
- [ ] Catat environment, seed, konfigurasi, dan versi library.

## Tahap 13 — End-to-End

- [ ] Browser baru menghasilkan session.
- [ ] Deteksi berhasil.
- [ ] Citra tersimpan.
- [ ] Public URL tersimpan.
- [ ] Riwayat tersimpan.
- [ ] Riwayat dapat ditampilkan.
- [ ] Browser/session lain tidak melihat riwayat session pertama.
- [ ] Error path diuji.
- [ ] Logging diperiksa.
- [ ] Alur browser → Laravel → FastAPI → Storage → Database → UI berhasil.

## 33.0 Format Task untuk AI Agent

Setiap task implementasi yang diturunkan dari checklist harus diperlakukan dengan format berikut. Agent boleh membagi task menjadi subtask yang lebih kecil, tetapi tidak boleh menghilangkan acceptance criteria phase.

```text
TASK ID:
PHASE:
TITLE:
PURPOSE:
PREREQUISITES:
INPUTS:
FILES / COMPONENTS AFFECTED:
REQUIRED CHANGES:
ACCEPTANCE CRITERIA:
VALIDATION COMMANDS / TESTS:
EXPECTED RESULT:
STATUS:
BLOCKERS:
DECISIONS REQUIRED:
ARTIFACTS PRODUCED:
```

### Aturan Task

- Task harus mempunyai tujuan yang jelas dan dapat diverifikasi.
- Task tidak boleh mengubah contract lintas komponen tanpa mencatat dampaknya.
- Jika task menghasilkan artifact yang dibutuhkan phase berikutnya, artifact tersebut harus divalidasi sebelum phase berikutnya dimulai.
- Agent harus menghindari task yang terlalu besar sehingga tidak dapat diverifikasi secara jelas.
- Agent tidak perlu membuat file dokumentasi task terpisah jika informasi tersebut sudah tercatat pada commit/log/runbook proyek; yang penting hasil dan status dapat ditelusuri.

## 33.1 Urutan Implementasi

Urutan implementasi yang harus diikuti:

```text
Environment
    ↓
Test PyTorch
    ↓
Test DirectML / CPU fallback
    ↓
Test EfficientNetB0 Mini-Batch
    ↓
Dataset Inspection
    ↓
Dataset Cleaning
    ↓
Dataset Sampling
    ↓
Train/Validation/Test Split
    ↓
Preprocessing
    ↓
Augmentation
    ↓
E01 Transfer Learning
    ↓
Validation Evaluation
    ↓
E02 Fine-Tuning
    ↓
Model Selection
    ↓
Final Test Evaluation
    ↓
Save Final Model
    ↓
Inference Test
    ↓
FastAPI
    ↓
Supabase
    ↓
Laravel Session
    ↓
Laravel Integration
    ↓
End-to-End Test
```

# 33.2 Phase Gate dan Acceptance Criteria untuk AI Agent

Bagian ini menerjemahkan checklist pada Bab 33 menjadi aturan berhenti/lanjut untuk AI agent. Agent tidak boleh menganggap sebuah phase selesai hanya karena seluruh kode atau file telah dibuat.

| Phase | Prasyarat minimum | Exit Criteria / Acceptance Criteria |
|---|---|---|
| 1. Environment | Repository baru | Python/PyTorch/torchvision tersedia; DirectML diuji; CPU fallback tersedia; environment dapat direproduksi |
| 2. Dataset | Environment valid | 10 kelas diverifikasi; working dataset terdokumentasi; cleaning selesai; split 80/10/10 valid; tidak ada leakage; manifest tersimpan |
| 3. Preprocessing & Augmentation | Split valid | RGB, 224×224, normalization, augmentation train-only, dan inference preprocessing konsisten |
| 4. Model & Training | Dataset + preprocessing valid | EfficientNetB0 berjalan; E01 dan E02 dijalankan atau kegagalannya terdokumentasi; checkpoint/history/config tersimpan |
| 5. Evaluation & Final Model | E01/E02 tersedia | Model final dipilih dari validation; test hanya digunakan setelah pemilihan; seluruh metrik dan artifact final tersedia; reload/inference berhasil |
| 6. FastAPI | Final model valid | `/predict` dan `/health` berjalan; contract response benar; model load saat startup; error handling diuji |
| 7. Supabase Database | Schema ditetapkan | Empat tabel, constraint/index, seed penyakit, dan metadata model aktif tersedia serta dapat diverifikasi |
| 8. Supabase Storage | Database dasar valid | Bucket `detection-images` tersedia; upload dan Public URL berhasil; object path sesuai contract |
| 9. Laravel Session | Supabase dasar valid | Cookie `tomato_session_id`, UUID v4, `user_sessions`, dan session isolation bekerja |
| 10. Laravel ↔ FastAPI | FastAPI valid + session valid | Laravel berhasil mengirim multipart image dan memproses response/error FastAPI sesuai contract |
| 11. Laravel ↔ Supabase | Storage + DB valid | Upload image → Public URL → lookup disease/model → insert history berjalan sesuai urutan wajib |
| 12. Documentation & Reproducibility | Artifact utama tersedia | README, requirements, konfigurasi, experiment record, evaluation result, dan runbook tersedia |
| 13. End-to-End | Semua phase sebelumnya valid | Browser → Laravel → FastAPI → Storage → Database → UI berhasil; session isolation dan error path tervalidasi |

### Aturan Phase Gate

1. **PASS**: seluruh exit criteria terpenuhi; phase boleh ditutup.
2. **PARTIALLY VALIDATED**: implementasi ada tetapi sebagian validasi belum dapat dijalankan; phase belum dianggap selesai.
3. **BLOCKED**: ada dependency, informasi, credential, atau keputusan Level 3 yang belum tersedia; agent harus berhenti pada bagian yang terdampak.
4. **FAIL**: validasi dijalankan dan hasilnya gagal; agent harus memperbaiki implementasi atau meminta keputusan jika perbaikannya memerlukan perubahan contract.

### Format Catatan Phase

Untuk setiap phase yang dikerjakan agent, catat minimal:

```text
Phase:
Status: PASS / PARTIALLY VALIDATED / BLOCKED / FAIL
Implemented:
Validation:
Result:
Known Issues:
Decisions Required:
Artifacts:
```

# 34. Definition of Done End-to-End

Sistem dianggap selesai secara teknis apabila seluruh kondisi berikut terpenuhi:

### Machine Learning

- Dataset 10 kelas telah diverifikasi.
- Working dataset dan split telah terdokumentasi.
- Train, validation, dan test tidak mengalami leakage.
- Preprocessing dan augmentation telah ditetapkan.
- EfficientNetB0 berhasil dijalankan.
- E01 transfer learning berhasil atau kegagalannya terdokumentasi.
- E02 fine-tuning berhasil atau kegagalannya terdokumentasi.
- Model final dipilih berdasarkan validation set.
- Test set digunakan hanya untuk evaluasi final.
- Confusion matrix, classification report, dan per-class metrics tersedia.
- Model final dapat di-load kembali.
- Inference terhadap gambar baru berhasil.
- Confidence score tersedia.

### FastAPI

- `POST /predict` tersedia.
- Request `multipart/form-data` dengan field `image` berjalan.
- Response memiliki `class`, `confidence`, `class_id`, `inference_time_ms`.
- Model dimuat saat startup.
- Health/readiness check tersedia.
- Error handling API diuji.

### Laravel

- Middleware `tomato_session_id` bekerja tanpa login.
- `user_sessions` dibuat/diperbarui.
- Laravel dapat memanggil FastAPI.
- Laravel dapat mengunggah citra ke Supabase Storage.
- Laravel mendapatkan Public URL.
- Laravel dapat menyimpan riwayat ke database.
- Riwayat difilter berdasarkan session ID browser.

### Supabase

- DDL empat tabel berhasil diterapkan.
- Master 10 penyakit tersedia.
- Metadata model tersedia.
- Bucket `detection-images` tersedia.
- Setiap deteksi valid menghasilkan record `riwayat_deteksi`.
- Foreign key dan integrity constraints berjalan.

### End-to-End

- Alur browser → Laravel → FastAPI → Storage → Database → UI berhasil.
- Session berbeda tidak dapat melihat riwayat session lain.
- Error path dan logging diuji.
- Environment dan konfigurasi dapat direproduksi.
- Dokumentasi implementasi tersedia.

# 35. Deliverables End-to-End

Output akhir proyek:

```text
1. Dataset manifest
2. Dataset split
3. Source code training
4. Source code evaluation
5. Source code inference
6. E01 checkpoint
7. E02 checkpoint
8. Final model
9. Label mapping
10. Preprocessing configuration
11. Training configuration
12. Training history
13. Confusion matrix
14. Classification report
15. Per-class metrics
16. FastAPI service
17. FastAPI API contract
18. Laravel web application
19. Laravel session middleware
20. Laravel ↔ FastAPI integration
21. Supabase PostgreSQL DDL
22. Supabase seed data
23. Supabase Storage bucket configuration
24. Database/storage integration
25. `.env.example` untuk Laravel/FastAPI
26. Integration test record
27. Requirements file
28. README/documentation
29. Experiment records
30. End-to-end deployment/runbook
```

# 36. Catatan Arsitektur Final

Catatan pada bagian ini merupakan batas arsitektur final. AI agent tidak boleh mengganti atau menambahkan komponen utama tanpa persetujuan pengguna.

1. **20.000 gambar** adalah working dataset yang dibentuk melalui sampling/stratified subset dari open dataset Kaggle *Tomato Leaf Disease Image Classification* oleh cyrilrajc; bukan klaim bahwa dataset mentah pasti berisi tepat 20.000 gambar siap pakai.
2. Gambar hasil augmentation tidak dihitung sebagai gambar sumber tambahan.
3. Jika kelas memiliki kurang dari target 2.000 gambar setelah pemeriksaan dan pembersihan, jumlah aktual harus didokumentasikan; jangan membuat jumlah dengan duplikasi persis.
4. Model ML menggunakan EfficientNetB0 dengan transfer learning dan fine-tuning.
5. Training menggunakan Windows 11, PyTorch, dan AMD Radeon RX 6550M; DirectML diuji sebagai akselerator dan CPU menjadi fallback.
6. FastAPI adalah service inference, bukan database dan bukan application UI.
7. Laravel adalah application layer untuk web UI, session-cookie, pemanggilan FastAPI, Supabase Storage, dan Supabase Database.
8. Sistem riwayat tidak memerlukan registrasi/login; identitas riwayat menggunakan UUID pada cookie `tomato_session_id`.
9. `session_id` harus selalu berasal dari middleware/server context ketika menyimpan dan membaca riwayat.
10. Citra input disimpan pada bucket public `detection-images`; Public URL disimpan pada `riwayat_deteksi.citra_input`.
11. `confidence_score` disimpan sebagai probabilitas 0–1 dan bukan jaminan kebenaran prediksi.
12. `id_penyakit` berasal dari master `penyakit`; mapping class model tetap berasal dari label mapping model.
13. `id_model` merepresentasikan model CNN yang menghasilkan prediksi.
14. Test set hanya digunakan untuk evaluasi final, bukan pemilihan hyperparameter atau checkpoint.
15. Nilai performa akhir harus berasal dari eksperimen, bukan ditetapkan sebelum eksperimen.
16. Batch size 16 adalah baseline dan dapat diturunkan bila VRAM tidak mencukupi.
17. DirectML harus diuji dengan mini-batch sebelum training penuh.
18. Secret dan service-role credentials tidak boleh dikirim ke browser atau disimpan di repository.
19. Bucket public memudahkan Public URL, tetapi tidak menggantikan validasi upload dan kontrol akses pada application layer.
20. Laravel bukan lingkungan training Machine Learning.
21. Graphify, apabila digunakan, hanya alat bantu navigasi/pengelolaan konteks codebase dan bukan komponen Machine Learning.
22. Sistem ini merupakan klasifikasi citra dan bukan pengganti diagnosis atau rekomendasi agronomis profesional.

# 37. Referensi Sumber Data dan Dokumen Proyek
Sumber dataset utama:

```text
Kaggle — Tomato Leaf Disease Image Classification
Author: cyrilrajc
https://www.kaggle.com/datasets/cyrilrajc/tomato-leaf-disease-image-classification
```

Dokumen Capstone yang menjadi konteks penelitian menetapkan fokus pada klasifikasi penyakit daun tomat menggunakan CNN/EfficientNet, dataset Kaggle tersebut, preprocessing/augmentasi, pembagian training-validation-testing, serta evaluasi model. Terminologi teknis pada PRD dipertahankan agar konsisten dengan dokumen penelitian.

**Status PRD:** Final / Agent-Ready / Siap Implementasi End-to-End.

**Catatan versi 3.0:** Perubahan utama pada versi ini adalah penguatan protokol AI agent, kewenangan agent, handling ambiguity, change control, validation state, phase gate, task format, dan konsistensi checklist. Requirement teknis inti dari versi sebelumnya dipertahankan.

---

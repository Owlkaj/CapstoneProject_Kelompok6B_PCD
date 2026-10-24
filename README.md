# 🍃 TomatCare — Sistem Klasifikasi & Diagnosis Penyakit Daun Tomat End-to-End

<div align="center">

![Python](https://img.shields.io/badge/Python-3.10%2B-blue?logo=python&logoColor=white)
![PyTorch](https://img.shields.io/badge/PyTorch-2.4%2B-EE4C2C?logo=pytorch&logoColor=white)
![FastAPI](https://img.shields.io/badge/FastAPI-0.115%2B-009688?logo=fastapi&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-v4-38B2AC?logo=tailwind-css&logoColor=white)
![Supabase](https://img.shields.io/badge/Supabase-PostgreSQL%20%26%20Storage-3ECF8E?logo=supabase&logoColor=white)
![License](https://img.shields.io/badge/License-Academic%20%2F%20Capstone-emerald)

**Platform terpadu berbasis Computer Vision & Deep Learning (*EfficientNet-B0*) untuk deteksi dini patologi daun tomat, dilengkapi mitigasi *domain shift*, *calibrated confidence gating*, dan antarmuka web modern responsif.**

[Fitur Utama](#-fitur-utama--inovasi-sistem) • [Arsitektur](#-arsitektur-sistem-end-to-end) • [Metrik Evaluasi](#-evaluasi--benchmark-performa) • [10 Kelas Patologi](#-10-kelas-patologi-daun-tomat) • [Instalasi](#-panduan-instalasi--menjalankan-sistem) • [Spesifikasi API](#-spesifikasi-rest-api-fastapi) • [Pengujian](#-pengujian--validasi-sistem)

</div>

---

## 📌 Ringkasan Eksekutif

**TomatCare** adalah sistem klasifikasi patologi daun tomat berbasis citra digital yang dirancang untuk mengatasi kesenjangan antara akurasi model di lingkungan laboratorium (*clean dataset*) dan performa nyata di lahan pertanian (*in-the-field conditions*). Sistem ini mengintegrasikan:
1. **Model Klasifikasi Deep Learning**: EfficientNet-B0 yang dilatih dengan strategi *transfer learning*, adaptasi domain citra lapangan, serta prapemrosesan segmentasi adaptif.
2. **Inference Microservice (FastAPI)**: Menyediakan komputasi inferensi ultra-cepat (~22–40 ms) dengan dukungan *Adaptive Leaf Preprocessing* dan *Multi-signal Confidence Gating*.
3. **Web Application (Laravel 12 + Tailwind CSS)**: Antarmuka ramah pengguna bagi petani/penyuluh pertanian, dilengkapi katalog penyakit agronomis terperinci, pelacakan riwayat berbasis sesi pseudonim (*zero-login*), dan dashboard evaluasi model interaktif.
4. **Cloud Persistence (Supabase)**: Penyimpanan citra terdistribusi pada Supabase Object Storage serta pencatatan metadata relasional pada PostgreSQL.

---

## ✨ Fitur Utama & Inovasi Sistem

### 1. 🧠 Transfer Learning & Fine-Tuning EfficientNet-B0
* Bobot dasar pretrained *ImageNet-1k* dengan *fine-tuning* terkalibrasi pada lapisan atas (*unfrozen top stages*).
* Dilatih pada kombinasi dataset laboratorium (PlantVillage) dan dataset lapangan (*YOLO-cropped field leaves*) untuk memperkaya variasi latar belakang dan sudut pencahayaan.

### 2. 🍃 Adaptive Leaf Isolation Preprocessing
Mengatasi kendala *domain shift* (latar tanah, bayangan, atau dedaunan lain) melalui pipeline isolasi daun bertingkat:
* **Multi-Strategy Segmentation**: Deteksi otomatis ketersediaan `rembg` (U²-Net), OpenCV GrabCut, atau *fallback* algoritma NumPy/SciPy (*Excess-Green Index* + Otsu thresholding + largest connected component).
* **Adaptive Gate (Konservatif)**: Memeriksa variansi tekstur dan saturasi latar belakang. Prapemrosesan hanya aktif jika citra terdeteksi sebagai foto lapangan, dan melakukan *bypass* jika citra sudah berlatar polos netral guna mencegah degradasi citra bersih.
* **Compositing & Normalisasi**: Menyelaraskan ukuran daun ke ~75% kanvas persegi serta menormalkan pencahayaan (*Gray-World White Balance* dan CLAHE).

### 3. 🛡️ Multi-Signal Calibrated Confidence Gating
Mencegah fenomena *closed-set overconfidence* (model menjawab yakin padahal citra bukan daun tomat atau buram):
* Mengevaluasi kombinasi 5 sinyal kualitas: **Confidence Score**, **Prediction Margin** ($Top_1 - Top_2$), **Laplacian Sharpness** (keburaman), **Luminance** (kecerahan/kegelapan), dan **Minimum Resolution**.
* Mengklasifikasikan keputusan menjadi:
  * `accept`: Hasil kredibel dan dapat diandalkan langsung sebagai diagnosis.
  * `review`: Tingkat kepastian borderline; diagnosis ditampilkan disertai anjuran pengambilan foto ulang.
  * `reject`: Kualitas citra di bawah ambang batas; sistem menolak menampilkan diagnosis keliru dan memandu pengguna memperbaiki sudut pemotretan.

### 4. 🔒 Zero-Login Pseudonymous Session Tracking
* Pengguna tidak diwajibkan melakukan registrasi atau login untuk menjaga alur kerja di lapangan tetap praktis.
* Riwayat deteksi diisolasi secara aman menggunakan cookie aman `tomato_session_id` (standar UUID v4) yang tersinkronisasi ke tabel `user_sessions` dan `riwayat_deteksi`.

### 5. 📊 Dashboard Evaluasi Model Interaktif
* Halaman evaluasi mandiri ([evaluation_dashboard.html](ml/results/evaluation_dashboard.html)) dengan grafik reaktif Chart.js.
* Menampilkan matriks konfusi resolusi tinggi, perbandingan metrik Lab vs Field, analisis bias kelas, serta navigasi ramah aksesibilitas (WAI-ARIA & navigasi keyboard).

---

## 🏛️ Arsitektur Sistem End-to-End

```text
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           KLIEN (Browser Petani / Pengguna)                     │
└──────────────────────────────────────┬──────────────────────────────────────────┘
                                       │ 1. Unggah Citra Daun (Kamera / File)
                                       │    + Cookie 'tomato_session_id'
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                      LARAVEL 12 APPLICATION LAYER (Port 8000)                   │
├─────────────────────────────────────────────────────────────────────────────────┤
│  • EnsureTomatoSession Middleware  ──> Validasi / Terbitkan UUID v4             │
│  • File Validation                ──> Format JPG/PNG/JPEG, Maks. 10 MB          │
│  • DiseaseCatalog Helper          ──> Deskripsi patologi, patogen & penanganan  │
└───────────────────┬─────────────────────────────────────────▲───────────────────┘
                    │ 2. Multipart POST /predict              │ 5. Response JSON
                    ▼                                         │
┌─────────────────────────────────────────────────────────────┴───────────────────┐
│                      FASTAPI INFERENCE SERVICE (Port 8001)                      │
├─────────────────────────────────────────────────────────────────────────────────┤
│  • Leaf Isolation Preprocessor     ──> Adaptive segmentasi, crop & white-balance│
│  • Image Preprocessing            ──> Resize 224x224 & ImageNet Normalization   │
│  • EfficientNet-B0 Model          ──> PyTorch GPU (DirectML / CUDA) atau CPU    │
│  • Calibrated Confidence Gating   ──> Multi-signal filtering (accept/review)    │
└─────────────────────────────────────────────────────────────────────────────────┘
                    │
                    │ 3. Upload citra input asli
                    ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         SUPABASE CLOUD PERSISTENCE                              │
├─────────────────────────────────────────────────────────────────────────────────┤
│  • Object Storage (`detection-images`) ──> Simpan file & terbitkan Public URL   │
│  • PostgreSQL Relational Tables:                                                │
│      - `user_sessions`   : Pencatatan waktu akses sesi                          │
│      - `model_cnn`       : Katalog bobot & metadata model aktif                 │
│      - `penyakit`        : Master referensi 10 kelas penyakit                   │
│      - `riwayat_deteksi` : Relasi hasil prediksi, confidence, sesi & public URL │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🌿 10 Kelas Patologi Daun Tomat

Sistem mengklasifikasikan 10 kelas kondisi daun tomat (9 patologi dan 1 kondisi sehat):

| # | Kelas Teknis (*Model Output*) | Nama Indonesia | Patogen / Agen Penyebab | Karakteristik Visual Utama |
|:---:|---|---|---|---|
| **0** | `Bacterial Spot` | **Bercak Bakteri** | Bakteri *Xanthomonas campestris* | Lesi basah kecil berwarna gelap dengan lingkaran kuning tipis (*halo*). |
| **1** | `Early Blight` | **Bercak Kering** | Jamur *Alternaria solani* | Bercak cokelat-hitam konsentris menyerupai papan sasaran (*concentric rings*). |
| **2** | `Healthy` | **Daun Sehat** | *(Kondisi Optimal)* | Daun berwarna hijau segar merata, turgor baik, bebas bercak atau nekrosis. |
| **3** | `Late Blight` | **Hawar Daun** | Oomycete *Phytophthora infestans* | Lesi luas kebasahan berwarna abu-abu kehijauan yang membusuk cepat saat lembap. |
| **4** | `Leaf Mold` | **Kapang Daun** | Jamur *Passalora fulva* | Bercak kuning pucat di permukaan atas disertai lapisan jamur beludru di bawahnya. |
| **5** | `Tomato Mosaic Virus` | **Virus Mosaik** | Virus *Tobacco/Tomato Mosaic Virus* | Pola belang hijau tua & hijau muda seperti mosaik dengan helai daun mengerut. |
| **6** | `Septoria Leaf Spot` | **Bercak Daun Septoria** | Jamur *Septoria lycopersici* | Bintik-bintik bundar kecil cokelat dengan titik pusat putih keabuan (piknidia). |
| **7** | `Spider Mites` | **Tungau Laba-Laba** | Hama *Tetranychus urticae* | Titik-titik kuning klorosis halus di permukaan daun, kadang berakar jaring laba-laba. |
| **8** | `Target Spot` | **Bercak Sasaran** | Jamur *Corynespora cassiicola* | Lesi melingkar cokelat bertingkat dengan batas tegas yang menyebar ke seluruh helai. |
| **9** | `Tomato Yellow Leaf Curl Virus` | **Virus Kuning Keriting** | Virus *Begomovirus* (vektor Kutu Kebul) | Daun menguning di tepi, mengecil, dan melengkung cembung ke atas (*cupping*). |

---

## 📈 Evaluasi & Benchmark Performa

Model telah dievaluasi secara komprehensif pada tiga kelompok dataset uji untuk mengukur generalisasi laboratorium maupun ketahanan lapangan:

### 1. Ringkasan Metrik Evaluasi

| Himpunan Data Uji (*Test Set*) | Karakteristik Citra | Jumlah Sampel | Akurasi | Macro F1-Score | Keterangan |
|---|---|:---:|:---:|:---:|---|
| **PlantVillage Test Set** | Foto laboratorium, latar belakang netral | 2.000 citra | **96.70%** | **96.70%** | Sangat tinggi, presisi 96.65% |
| **Field In-Pool Test Set** | Foto daun tomat lapangan (8 kelas) | 1.160 citra | **96.64%** | **96.48%** | Performa domain lapangan sangat stabil |
| **Cross-Dataset Field Test** | Dataset independen luar (3 kelas) | 653 citra | **83.31%** | **82.15%** | Generalisasi lintas sumber (+36.6% vs baseline) |

### 2. Efisiensi Komputasi
* **Ukuran Bobot Model**: ~16.3 MB (`final_model.pt`).
* **Latensi Inferensi**:
  * GPU DirectML / CUDA: **~22 ms** per citra.
  * CPU Standar: **~40–65 ms** per citra.
* **Throughput**: Mendukung pemrosesan real-time untuk deployment lokal maupun cloud.

---

## 📂 Struktur Direktori Repositori

```text
casptone/sistem/
├── ml/                                 # Pipeline Machine Learning & Eksperimen
│   ├── dataset/                        # Direktori dataset & manifest split
│   ├── models/                         # Bobot model terlatih & mapping kelas
│   ├── results/                        # Matriks konfusi, kurva training & dashboard HTML
│   │   ├── evaluation_dashboard.html   # Standalone interactive evaluation dashboard
│   │   └── confusion_matrix.png        # Visualisasi heatmap performa 10 kelas
│   ├── src/                            # Kode modular: dataset, model, training, evaluate
│   │   ├── model/efficientnet.py       # Definisi arsitektur EfficientNet-B0
│   │   └── evaluation/calibrate_gating.py # Skrip kalibrasi ambang confidence gating
│   └── requirements.txt                # Dependensi ML (PyTorch, torchvision, scipy, etc.)
│
├── fastapi/                            # REST API Microservice untuk Inferensi Model
│   ├── app/
│   │   ├── routes/predict.py           # Endpoint POST /predict & GET /health
│   │   ├── schemas/predict.py          # Kontrak request & response Pydantic
│   │   └── services/
│   │       ├── inference.py            # Engine inferensi model PyTorch
│   │       ├── leaf_preprocessing.py   # Adaptive leaf isolation & segmentasi
│   │       └── confidence_gating.py    # Logika penilaian kualitas citra & gating
│   ├── models/                         # Salinan bobot final_model.pt & label_mapping.json
│   ├── test_api.py                     # Integration & contract test suite
│   ├── test_leaf_preprocessing.py      # Unit test segmentasi daun
│   ├── test_confidence_gating.py       # Unit test validasi ambang gerbang
│   └── requirements.txt                # Dependensi backend FastAPI
│
├── laravel/                            # Web Application Utama (Laravel 12 + Tailwind)
│   ├── app/
│   │   ├── Helpers/DiseaseCatalog.php  # Katalog agronomis 10 penyakit tomat
│   │   ├── Http/Controllers/
│   │   │   ├── DetectionController.php # Manajemen alur upload, inferensi & simpan
│   │   │   └── HistoryController.php   # Manajemen tampilan & penghapusan riwayat sesi
│   │   ├── Http/Middleware/
│   │   │   └── EnsureTomatoSession.php # Manajemen pseudonim cookie UUID v4
│   │   └── Services/
│   │       ├── FastApiService.php      # HTTP Client penghubung ke FastAPI
│   │       └── SupabaseService.php     # Client REST & Storage Supabase
│   ├── resources/views/
│   │   ├── components/                 # Komponen UI Blade rekayasa ulang
│   │   ├── detection/index.blade.php   # Halaman utama unggah citra & hasil diagnosis
│   │   ├── history/index.blade.php     # Halaman riwayat deteksi per sesi
│   │   └── about/index.blade.php       # Halaman edukasi patologi & panduan foto
│   ├── tests/Feature/                  # PHPUnit test suite (Feature & Unit)
│   └── routes/web.php                  # Web & API routes Laravel
│
├── start_public_demo.bat               # Skrip one-click aktivasi demo publik via Cloudflare Tunnel
├── stop_public_demo.bat                # Skrip terminasi seluruh service latar belakang
├── LAPORAN_ARSITEKTUR_DAN_SISTEM.md    # Laporan teknis detail arsitektur sistem
├── PERUBAHAN.md                        # Changelog implementasi fitur & mitigasi domain shift
├── RUNBOOK.md                          # Panduan operasional & pemeliharaan teknis
└── README.md                           # Dokumentasi utama proyek
```

---

## 🚀 Panduan Instalasi & Menjalankan Sistem

### Prasyarat Perangkat Lunak
* **Python**: Versi 3.10 atau lebih tinggi
* **PHP**: Versi 8.2 atau lebih tinggi (ekstensi aktif: `curl`, `mbstring`, `pdo_sqlite`, `openssl`, `fileinfo`)
* **Composer**: Versi 2.x
* **Node.js & NPM**: (Opsional jika ingin rebuild asset Tailwind)

---

### Langkah 1: Menjalankan Backend Inference (FastAPI)

1. Masuk ke direktori `fastapi/`:
   ```bash
   cd fastapi
   ```
2. Buat dan aktifkan *virtual environment*:
   ```bash
   python -m venv .venv
   # Windows (PowerShell):
   .venv\Scripts\Activate.ps1
   # Linux / macOS:
   source .venv/bin/activate
   ```
3. Pasang seluruh dependensi:
   ```bash
   pip install -r requirements.txt
   ```
4. Salin file environment:
   ```bash
   copy .env.example .env    # Windows
   # cp .env.example .env    # Linux / macOS
   ```
5. Jalankan server FastAPI:
   ```bash
   python -m uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
   ```
   *Verifikasi*: Akses `http://127.0.0.1:8001/health` di browser. Hasil harus menampilkan `{"status":"ok","model_loaded":true}`.

---

### Langkah 2: Menjalankan Web Application (Laravel 12)

1. Buka terminal baru dan masuk ke direktori `laravel/`:
   ```bash
   cd laravel
   ```
2. Pasang dependensi PHP via Composer:
   ```bash
   composer install
   ```
3. Salin file environment dan generate application key:
   ```bash
   copy .env.example .env
   php artisan key:generate
   ```
4. Sesuaikan konfigurasi pada `laravel/.env`:
   ```env
   FASTAPI_URL=http://127.0.0.1:8001
   SUPABASE_URL=https://<your-project-id>.supabase.co
   SUPABASE_ANON_KEY=<your-anon-key>
   SUPABASE_STORAGE_BUCKET=detection-images
   ```
5. Jalankan migrasi database lokal (SQLite default):
   ```bash
   php artisan migrate
   ```
6. Jalankan server Laravel:
   ```bash
   php artisan serve --port 8000
   ```
   *Akses Aplikasi*: Buka **`http://localhost:8000`** melalui browser Anda.

---

### Langkah 3: Menjalankan Demo Publik (Opsional)

Jika ingin membagikan demo ke perangkat seluler atau pengguna luar tanpa port-forwarding rumit:
```cmd
# Jalankan skrip batch di root folder:
start_public_demo.bat
```
Skrip ini akan menyalakan FastAPI, Laravel, dan terowongan aman **Cloudflare Tunnel**, lalu menampilkan URL publik HTTPS di terminal. Untuk menghentikan semua layanan:
```cmd
stop_public_demo.bat
```

---

## 📡 Spesifikasi REST API (FastAPI)

### 1. `GET /health`
Memeriksa kesiapan service dan ketersediaan bobot model di memori.
* **Response `200 OK`**:
  ```json
  {
    "status": "ok",
    "model_loaded": true
  }
  ```

### 2. `POST /predict`
Mengunggah citra daun untuk proses inferensi klasifikasi patologi.
* **Parameter Query (Opsional)**:
  * `preprocess`: `adaptive` (default) | `true` (paksa segmentasi) | `false` (lewati segmentasi)
  * `gate`: `true` (default) | `false` (nonaktifkan pemeriksaan kualitas citra)
* **Body Form-Data**:
  * `image`: File citra biner (format `.jpg`, `.jpeg`, `.png`, maks. 10 MB)
* **Response `200 OK`**:
  ```json
  {
    "class": "Early Blight",
    "confidence": 0.9412,
    "class_id": 1,
    "inference_time_ms": 28.45,
    "preprocessing_applied": false,
    "preprocessing_method": "none",
    "top_predictions": [
      { "class": "Early Blight", "class_id": 1, "confidence": 0.9412 },
      { "class": "Target Spot", "class_id": 8, "confidence": 0.0381 },
      { "class": "Late Blight", "class_id": 3, "confidence": 0.0125 }
    ],
    "confidence_gate": {
      "passed": true,
      "action": "accept",
      "threshold": 0.55,
      "quality": {
        "sharpness": 1240.5,
        "brightness": 128.2,
        "min_side": 800
      },
      "reasons": []
    }
  }
  ```

---

## 🧪 Pengujian & Validasi Sistem

Proyek ini menerapkan *automated testing* ketat di setiap layer:

```bash
# 1. Menguji Test Suite Lengkap Laravel (Unit & Feature Test)
cd laravel
php artisan test

# 2. Menguji Kontrak API & Sampling 10 Kelas Nyata FastAPI
cd fastapi
python test_api.py

# 3. Menguji Pipeline Segmentasi Daun (Unit Test Preprocessing)
cd fastapi
python test_leaf_preprocessing.py

# 4. Menguji Ambang Batas Multi-Signal Confidence Gating
cd fastapi
python test_confidence_gating.py

# 5. Menguji Konsistensi Cookie & Pseudonim Session
php laravel/test_session.php
```

---

## 📚 Dokumen Pendukung & Referensi Teknis

Untuk pemahaman arsitektural yang lebih mendalam, silakan baca dokumentasi pendukung berikut:
* 📄 [**LAPORAN_ARSITEKTUR_DAN_SISTEM.md**](LAPORAN_ARSITEKTUR_DAN_SISTEM.md): Penjelasan komprehensif seluruh komponen sistem, arsitektur data relasional, dan panduan akademik.
* 📄 [**PERUBAHAN.md**](PERUBAHAN.md): Dokumentasi historis mitigasi *domain shift*, kalibrasi ambang gating, dan eksperimen lapangan.
* 📄 [**RUNBOOK.md**](RUNBOOK.md): Panduan operasional langkah demi langkah untuk deployment dan pemeliharaan server produksi.
* 📊 [**Dashboard Evaluasi Model**](ml/results/evaluation_dashboard.html): Tampilan visual interaktif evaluasi model, matriks konfusi, dan metrik presisi-recall.

---

## ⚖️ Lisensi & Batasan Tanggung Jawab (*Disclaimer*)

Sistem **TomatCare** dikembangkan sebagai purwarupa penelitian berbasis kecerdasan buatan untuk identifikasi visual awal penyakit tanaman tomat. 

> [!IMPORTANT]
> Hasil klasifikasi yang dihasilkan oleh sistem ini adalah perkiraan probabilitas berbasis visi komputer dan **bukan merupakan pengganti diagnosis laboratorium agronomis atau nasihat ahli patologi tanaman bersertifikasi secara mutlak**. Pengguna disarankan tetap memverifikasi kondisi lapangan secara holistik sebelum mengambil tindakan kimiawi berskala besar.

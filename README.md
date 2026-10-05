# 🍃 TomatCare — Sistem Klasifikasi Penyakit Daun Tomat End-to-End

Aplikasi klasifikasi dan diagnosis penyakit daun tomat berbasis visi komputer (*Computer Vision*) dan Deep Learning (*EfficientNet-B0*) yang diintegrasikan secara *end-to-end* dengan microservice REST API (FastAPI), web application modern (Laravel 12 + Tailwind CSS), serta database relasional & object storage (Supabase).

---

## 📌 Ringkasan Proyek

| Parameter | Spesifikasi |
|---|---|
| **Model Deep Learning** | EfficientNet-B0 (Transfer Learning + Fine-Tuning 2 Stage Teratas) |
| **Framework ML** | PyTorch 2.4.1 + torchvision 0.19.1 |
| **Dataset Training** | 20.000 citra seimbang (2.000 citra per kelas, 10 kelas) |
| **Split Dataset** | 80% Train (16.000) • 10% Val (2.000) • 10% Test (2.000) |
| **Akurasi Evaluasi Test** | **96.60%** (Macro F1-Score: 96.60%, Precision: 96.65%, Recall: 96.60%) |
| **Kecepatan Inferensi** | ~22 – 40 ms per citra |
| **Backend Inference** | FastAPI (Python 3.10) pada port `8001` |
| **Application Layer** | Laravel 12 (PHP 8.2+) pada port `8000` |
| **Database & Storage** | Supabase PostgreSQL (4 tabel) & Supabase Storage (`detection-images`) |
| **Sistem Autentikasi** | Tanpa login — isolasi riwayat berbasis cookie pseudonim `tomato_session_id` (UUID v4) |

---

## 🏛️ Arsitektur Sistem End-to-End

```text
Browser Klien (Petani / Pengguna)
      │
      │ 1. Unggah citra daun (File / Kamera) + cookie 'tomato_session_id'
      ▼
Laravel 12 Application (Port 8000)
      │
      ├── EnsureTomatoSession Middleware (Validasi / terbitkan UUID v4)
      ├── Validasi format citra (JPG/PNG, maks 10MB)
      │
      │ 2. Multipart POST /predict (field: image)
      ▼
FastAPI Microservice (Port 8001)
      │
      ├── Preprocessing: Resize 224x224, Normalisasi ImageNet
      ├── EfficientNet-B0 Inference (DirectML / CPU)
      │
      │ 3. Response JSON: {class, confidence, class_id, inference_time_ms}
      ▼
Laravel 12 Application
      │
      │ 4. Upload citra ke Supabase Storage (detection-images/{session_id}/{uuid}.jpg)
      │ 5. Dapatkan Public URL
      │ 6. Lookup master penyakit & model_cnn aktif
      │ 7. INSERT ke tabel riwayat_deteksi
      │
      │ 8. Render UI Responsif (Blade + Tailwind) dengan rekomendasi agronomis
      ▼
Browser Klien (Tampilan Hasil Diagnosa & Riwayat Sesi)
```

---

## 🌿 10 Kelas Patologi Daun Tomat Terklasifikasi

| # | Kelas Teknis | Nama Indonesia | Patogen / Penyebab |
|:---:|---|---|---|
| 0 | `Bacterial Spot` | Bercak Bakteri | Bakteri *Xanthomonas campestris* |
| 1 | `Early Blight` | Bercak Kering | Jamur *Alternaria solani* |
| 2 | `Healthy` | Daun Sehat | Kondisi tanaman tomat prima |
| 3 | `Late Blight` | Hawar Daun | Oomycete *Phytophthora infestans* |
| 4 | `Leaf Mold` | Kapang Daun | Jamur *Passalora fulva* |
| 5 | `Tomato Mosaic Virus` | Virus Mosaik | Virus *Tobamovirus* (ToMV) |
| 6 | `Septoria Leaf Spot` | Bercak Daun Septoria | Jamur *Septoria lycopersici* |
| 7 | `Spider Mites` | Tungau Laba-Laba | Hama *Tetranychus urticae* |
| 8 | `Target Spot` | Bercak Sasaran | Jamur *Corynespora cassiicola* |
| 9 | `Tomato Yellow Leaf Curl Virus (TYLCV)` | Virus Kuning Keriting | Virus *Begomovirus* via Kutu Kebul |

---

## 📂 Struktur Repositori

```text
TomatCare/
├── ml/                           # Pipeline Machine Learning & Eksperimen
│   ├── dataset/splits/           # Manifest dataset (manifest.csv) & split 80/10/10
│   ├── models/                   # final_model.pt, label_mapping.json, preprocessing.json
│   ├── results/                  # Confusion matrix heatmap, training curve, metrik JSON
│   ├── src/                      # Source code dataset, preprocessing, model, training, evaluation
│   └── requirements.txt          # Dependency ML (PyTorch, torchvision, etc.)
│
├── fastapi/                      # REST API Microservice untuk Model Inference
│   ├── app/                      # FastAPI router, schema, inference service
│   ├── models/                   # Bobot model final & mapping konfigurasi
│   ├── requirements.txt          # Dependency API (FastAPI, uvicorn, Pillow)
│   ├── test_api.py               # Test suite contract FastAPI
│   └── .env.example              # Template konfigurasi port, path model, device
│
├── laravel/                      # Web Application Utama (Laravel 12)
│   ├── app/Http/Controllers/     # DetectionController & HistoryController
│   ├── app/Http/Middleware/      # EnsureTomatoSession (Cookie tracking)
│   ├── app/Services/             # FastApiService & SupabaseService
│   ├── app/Helpers/              # DiseaseCatalog (Data agronomis 10 penyakit)
│   ├── resources/views/          # Blade views (layouts, detection, history, about)
│   ├── routes/web.php            # Route web & API history
│   └── .env.example              # Template konfigurasi Supabase & FastAPI
│
├── README.md                     # Dokumentasi arsitektur & cara penggunaan
└── RUNBOOK.md                    # Panduan operasional developer & eksekusi service
```

---

## 🚀 Panduan Menjalankan Sistem Secara Lokal

### 1. Menjalankan Microservice FastAPI

Buka terminal di direktori `fastapi/`:
```bash
cd fastapi
python -m uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```
*Health Check*: Buka `http://127.0.0.1:8001/health` di browser. Respons: `{"status":"ok","model_loaded":true}`.

### 2. Menjalankan Aplikasi Web Laravel

Buka terminal di direktori `laravel/`:
```bash
cd laravel
php artisan serve --port 8000
```
Akses aplikasi melalui browser: **`http://localhost:8000`** atau **`http://127.0.0.1:8000`**.

---

## 🧪 Validasi Pengujian Sistem

Proyek ini dilengkapi dengan skrip pengujian otomatis dan test suite formal di setiap lapisan:
- **FastAPI Contract & Multi-Class Sampling**: `python fastapi/test_api.py` (Menguji `/health`, 4-field contract `/predict`, validasi tipe `class_id: int`, evaluasi akurasi sampling 10 kelas dari dataset test sesungguhnya, dan handling error).
- **Laravel PHPUnit Feature Tests**: `cd laravel && php artisan test` (Menguji pipeline deteksi tanpa fallback diam-diam, validasi request citra, pembuatan session UUID v4, dan penanganan error).
- **Session & Cookie Verification**: `php laravel/test_session.php` (Menguji penerbitan cookie `tomato_session_id`, UUID v4, dan upsert `user_sessions`).
- **End-to-End Pipeline & Isolation**: `php laravel/test_phase11.php` (Menguji alur deteksi, kelengkapan DiseaseCatalog, dan pembuktian isolasi data antar sesi).
- **CI/CD Automation**: GitHub Actions workflow (`.github/workflows/ci.yml`) menjalankan pengujian FastAPI dan Laravel secara otomatis pada setiap pull request/push.

---

## 📄 Lisensi & Disclaimer Agrikultur

Sistem ini dikembangkan sebagai purwarupa klasifikasi berbasis kecerdasan buatan untuk identifikasi visual awal penyakit daun tomat. Hasil inferensi tidak menggantikan analisis laboratorium agronomis atau konsultasi penyuluh pertanian bersertifikasi secara mutlak.

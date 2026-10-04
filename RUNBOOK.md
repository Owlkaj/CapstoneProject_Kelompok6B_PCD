# 📖 Runbook Operasional & Panduan Reproduksi — TomatCare

Dokumen ini memuat panduan teknis operasional untuk mereproduksi proses pelatihan model (*Machine Learning*), menjalankan layanan inferensi (*FastAPI*), menjalankan server aplikasi web (*Laravel 12*), serta prosedur penanganan masalah (*troubleshooting*).

---

## 1. Prasyarat Sistem & Dependensi

* **Sistem Operasi**: Windows 11 / Linux
* **Python**: 3.10.x (Virtual Environment pada `ml/.venv`)
* **PHP**: 8.2+ (Ekstensi aktif: `curl`, `fileinfo`, `openssl`, `mbstring`, `pdo_sqlite`)
* **Composer**: 2.x
* **Hardware Akselerasi**: AMD GPU (DirectML) atau CPU Fallback

---

## 2. Reproduksi Pipeline Machine Learning (Training & Evaluasi)

Seluruh script eksekusi berada di direktori `ml/`:

### A. Persiapan Virtual Environment
```powershell
cd ml
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

### B. Eksperimen E01 — Baseline Transfer Learning
Membekukan (*freeze*) backbone EfficientNetB0 dan hanya melatih *custom classification head*:
```powershell
python -m src.training.train --experiment E01 --epochs 5 --batch-size 32 --lr 1e-3
```
*Output*: Checkpoint tersimpan di `ml/models/E01_best.pt`.

### C. Eksperimen E02 — Fine-Tuning 2 Stage Teratas
Membuka freeze (*unfreeze*) 2 blok konvolusi teratas EfficientNetB0 dengan learning rate rendah:
```powershell
python -m src.training.train --experiment E02 --epochs 5 --batch-size 32 --lr 1e-4 --resume ml/models/E01_best.pt
```
*Output*: Checkpoint tersimpan di `ml/models/E02_best.pt` (Akurasi Val: 95.60%).

### D. Evaluasi Akhir Test Set & Penyalinan Model Final
Menjalankan evaluasi pada 2.000 citra *test set unseen*:
```powershell
python -m src.evaluation.evaluate --model-path ml/models/E02_best.pt --test-manifest ml/dataset/splits/manifest.csv
```
*Output*:
- `ml/models/final_model.pt` (Disalin ke `fastapi/models/final_model.pt`)
- `ml/results/confusion_matrix.png` & `confusion_matrix.csv`
- `ml/results/evaluation_metrics.json` (Akurasi Test: 96.60%, Macro F1: 96.60%)

---

## 3. Menjalankan Microservice FastAPI

Microservice bertugas menangani request inferensi dari Laravel.

### A. Konfigurasi (`fastapi/.env`)
Pastikan file `.env` berisi:
```env
MODEL_PATH=./models/final_model.pt
LABEL_MAPPING_PATH=./models/label_mapping.json
PREPROCESSING_CONFIG_PATH=./models/preprocessing.json
DEVICE=cpu
MAX_UPLOAD_SIZE_MB=10
```

### B. Menjalankan Server Uvicorn
```powershell
cd fastapi
..\ml\.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8001
```

### C. Verifikasi Layanan
Jalankan pengujian kontrak API:
```powershell
..\ml\.venv\Scripts\python.exe test_api.py
```
Seluruh 6 skenario pengujian harus berstatus `PASS`.

---

## 4. Menjalankan Aplikasi Web Laravel

Aplikasi Laravel bertindak sebagai orkestrator antarmuka pengguna, session tracking, dan integrasi cloud.

### A. Konfigurasi (`laravel/.env`)
Pastikan variabel berikut terisi dengan konfigurasi project Anda:
```env
APP_NAME=TomatCare
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

SUPABASE_URL=https://<your-project-id>.supabase.co
SUPABASE_ANON_KEY=<your-supabase-anon-key>
SUPABASE_STORAGE_BUCKET=detection-images

FASTAPI_URL=http://127.0.0.1:8001
FASTAPI_CONNECT_TIMEOUT=5
FASTAPI_TIMEOUT=60
```

### B. Menjalankan Server Laravel
```powershell
cd laravel
php artisan serve --port 8000
```
Buka browser dan navigasikan ke `http://localhost:8000`.

---

## 5. Prosedur Supabase (Database & Storage)

### A. Skema Database PostgreSQL
Jika memerlukan inisialisasi ulang database pada project baru, jalankan DDL berikut pada Supabase SQL Editor:
```sql
CREATE TABLE IF NOT EXISTS public.penyakit (
    id_penyakit BIGSERIAL PRIMARY KEY,
    nama_penyakit VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT
);

CREATE TABLE IF NOT EXISTS public.model_cnn (
    id_model BIGSERIAL PRIMARY KEY,
    nama_model VARCHAR(100) NOT NULL,
    arsitektur VARCHAR(100) NOT NULL,
    file_bobot TEXT NOT NULL,
    akurasi NUMERIC(6,5),
    status VARCHAR(20) NOT NULL DEFAULT 'inactive',
    CONSTRAINT model_cnn_status_check CHECK (status IN ('active', 'inactive', 'archived')),
    CONSTRAINT model_cnn_akurasi_check CHECK (akurasi IS NULL OR (akurasi >= 0 AND akurasi <= 1))
);

CREATE TABLE IF NOT EXISTS public.user_sessions (
    session_id UUID PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    last_accessed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.riwayat_deteksi (
    id_riwayat BIGSERIAL PRIMARY KEY,
    session_id UUID NOT NULL REFERENCES public.user_sessions(session_id) ON UPDATE CASCADE ON DELETE CASCADE,
    id_penyakit BIGINT NOT NULL REFERENCES public.penyakit(id_penyakit) ON UPDATE CASCADE ON DELETE RESTRICT,
    id_model BIGINT NOT NULL REFERENCES public.model_cnn(id_model) ON UPDATE CASCADE,
    citra_input TEXT NOT NULL,
    confidence_score NUMERIC(6,5) NOT NULL CHECK (confidence_score >= 0 AND confidence_score <= 1),
    tanggal_deteksi TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_riwayat_session ON public.riwayat_deteksi(session_id);
CREATE INDEX IF NOT EXISTS idx_riwayat_tanggal ON public.riwayat_deteksi(tanggal_deteksi DESC);
CREATE INDEX IF NOT EXISTS idx_riwayat_penyakit ON public.riwayat_deteksi(id_penyakit);
CREATE INDEX IF NOT EXISTS idx_riwayat_model ON public.riwayat_deteksi(id_model);
```

### B. Storage Bucket
Pastikan bucket `detection-images` memiliki opsi `public: true` dan policy upload diizinkan untuk peran `anon`.

---

## 6. Prosedur Penanganan Masalah (Troubleshooting)

### Kasus 1: Error "Inference service connection timed out or unreachable" (503)
* **Penyebab**: Server FastAPI pada port 8001 belum dinyalakan atau mengalami crash.
* **Solusi**: Periksa apakah proses uvicorn berjalan:
  ```powershell
  Invoke-RestMethod -Uri "http://127.0.0.1:8001/health"
  ```
  Jika tidak merespon, jalankan kembali uvicorn sesuai instruksi pada Bagian 3.

### Kasus 2: "Failed to upload image to storage" (Supabase Storage Error)
* **Penyebab**: Bucket `detection-images` tidak ditemukan atau kredensial anon key tidak valid.
* **Solusi**: Verifikasi nilai `SUPABASE_URL` dan `SUPABASE_ANON_KEY` di `laravel/.env`. Pastikan koneksi internet aktif.

### Kasus 3: Riwayat tidak muncul setelah refresh browser
* **Penyebab**: Cookie `tomato_session_id` terhapus atau dibersihkan oleh browser.
* **Solusi**: Sesuai spesifikasi arsitektur privasi sistem, riwayat terikat pada cookie sesi browser (`tomato_session_id`). Jika cookie dibersihkan, browser memperoleh session ID baru sehingga riwayat lama terisolasi demi menjaga privasi pseudonim.

# UI/UX Specification — Sistem Klasifikasi Penyakit Daun Tomat (TomatCare)

Dokumen ini adalah **spesifikasi desain antarmuka (UI) dan pengalaman pengguna (UX)** untuk aplikasi web klasifikasi penyakit daun tomat berbasis citra. Seluruh arsitektur frontend ditujukan untuk implementasi menggunakan **Laravel (Blade + Tailwind CSS)** dengan gaya visual yang bersih, alami, dan modern.

---

## 1. Identitas Visual & Design System

### 1.1 Branding
* **Nama Aplikasi**: **TomatCare**
* **Tagline**: *Tomato Leaf Classifier & Diagnosis Assistant*
* **Karakter Desain**: Clean, natural, agriculture-oriented, modern, banyak whitespace, card dengan sudut membulat (*rounded-2xl*), border tipis berbayang halus (*soft elevation*).

### 1.2 Palet Warna (Color Palette)
Aplikasi mengusung nuansa hijau segar pertanian dengan kontras warna fungsional untuk status penyakit:

| Elemen / Token | Hex Code | Peran / Penggunaan |
|---|:---:|---|
| **Primary Green** | `#2E7D32` | Tombol utama, tab aktif, icon brand, aksen visual utama |
| **Primary Hover** | `#1B5E20` | State hover tombol primary |
| **Light Green Surface** | `#E8F5E9` | Background badge, highlight card, container preview aktif |
| **Dark Neutral (Text)** | `#111827` | Heading, teks utama, judul hasil diagnosa |
| **Muted Neutral (Body)** | `#4B5563` | Deskripsi, teks bantuan, label form, footer |
| **Border Soft** | `#E5E7EB` | Garis batas card, pemisah tabel, dropzone border |
| **Background App** | `#F9FAFB` | Latar belakang seluruh halaman web |
| **White Surface** | `#FFFFFF` | Background card, navbar, modal container |

#### Warna Semantic Status Penyakit (Badge & Tingkat Keparahan)
* `Healthy` (Sehat): **Emerald** (`bg-emerald-100 text-emerald-800 border-emerald-300`)
* `Bacterial Spot`: **Orange** (`bg-orange-100 text-orange-800 border-orange-300`)
* `Early Blight`: **Amber** (`bg-amber-100 text-amber-800 border-amber-300`)
* `Late Blight`: **Red** (`bg-red-100 text-red-800 border-red-300`)
* `Leaf Mold`: **Lime** (`bg-lime-100 text-lime-800 border-lime-300`)
* `Tomato Mosaic Virus`: **Pink** (`bg-pink-100 text-pink-800 border-pink-300`)
* `Septoria Leaf Spot`: **Purple** (`bg-purple-100 text-purple-800 border-purple-300`)
* `Spider Mites`: **Stone** (`bg-stone-100 text-stone-800 border-stone-300`)
* `Target Spot`: **Cyan** (`bg-cyan-100 text-cyan-800 border-cyan-300`)
* `TYLCV (Kuning Keriting)`: **Yellow** (`bg-yellow-100 text-yellow-800 border-yellow-300`)

### 1.3 Tipografi
* **Heading (H1 - H4)**: `Outfit`, sans-serif (font-weight: 600, 700) — memberikan kesan modern, tegas, dan ramah.
* **Body / Paragraf / Tabel**: `Inter` / `Plus Jakarta Sans`, sans-serif (font-weight: 400, 500) — memaksimalkan keterbacaan instruksi agronomis dan data hasil deteksi.

---

## 2. Struktur Navigasi & Informasi (Laravel Routes)

Aplikasi memiliki 3 navigasi utama yang diakses melalui URL Laravel:

```text
┌────────────────────────────────────────────────────────────────┐
│ 🍃 TomatCare                    [Deteksi]  [Riwayat]  [Tentang] │
└────────────────────────────────────────────────────────────────┘
```

1. **`/` atau `/detect` (Deteksi)**: Halaman utama untuk mengunggah gambar daun tomat, melihat preview, menjalankan inferensi, dan membaca rekomendasi perawatan.
2. **`/history` (Riwayat Deteksi)**: Halaman riwayat deteksi pengguna yang diisolasi secara otomatis menggunakan cookie sesi tanpa login (`tomato_session_id`).
3. **`/about` (Tentang Sistem)**: Halaman informasi mengenai penelitian model EfficientNetB0, katalog 10 penyakit daun tomat, metrik akurasi, dan batasan disclaimer sistem.

### 2.1 Header / Navbar Responsif
* **Desktop**: Logo di kiri, menu navigasi pill di kanan. Tombol halaman aktif memiliki background `#2E7D32` dengan teks putih.
* **Mobile**: Hamburger menu di kanan atas yang memunculkan drawer/dropdown vertikal transisi mulus (*slide-down*).

---

## 3. Spesifikasi Komponen & Layar (Laravel Blade)

### 3.1 Layar 1: Halaman Deteksi (`resources/views/detection/index.blade.php`)

Layout desktop menggunakan pembagian 2 kolom (*side-by-side*), dan menjadi 1 kolom vertikal (*stacked*) pada layar tablet/ponsel:

```text
┌──────────────────────────────────────────────────────────────┐
│ [Hero Section: Judul, Badge Model EfficientNetB0, Deskripsi] │
├──────────────────────────────┬───────────────────────────────┤
│ Kolom Kiri: Input Daun       │ Kolom Kanan: Hasil Diagnosis  │
│                              │                               │
│ - Tab: File vs Kamera        │ - Empty State / Loading /     │
│ - Dropzone Drag & Drop       │   Hasil Diagnosa Aktif        │
│ - Image Thumbnail Preview    │ - Confidence Bar (%)          │
│ - Tombol "Deteksi Sekarang"  │ - Deskripsi Gejala            │
│                              │ - Rekomendasi Tindakan        │
│                              │ - Metadata Model & Kecepatan  │
└──────────────────────────────┴───────────────────────────────┘
```

#### A. Hero Section
* **Badge**: Pill kecil hijau muda berikon checklist: `● Model EfficientNetB0 Siap (Akurasi 96.60%)`.
* **Judul (H1)**: `Mendeteksi Penyakit Daun Tomat Secara Akurat`.
* **Subjudul**: `Unggah foto daun tomat tanaman Anda atau ambil foto langsung melalui kamera ponsel. Sistem cerdas kami akan menganalisis gejala visual dan memberikan rekomendasi penanganan secara seketika.`

#### B. Kolom Kiri — Area Input & Upload (`<x-upload-card>`)
* **Pilihan Mode (Tabs)**:
  * Tab 1: **Unggah Berkas** (Icon folder/upload).
  * Tab 2: **Ambil Foto Kamera** (Icon kamera).
* **State Dropzone Berkas**:
  * *Idle State*: Kotak bergaris putus-putus (*dashed border*), icon daun/awan, instruksi: *"Tarik & lepas foto daun tomat ke sini, atau klik untuk memilih file"*, keterangan format: `JPG, JPEG, PNG (Maks. 10MB)`.
  * *Dragover State*: Background berubah menjadi hijau lembut `#E8F5E9` dengan border hijau menyala.
  * *Preview State*: Menampilkan foto yang dipilih dalam rasio 1:1 atau 4:3 dengan tombol "Ganti Foto" dan icon tempat sampah untuk menghapus preview.
* **State Kamera**:
  * Meminta izin kamera browser (*getUserMedia*).
  * Menampilkan viewfinder video langsung (*live video stream*).
  * Tombol bulat capture foto di tengah bawah.
* **Tombol Eksekusi**:
  * Tombol utama berukuran penuh (*full width*): `Deteksi Penyakit Daun` (Warna `#2E7D32`).
  * *Disabled State* saat belum ada gambar terpilih.
  * *Loading State* saat proses inferensi berjalan: tombol menampilkan spinner animasi dengan teks `Menganalisis citra daun...`.

#### C. Kolom Kanan — Kartu Hasil Diagnosis (`<x-result-card>`)
* **State 1 — Empty State (Belum ada deteksi)**:
  * Ilustrasi daun tomat abu-abu minimalis.
  * Teks: *"Belum Ada Hasil Analisis"*.
  * Panduan singkat: *"Unggah foto daun tomat di sebelah kiri dan klik tombol Deteksi untuk melihat diagnosa penyakit serta rekomendasi penanganannya."*
* **State 2 — Loading / Skeleton State**:
  * Animasi berkedip halus (*pulse shimmer*) pada layout kartu hasil diagnosis selagi request HTTP berjalan ke server.
* **State 3 — Result State (Hasil Diagnosa Muncul)**:
  * **Header Diagnosa**:
    * Nama Penyakit dalam Bahasa Indonesia (H2 tebal, misal: *Bercak Kering (Early Blight)*).
    * Badge Kategori Penyakit dengan warna semantic (misal: *Jamur Alternaria solani*).
  * **Bar Tingkat Kepercayaan (Confidence Bar)**:
    * Komponen `<x-confidence-bar>`:
    * Menampilkan label *"Tingkat Kepercayaan Model"* dan angka persentase tebal (contoh: `96.8%`).
    * Bar progress membulat dengan warna adaptif:
      * Hijau (`#2E7D32`) jika $\ge 90\%$ (Sangat Yakin).
      * Kuning/Amber (`#F59E0B`) jika $70\% - 89.9\%$ (Cukup Yakin).
      * Merah (`#EF4444`) jika $< 70\%$ (Perlu Verifikasi Ulang).
  * **Kotak Informasi & Penanganan (Tabs / Accordion)**:
    * **Deskripsi Gejala**: Menjelaskan tanda-tanda visual yang terdeteksi pada daun tomat.
    * **Rekomendasi Penanganan Agrikultur**: Langkah praktis bagi petani/pengguna (misal: sanitasi daun bawah, fungisida yang disarankan, perbaikan sirkulasi udara bedengan).
  * **Footer Metadata**:
    * Menampilkan: Model `EfficientNetB0` • Waktu Inferensi: `~30-40 ms` • Tanggal & Jam Analisis.
    * Tombol sekunder: `"Lihat Riwayat Sesi"` dan tombol cetak/simpan ringkasan.

---

### 3.2 Layar 2: Halaman Riwayat Deteksi (`resources/views/history/index.blade.php`)

Menampilkan daftar semua hasil deteksi yang pernah dilakukan pengguna pada browser tersebut tanpa mewajibkan pendaftaran atau login.

#### A. Header Riwayat
* **Judul**: `Riwayat Deteksi Tanpa Login`
* **Badge Informasi Sesi**:
  * Pill kecil biru/hijau: *"Identitas Sesi Browser: [UUID singkat] • Riwayat tersimpan otomatis via cookie sesi"*.
* **Filter & Pencarian**:
  * Input pencarian berdasarkan nama penyakit.
  * Dropdown filter status (Semua Kelas, Hanya Sehat, Sakit).

#### B. Tampilan Daftar Riwayat (`<x-history-list>`)
* **State 1 — Kosong (Belum ada riwayat)**:
  * Icon keranjang riwayat kosong.
  * Teks: *"Belum ada riwayat pemeriksaan daun pada browser ini."*
  * Tombol CTA hijau: `"Mulai Deteksi Sekarang"`.
* **State 2 — Berisi Data (Grid Card / Responsive Table)**:
  Setiap item riwayat menampilkan:
  1. **Thumbnail Foto**: Citra daun yang tersimpan di Supabase Storage.
  2. **Nama Penyakit**: Judul nama Indonesia & Inggris dengan badge warna.
  3. **Persentase Confidence**: Badge persentase akurasi model.
  4. **Tanggal & Waktu**: Format lokal Indonesia (misal: *04 Okt 2026, 14:30 WIB*).
  5. **Tombol Aksi**:
     * Tombol *"Lihat Detail"* (Membuka popup modal detail komprehensif).
     * Tombol *"Hapus"* (Icon tempat sampah merah untuk menghapus record dari database).

#### C. Modal Detail Riwayat (`<x-history-detail-modal>`)
* Popup dialog interaktif yang menampilkan:
  * Foto daun resolusi penuh.
  * Parameter lengkap diagnosa, persentase kepastian, deskripsi penyakit, dan solusi perawatan.
  * Tombol tutup modal (*Escape* atau klik di luar modal).

---

### 3.3 Layar 3: Halaman Tentang Sistem (`resources/views/about/index.blade.php`)

Menjelaskan latar belakang riset dan transparansi cara kerja sistem:
1. **Profil Proyek**: Capstone Machine Learning klasifikasi penyakit tanaman tomat.
2. **Spesifikasi Model & Metrik**:
   * Arsitektur: **EfficientNetB0** (Transfer Learning + Fine-Tuning).
   * Dataset: **20.000 citra** (stratified 2.000 citra/kelas pada 10 kelas).
   * Akurasi Evaluasi Test Set: **96.60%** (F1-Score: 96.60%).
3. **Katalog Visual 10 Kelas Penyakit**:
   * Grid 10 kartu kecil yang menjelaskan foto contoh, nama ilmiah, dan ciri khas visual masing-masing kelas (Bacterial Spot, Early Blight, Healthy, Late Blight, Leaf Mold, Mosaic Virus, Septoria, Spider Mites, Target Spot, TYLCV).
4. **Catatan Disclaimer & Batasan Agrikultur**:
   * Alert kotak informasi abu-abu/kuning: *"Sistem ini berfungsi sebagai alat bantu klasifikasi citra berbasis kecerdasan buatan dan bukan merupakan pengganti diagnosis agronomis di lapangan secara mutlak."*

---

## 4. Pola Interaksi UX & Feedback Pengguna

1. **Optimistic Loading & Progress Feedback**:
   * Setiap aksi unggah menampilkan indikator visual seketika agar pengguna tidak mengira aplikasi macet.
2. **Validasi File di Sisi Klien & Server**:
   * Jika file yang dipilih bukan gambar (misal PDF atau TXT), tolak langsung sebelum upload dengan pesan toast merah: *"Format file tidak didukung. Harap pilih gambar JPG atau PNG."*
   * Jika ukuran melebihi 10MB, tolak dengan toast: *"Ukuran file terlalu besar. Maksimum ukuran adalah 10 MB."*
3. **Error Handling yang Sopan**:
   * Jika koneksi ke server inferensi timeout, tampilkan kartu alert peringatan kuning dengan tombol *"Coba Lagi"*.
4. **Isolasi Privasi Sesi**:
   * Pengguna diinformasikan bahwa data riwayat terikat dengan cookie browser `tomato_session_id`. Jika cookie dibersihkan dari browser, riwayat lama tidak akan tampil demi menjaga privasi pseudonim.

---

## 5. Struktur Komponen Laravel Blade

Berikut pemetaan file view Blade yang direkomendasikan pada Laravel:

```text
laravel/resources/views/
├── layouts/
│   └── app.blade.php              # Shell layout (HTML5, Meta SEO, Tailwind CDN, Header & Footer)
├── components/
│   ├── navbar.blade.php           # Navigasi desktop & mobile drawer
│   ├── footer.blade.php           # Footer disclaimer & copyright
│   ├── confidence-bar.blade.php   # Progress bar confidence adaptif
│   ├── disease-badge.blade.php    # Badge warna-warni status kelas penyakit
│   └── toast.blade.php            # Notifikasi pop-up error/sukses
├── detection/
│   └── index.blade.php            # Halaman deteksi utama (hero + upload + result)
├── history/
│   └── index.blade.php            # Halaman riwayat deteksi & detail modal
└── about/
    └── index.blade.php            # Halaman tentang sistem, katalog 10 penyakit & metrik riset
```

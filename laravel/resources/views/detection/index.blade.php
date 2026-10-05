@extends('layouts.app')

@section('title', 'Deteksi Daun Tomat — TomatCare')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Hero Section -->
    <div class="text-center max-w-3xl mx-auto mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold mb-4 border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
            Model EfficientNetB0 Siap (Akurasi 96.60%)
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 tracking-tight leading-tight">
            Mendeteksi Penyakit Daun Tomat Secara Akurat
        </h1>
        <p class="mt-4 text-base sm:text-lg text-gray-600 leading-relaxed">
            Unggah foto daun tomat tanaman Anda atau ambil langsung melalui kamera ponsel. Sistem cerdas kami menganalisis gejala visual dan memberikan rekomendasi penanganan agrikultur secara instan.
        </p>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: Input Daun (5 cols) -->
        <div class="lg:col-span-6 bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h2 class="text-lg font-bold text-gray-900 font-heading flex items-center gap-2">
                    <span>📸</span> Masukkan Foto Daun
                </h2>
                <div class="flex bg-gray-100 p-1 rounded-xl text-xs font-medium">
                    <button type="button" id="tabUploadBtn" class="px-3 py-1.5 rounded-lg bg-white shadow-xs text-gray-900 font-semibold transition-all">
                        Unggah File
                    </button>
                    <button type="button" id="tabCameraBtn" class="px-3 py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all">
                        Buka Kamera
                    </button>
                </div>
            </div>

            <!-- Tab 1: File Upload Dropzone -->
            <div id="uploadTabContent" class="space-y-4">
                <div id="dropzone" class="relative border-2 border-dashed border-gray-300 rounded-2xl p-6 sm:p-10 text-center hover:border-emerald-600 hover:bg-emerald-50/40 transition-all cursor-pointer group">
                    <input type="file" id="fileInput" accept="image/jpeg,image/png,image/jpg" class="hidden">
                    
                    <!-- Idle State -->
                    <div id="dropzoneIdle" class="space-y-3">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                            🍃
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                Tarik & lepas foto daun tomat ke sini
                            </p>
                            <p class="text-xs text-gray-500 mt-1">atau klik untuk memilih berkas dari perangkat</p>
                        </div>
                        <p class="text-[11px] text-gray-400 uppercase tracking-wider font-medium">
                            JPG, JPEG, PNG (Maksimal 10 MB)
                        </p>
                    </div>

                    <!-- Preview State -->
                    <div id="dropzonePreview" class="hidden space-y-4">
                        <div class="relative w-full max-h-72 aspect-square max-w-xs mx-auto rounded-xl overflow-hidden border border-gray-200 shadow-xs">
                            <img id="imagePreview" src="" alt="Pratinjau Daun" class="w-full h-full object-cover">
                            <button type="button" id="removeImageBtn" class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1.5 shadow-md hover:bg-red-700 transition-colors" title="Hapus foto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p id="fileNamePreview" class="text-xs text-gray-500 font-medium truncate max-w-xs mx-auto"></p>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Camera Viewfinder (Hidden by default) -->
            <div id="cameraTabContent" class="hidden space-y-4 text-center">
                <div class="relative w-full aspect-square max-w-sm mx-auto bg-black rounded-2xl overflow-hidden border border-gray-300 shadow-inner">
                    <video id="cameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                    <canvas id="cameraCanvas" class="hidden"></canvas>
                    <div id="cameraOverlay" class="absolute inset-0 border-2 border-emerald-500/50 rounded-2xl pointer-events-none flex items-center justify-center">
                        <div class="w-48 h-48 border-2 border-dashed border-emerald-400/80 rounded-xl"></div>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-3">
                    <button type="button" id="captureBtn" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ambil Foto
                    </button>
                    <button type="button" id="switchCameraBtn" class="px-3 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium">
                        🔄 Balik Kamera
                    </button>
                </div>
            </div>

            <!-- Error Banner -->
            <div id="errorBanner" class="hidden p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
                <span>⚠️</span>
                <span id="errorMessage">Terjadi kesalahan pada sistem.</span>
            </div>

            <!-- Action Button -->
            <button type="button" id="detectBtn" disabled class="w-full py-3.5 px-6 rounded-xl font-heading font-bold text-base text-white bg-[#2E7D32] hover:bg-[#1B5E20] disabled:bg-gray-300 disabled:cursor-not-allowed shadow-sm transition-all flex items-center justify-center gap-2">
                <span id="btnIcon">🔍</span>
                <span id="btnText">Deteksi Penyakit Daun</span>
            </button>

            <!-- Quick Tips Notice -->
            <div class="bg-amber-50/80 border border-amber-200/80 rounded-xl p-3.5 flex items-start gap-2.5 text-xs text-amber-900">
                <span class="text-base leading-none">💡</span>
                <div>
                    <strong>Standar Foto Model (PlantVillage):</strong> Letakkan 1 helai daun di atas latar polos (kertas putih/abu-abu netral) dengan pencahayaan merata.
                    <a href="#panduan-foto" class="underline font-semibold hover:text-amber-950 ml-1">Lihat syarat lengkap ↓</a>
                </div>
            </div>
        </div>

        <!-- Right Column: Hasil Diagnosis (6 cols) -->
        <div class="lg:col-span-6 bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 min-h-[460px] flex flex-col justify-between">
            
            <!-- State 1: Empty State -->
            <div id="resultEmptyState" class="my-auto text-center py-12 px-4 space-y-4">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-gray-50 border border-gray-200 text-gray-400 flex items-center justify-center text-4xl">
                    🌱
                </div>
                <h3 class="text-xl font-bold text-gray-800 font-heading">
                    Belum Ada Hasil Analisis
                </h3>
                <p class="text-sm text-gray-500 max-w-sm mx-auto leading-relaxed">
                    Pilih atau ambil foto daun tomat di sebelah kiri, kemudian klik tombol <strong>Deteksi Penyakit Daun</strong> untuk mendapatkan diagnosis cerdas serta rekomendasi penanganannya.
                </p>
            </div>

            <!-- State 2: Skeleton Loading Shimmer -->
            <div id="resultLoadingState" class="hidden space-y-6 my-auto py-8">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl bg-gray-200 shimmer-pulse"></div>
                    <div class="space-y-2 flex-1">
                        <div class="h-6 w-3/4 bg-gray-200 rounded shimmer-pulse"></div>
                        <div class="h-4 w-1/2 bg-gray-200 rounded shimmer-pulse"></div>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="h-3 w-full bg-gray-200 rounded-full shimmer-pulse"></div>
                    <div class="h-3 w-5/6 bg-gray-200 rounded-full shimmer-pulse"></div>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 space-y-3">
                    <div class="h-4 w-1/3 bg-gray-200 rounded shimmer-pulse"></div>
                    <div class="h-3 w-full bg-gray-200 rounded shimmer-pulse"></div>
                    <div class="h-3 w-4/5 bg-gray-200 rounded shimmer-pulse"></div>
                </div>
                <p class="text-center text-xs text-emerald-700 font-semibold animate-pulse">
                    Menganalisis citra daun tomat dengan model EfficientNetB0...
                </p>
            </div>

            <!-- State 3: Result Active -->
            <div id="resultActiveState" class="hidden space-y-6">
                <!-- Header Diagnosa -->
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-5">
                    <div>
                        <span id="resBadge" class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold mb-2">
                            Penyakit Daun
                        </span>
                        <h3 id="resDiseaseName" class="text-2xl sm:text-3xl font-extrabold text-gray-900 font-heading">
                            -
                        </h3>
                        <p id="resDiseaseEn" class="text-sm font-medium text-gray-500 italic mt-0.5">
                            -
                        </p>
                    </div>
                    <div class="w-20 h-20 rounded-xl overflow-hidden border border-gray-200 shadow-xs flex-shrink-0">
                        <img id="resImage" src="" alt="Citra Deteksi" class="w-full h-full object-cover">
                    </div>
                </div>

                <!-- Confidence Bar -->
                <div class="space-y-2 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-gray-700">Tingkat Kepercayaan Model:</span>
                        <span id="resConfidenceText" class="font-heading font-extrabold text-lg text-emerald-700">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div id="resConfidenceBar" class="h-3 rounded-full bg-emerald-600 transition-all duration-700" style="width: 0%"></div>
                    </div>
                    <p id="resConfidenceCategory" class="text-[11px] text-gray-500 font-medium">Sangat Yakin (&ge; 90%)</p>
                </div>

                <!-- Alternatif Diagnosa (Top-K Predictions / Differential Diagnosis) -->
                <div id="resAlternativesWrap" class="hidden space-y-2.5 bg-gray-50/90 p-4 rounded-xl border border-gray-200/90">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm">🔍</span>
                            <span class="text-xs font-bold text-gray-800 uppercase tracking-wider font-heading">
                                Kemungkinan Alternatif (Diagnosis Banding)
                            </span>
                        </div>
                        <span class="text-[11px] text-gray-500 font-medium">Prediksi Lainnya</span>
                    </div>
                    <p class="text-[11px] text-gray-500 leading-snug">
                        Jika visual daun memiliki kemiripan gejala, model AI juga mengestimasi kemungkinan alternatif berikut:
                    </p>
                    <div id="resAlternativesList" class="space-y-2 pt-1">
                        <!-- Rendered via JavaScript -->
                    </div>
                </div>

                <!-- Tab Informasi / Rekomendasi -->
                <div class="space-y-3">
                    <div class="flex border-b border-gray-200 text-xs font-semibold">
                        <button type="button" id="tabGejalaBtn" class="py-2 px-3 border-b-2 border-emerald-600 text-emerald-800">
                            Gejala Visual
                        </button>
                        <button type="button" id="tabPenangananBtn" class="py-2 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            Rekomendasi Penanganan
                        </button>
                    </div>
                    <div id="gejalaContent" class="text-sm text-gray-700 leading-relaxed bg-emerald-50/50 p-4 rounded-xl border border-emerald-100/60">
                        <p id="resDescription">-</p>
                    </div>
                    <div id="penangananContent" class="hidden text-sm text-gray-700 leading-relaxed bg-amber-50/50 p-4 rounded-xl border border-amber-100/60">
                        <p id="resTreatment">-</p>
                    </div>
                </div>

                <!-- Footer Metadata -->
                <div class="pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                    <div class="flex items-center gap-2">
                        <span>🤖 Model: <strong id="resModelName">EfficientNetB0</strong></span>
                        <span>•</span>
                        <span>⚡ Inferensi: <strong id="resInferenceTime">0 ms</strong></span>
                    </div>
                    <a href="{{ url('/history') }}" class="inline-flex items-center gap-1 font-semibold text-emerald-700 hover:text-emerald-800">
                        Lihat di Riwayat &rarr;
                    </a>
                </div>
            </div>

            <!-- Safety Note -->
            <div class="pt-4 border-t border-gray-100 text-[11px] text-gray-400 flex items-center justify-between">
                <span>🍃 TomatCare AI Assistant</span>
                <span>Cookie sesi: {{ substr(request()->attributes->get('tomato_session_id', 'Active'), 0, 8) }}...</span>
            </div>
        </div>
    </div>

    <!-- Photo Guidelines Section (Standar Dataset PlantVillage) -->
    <div id="panduan-foto" class="mt-12 bg-white rounded-3xl border border-gray-200/80 shadow-sm overflow-hidden scroll-mt-6">
        <div class="bg-gradient-to-r from-emerald-800 to-teal-900 p-6 sm:p-8 text-white">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-700/60 text-emerald-200 text-xs font-semibold uppercase tracking-wider mb-2 border border-emerald-500/30">
                        📋 SOP Standar Dataset PlantVillage
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Syarat & Panduan Foto Daun (Karakteristik PlantVillage)
                    </h2>
                    <p class="mt-2 text-emerald-100/90 text-sm sm:text-base max-w-2xl leading-relaxed">
                        Model AI EfficientNetB0 TomatCare dilatih menggunakan dataset patologi tanaman <strong>PlantVillage</strong>. Agar hasil deteksi memiliki akurasi optimal (&gt;96%), pastikan foto yang Anda ambil atau unggah memenuhi 4 karakteristik standar berikut:
                    </p>
                </div>
                <div class="hidden md:flex items-center justify-center w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 text-3xl">
                    📸
                </div>
            </div>
        </div>

        <!-- 4 Grid Cards -->
        <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card 1 -->
            <div class="bg-emerald-50/50 rounded-2xl p-5 border border-emerald-100/80 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg mb-3 shadow-xs">
                        1
                    </div>
                    <h3 class="font-bold text-gray-900 text-base mb-1.5">Latar Belakang Netral / Polos</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Letakkan daun di atas permukaan datar polos (kertas HVS putih, lembaran abu-abu, atau alas netral). Hindari latar belakang tanah kebun, pot, dedaunan lain, atau jari tangan.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-emerald-100/80 text-[11px] text-emerald-800 font-semibold flex items-center gap-1">
                    <span>✅ Trik: Selipkan kertas putih di belakang daun</span>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-emerald-50/50 rounded-2xl p-5 border border-emerald-100/80 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg mb-3 shadow-xs">
                        2
                    </div>
                    <h3 class="font-bold text-gray-900 text-base mb-1.5">1 Helai Daun Terbentang Rata</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Pilih <strong>satu helai daun tunggal</strong> yang bergejala dan bentangkan menghadap kamera. Hindari daun yang terlipat, tergulung rapat, atau bertumpuk dengan ranting lain.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-emerald-100/80 text-[11px] text-emerald-800 font-semibold flex items-center gap-1">
                    <span>✅ Daun tunggal terisolasi</span>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-emerald-50/50 rounded-2xl p-5 border border-emerald-100/80 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg mb-3 shadow-xs">
                        3
                    </div>
                    <h3 class="font-bold text-gray-900 text-base mb-1.5">Pencahayaan Studio Merata</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Gunakan cahaya menyebar yang terang. Hindari pantulan silau matahari langsung (<em>glare</em>) dan bayangan gelap tubuh atau HP yang menutupi lesi bercak.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-emerald-100/80 text-[11px] text-emerald-800 font-semibold flex items-center gap-1">
                    <span>✅ Bebas silau & bayangan tajam</span>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="bg-emerald-50/50 rounded-2xl p-5 border border-emerald-100/80 flex flex-col justify-between">
                <div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg mb-3 shadow-xs">
                        4
                    </div>
                    <h3 class="font-bold text-gray-900 text-base mb-1.5">Framing Penuh & Fokus Tajam</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Posisikan daun di tengah hingga mengisi <strong>70%–80% layar</strong>. Pastikan seluruh helai daun masuk dalam frame (tidak terpotong) dengan ketajaman fokus tinggi.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-emerald-100/80 text-[11px] text-emerald-800 font-semibold flex items-center gap-1">
                    <span>✅ Daun utuh & fokus tajam</span>
                </div>
            </div>
        </div>

        <!-- Quick Comparison Checklist & Educational Callout -->
        <div class="px-6 pb-8 sm:px-8 space-y-4">
            <!-- Educational Callout -->
            <div class="bg-blue-50/80 rounded-2xl p-4 sm:p-5 border border-blue-200/80 flex items-start gap-3 text-xs text-blue-900">
                <span class="text-xl leading-none">🔬</span>
                <div class="space-y-1">
                    <strong class="font-bold text-blue-950 block font-heading">Mengapa Mengikuti Karakteristik PlantVillage Sangat Penting?</strong>
                    <p class="text-blue-800 leading-relaxed">
                        Dataset <strong>PlantVillage</strong> dibuat di laboratorium dengan memotret daun terpisah di atas latar belakang netral abu-abu/putih. Model <em>Convolutional Neural Network</em> (CNN) mengenali pola lesi penyakit secara optimal ketika tidak ada gangguan visual latar belakang (<em>noise</em>) seperti tanah kebun, batang pohon, atau dedaunan lain. Mengisolasi daun dengan latar polos memastikan fitur visual yang diekstraksi model 100% akurat.
                    </p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-2xl p-4 sm:p-6 border border-gray-200/80">
                <h4 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 flex items-center gap-2 font-heading">
                    <span>📌</span> Checklist Cepat Sebelum Menekan Tombol Deteksi
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                    <div class="flex items-start gap-2.5">
                        <span class="text-emerald-600 text-base leading-none">✔</span>
                        <div>
                            <span class="font-bold text-gray-900 block">Latar Polos Bersih:</span>
                            <span class="text-gray-500">Kertas putih atau alas datar netral tanpa objek asing di sekitarnya.</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-emerald-600 text-base leading-none">✔</span>
                        <div>
                            <span class="font-bold text-gray-900 block">Kondisi Daun Alami:</span>
                            <span class="text-gray-500">Permukaan daun kering alami (bebas lumpur atau genangan tetesan air semprotan).</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-emerald-600 text-base leading-none">✔</span>
                        <div>
                            <span class="font-bold text-gray-900 block">Tingkat Keyakinan & Alternatif:</span>
                            <span class="text-gray-500">Skor &gt; 70% menandakan model sangat yakin; cek daftar alternatif jika bercak tampak mirip.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const dropzoneIdle = document.getElementById('dropzoneIdle');
    const dropzonePreview = document.getElementById('dropzonePreview');
    const imagePreview = document.getElementById('imagePreview');
    const fileNamePreview = document.getElementById('fileNamePreview');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const detectBtn = document.getElementById('detectBtn');
    const btnIcon = document.getElementById('btnIcon');
    const btnText = document.getElementById('btnText');
    const errorBanner = document.getElementById('errorBanner');
    const errorMessage = document.getElementById('errorMessage');

    // State Elements
    const emptyState = document.getElementById('resultEmptyState');
    const loadingState = document.getElementById('resultLoadingState');
    const activeState = document.getElementById('resultActiveState');

    // Result Data Elements
    const resBadge = document.getElementById('resBadge');
    const resDiseaseName = document.getElementById('resDiseaseName');
    const resDiseaseEn = document.getElementById('resDiseaseEn');
    const resImage = document.getElementById('resImage');
    const resConfidenceText = document.getElementById('resConfidenceText');
    const resConfidenceBar = document.getElementById('resConfidenceBar');
    const resConfidenceCategory = document.getElementById('resConfidenceCategory');
    const resDescription = document.getElementById('resDescription');
    const resTreatment = document.getElementById('resTreatment');
    const resModelName = document.getElementById('resModelName');
    const resInferenceTime = document.getElementById('resInferenceTime');

    // Tabs inside Result
    const tabGejalaBtn = document.getElementById('tabGejalaBtn');
    const tabPenangananBtn = document.getElementById('tabPenangananBtn');
    const gejalaContent = document.getElementById('gejalaContent');
    const penangananContent = document.getElementById('penangananContent');

    tabGejalaBtn.addEventListener('click', () => {
        tabGejalaBtn.className = "py-2 px-3 border-b-2 border-emerald-600 text-emerald-800";
        tabPenangananBtn.className = "py-2 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700";
        gejalaContent.classList.remove('hidden');
        penangananContent.classList.add('hidden');
    });

    tabPenangananBtn.addEventListener('click', () => {
        tabPenangananBtn.className = "py-2 px-3 border-b-2 border-emerald-600 text-emerald-800";
        tabGejalaBtn.className = "py-2 px-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700";
        penangananContent.classList.remove('hidden');
        gejalaContent.classList.add('hidden');
    });

    let currentFile = null;

    // File selection handling
    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleSelectedFile(e.target.files[0]);
        }
    });

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.add('border-emerald-600', 'bg-emerald-50/50');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-emerald-600', 'bg-emerald-50/50');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (dt.files && dt.files.length > 0) {
            handleSelectedFile(dt.files[0]);
        }
    });

    function handleSelectedFile(file) {
        errorBanner.classList.add('hidden');

        // Validation
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!validTypes.includes(file.type)) {
            showError('Format file tidak didukung. Harap pilih gambar JPG atau PNG.');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            showError('Ukuran file terlalu besar. Maksimum ukuran adalah 10 MB.');
            return;
        }

        currentFile = file;
        fileNamePreview.textContent = file.name;

        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            dropzoneIdle.classList.add('hidden');
            dropzonePreview.classList.remove('hidden');
            detectBtn.disabled = false;
        };
        reader.readAsDataURL(file);
    }

    removeImageBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        resetFileInput();
    });

    function resetFileInput() {
        currentFile = null;
        fileInput.value = '';
        dropzonePreview.classList.add('hidden');
        dropzoneIdle.classList.remove('hidden');
        detectBtn.disabled = true;
    }

    function showError(msg) {
        errorMessage.textContent = msg;
        errorBanner.classList.remove('hidden');
    }

    // Detection Submit
    detectBtn.addEventListener('click', async () => {
        if (!currentFile) return;

        // UI Loading State
        errorBanner.classList.add('hidden');
        detectBtn.disabled = true;
        btnIcon.textContent = '⏳';
        btnText.textContent = 'Menganalisis citra daun...';

        emptyState.classList.add('hidden');
        activeState.classList.add('hidden');
        loadingState.classList.remove('hidden');

        const formData = new FormData();
        formData.append('image', currentFile);

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const response = await fetch('/detect', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal melakukan deteksi penyakit.');
            }

            renderResult(result.data);

        } catch (err) {
            loadingState.classList.add('hidden');
            emptyState.classList.remove('hidden');
            showError(err.message || 'Koneksi ke server gagal atau terjadi kesalahan.');
        } finally {
            detectBtn.disabled = false;
            btnIcon.textContent = '🔍';
            btnText.textContent = 'Deteksi Penyakit Daun';
        }
    });

    function renderResult(data) {
        loadingState.classList.add('hidden');
        emptyState.classList.add('hidden');
        activeState.classList.remove('hidden');

        resDiseaseName.textContent = data.disease;
        resDiseaseEn.textContent = data.diseaseEn;
        resImage.src = data.image;
        resConfidenceText.textContent = `${data.confidence}%`;
        resConfidenceBar.style.width = `${data.confidence}%`;

        // Semantic Colors
        const colorMap = {
            'emerald': { badge: 'bg-emerald-100 text-emerald-800 border border-emerald-300', bar: 'bg-emerald-600' },
            'orange': { badge: 'bg-orange-100 text-orange-800 border border-orange-300', bar: 'bg-orange-500' },
            'amber': { badge: 'bg-amber-100 text-amber-800 border border-amber-300', bar: 'bg-amber-500' },
            'red': { badge: 'bg-red-100 text-red-800 border border-red-300', bar: 'bg-red-600' },
            'lime': { badge: 'bg-lime-100 text-lime-800 border border-lime-300', bar: 'bg-lime-600' },
            'pink': { badge: 'bg-pink-100 text-pink-800 border border-pink-300', bar: 'bg-pink-500' },
            'purple': { badge: 'bg-purple-100 text-purple-800 border border-purple-300', bar: 'bg-purple-600' },
            'stone': { badge: 'bg-stone-200 text-stone-800 border border-stone-300', bar: 'bg-stone-600' },
            'cyan': { badge: 'bg-cyan-100 text-cyan-800 border border-cyan-300', bar: 'bg-cyan-600' },
            'yellow': { badge: 'bg-yellow-100 text-yellow-800 border border-yellow-300', bar: 'bg-yellow-500' },
        };

        const theme = colorMap[data.color] || colorMap['emerald'];
        resBadge.className = `inline-block px-2.5 py-0.5 rounded-full text-xs font-bold mb-2 ${theme.badge}`;
        resBadge.textContent = data.diseaseEn;
        resConfidenceBar.className = `h-3 rounded-full transition-all duration-700 ${theme.bar}`;

        if (data.confidence >= 90) {
            resConfidenceCategory.textContent = 'Tingkat Keyakinan: Sangat Yakin (≥ 90%)';
        } else if (data.confidence >= 70) {
            resConfidenceCategory.textContent = 'Tingkat Keyakinan: Cukup Yakin (70% - 89.9%)';
        } else {
            resConfidenceCategory.textContent = 'Tingkat Keyakinan: Perlu Verifikasi Lanjutan (< 70%)';
        }

        resDescription.textContent = data.description;
        resTreatment.textContent = data.treatment;
        resModelName.textContent = data.model || 'EfficientNetB0';
        resInferenceTime.textContent = `${data.inference_time_ms} ms`;

        // Render Alternatives / Differential Diagnosis (Top-K)
        const alternativesWrap = document.getElementById('resAlternativesWrap');
        const alternativesList = document.getElementById('resAlternativesList');
        
        if (data.alternatives && data.alternatives.length > 0) {
            alternativesList.innerHTML = '';
            data.alternatives.slice(0, 3).forEach((alt, idx) => {
                const altTheme = colorMap[alt.color] || colorMap['emerald'];
                const item = document.createElement('div');
                item.className = "p-2.5 bg-white rounded-lg border border-gray-200/90 shadow-2xs hover:border-gray-300 transition-all";
                item.innerHTML = `
                    <div class="flex items-center justify-between text-xs mb-1.5">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-gray-100 text-gray-700 text-[10px] font-bold flex items-center justify-center">${idx + 2}</span>
                            <span class="font-bold text-gray-900">${alt.disease}</span>
                            <span class="text-gray-400 italic text-[11px] hidden sm:inline">(${alt.diseaseEn})</span>
                        </div>
                        <span class="font-extrabold text-gray-800 font-heading">${alt.confidence}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full ${altTheme.bar} transition-all duration-500" style="width: ${Math.max(alt.confidence, 2)}%"></div>
                    </div>
                `;
                alternativesList.appendChild(item);
            });
            alternativesWrap.classList.remove('hidden');
        } else {
            alternativesWrap.classList.add('hidden');
        }
    }

    // Camera Mode Implementation
    const tabUploadBtn = document.getElementById('tabUploadBtn');
    const tabCameraBtn = document.getElementById('tabCameraBtn');
    const uploadTabContent = document.getElementById('uploadTabContent');
    const cameraTabContent = document.getElementById('cameraTabContent');
    const cameraVideo = document.getElementById('cameraVideo');
    const cameraCanvas = document.getElementById('cameraCanvas');
    const captureBtn = document.getElementById('captureBtn');
    const switchCameraBtn = document.getElementById('switchCameraBtn');

    let stream = null;
    let facingMode = 'environment';

    tabUploadBtn.addEventListener('click', () => {
        tabUploadBtn.className = "px-3 py-1.5 rounded-lg bg-white shadow-xs text-gray-900 font-semibold transition-all";
        tabCameraBtn.className = "px-3 py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all";
        uploadTabContent.classList.remove('hidden');
        cameraTabContent.classList.add('hidden');
        stopCamera();
    });

    tabCameraBtn.addEventListener('click', async () => {
        tabCameraBtn.className = "px-3 py-1.5 rounded-lg bg-white shadow-xs text-gray-900 font-semibold transition-all";
        tabUploadBtn.className = "px-3 py-1.5 rounded-lg text-gray-600 hover:text-gray-900 transition-all";
        cameraTabContent.classList.remove('hidden');
        uploadTabContent.classList.add('hidden');
        await startCamera();
    });

    async function startCamera() {
        stopCamera();
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: facingMode, width: { ideal: 1080 }, height: { ideal: 1080 } }
            });
            cameraVideo.srcObject = stream;
        } catch (err) {
            showError('Tidak dapat mengakses kamera: ' + err.message);
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
    }

    switchCameraBtn.addEventListener('click', async () => {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        await startCamera();
    });

    captureBtn.addEventListener('click', () => {
        if (!cameraVideo.srcObject) return;
        const context = cameraCanvas.getContext('2d');
        cameraCanvas.width = cameraVideo.videoWidth;
        cameraCanvas.height = cameraVideo.videoHeight;
        context.drawImage(cameraVideo, 0, 0, cameraCanvas.width, cameraCanvas.height);

        cameraCanvas.toBlob((blob) => {
            const capturedFile = new File([blob], 'camera_leaf.jpg', { type: 'image/jpeg' });
            stopCamera();
            tabUploadBtn.click();
            handleSelectedFile(capturedFile);
        }, 'image/jpeg', 0.95);
    });
});
</script>
@endpush

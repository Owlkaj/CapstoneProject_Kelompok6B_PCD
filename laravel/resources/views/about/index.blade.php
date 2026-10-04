@extends('layouts.app')

@section('title', 'Tentang Sistem — TomatCare')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-12">
    <!-- Header -->
    <div class="text-center max-w-3xl mx-auto">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold mb-4 border border-emerald-200">
            <span>🔬</span> Arsitektur Riset & Implementasi
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 font-heading">
            Sistem Klasifikasi Penyakit Daun Tomat
        </h1>
        <p class="mt-4 text-base text-gray-600 leading-relaxed">
            TomatCare dikembangkan sebagai purwarupa sistem pakar visi komputer untuk mengidentifikasi patologi daun tanaman tomat secara cepat, objektif, dan terstandarisasi bagi petani dan pemerhati agrikultur.
        </p>
    </div>

    <!-- Metric Highlights -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs text-center space-y-1">
            <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Model Utama</span>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 font-heading">EfficientNet-B0</h3>
            <p class="text-xs text-gray-500">Transfer Learning + Fine Tuning</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs text-center space-y-1">
            <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Test Accuracy</span>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 font-heading">96.60%</h3>
            <p class="text-xs text-gray-500">Evaluasi pada 2.000 citra unseen</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs text-center space-y-1">
            <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Total Dataset</span>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 font-heading">20.000</h3>
            <p class="text-xs text-gray-500">2.000 citra seimbang per kelas</p>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs text-center space-y-1">
            <span class="text-xs uppercase tracking-wider text-gray-400 font-bold">Macro F1-Score</span>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-emerald-700 font-heading">96.60%</h3>
            <p class="text-xs text-gray-500">Kinerja konsisten di seluruh kelas</p>
        </div>
    </div>

    <!-- 10 Class Disease Catalog -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-200 shadow-xs space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 font-heading">
                Katalog 10 Kelas Patologi Daun Tomat
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Kategori daun yang mampu diklasifikasikan oleh sistem model AI:
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $classes = [
                    ['Bacterial Spot', 'Bercak Bakteri', 'Bakteri Xanthomonas campestris', 'Bercak kecil basah kehitaman dengan halo kuning.', 'orange'],
                    ['Early Blight', 'Bercak Kering', 'Jamur Alternaria solani', 'Cincin konsentris kecoklatan menyerupai pola target sasaran.', 'amber'],
                    ['Healthy', 'Daun Sehat', 'Tanaman Tomat Prima', 'Daun hijau segar alami tanpa gejala nekrosis atau patogen.', 'emerald'],
                    ['Late Blight', 'Hawar Daun', 'Oomycete Phytophthora infestans', 'Bercak basah membusuk cepat pada daun dan batang saat lembab.', 'red'],
                    ['Leaf Mold', 'Kapang Daun', 'Jamur Passalora fulva', 'Bercak klorosis kuning di atas daun dan lapisan beludru di bawahnya.', 'lime'],
                    ['Tomato Mosaic Virus', 'Virus Mosaik', 'Tobamovirus (ToMV)', 'Daun belang hijau muda-tua acak dan pertumbuhan tanaman kerdil.', 'pink'],
                    ['Septoria Leaf Spot', 'Bercak Daun Septoria', 'Jamur Septoria lycopersici', 'Bintik melingkar banyak berdiameter kecil dengan tepi gelap.', 'purple'],
                    ['Spider Mites', 'Tungau Laba-Laba', 'Hama Tetranychus urticae', 'Bintik keperakan halus dan benang jaring tipis di balik daun.', 'stone'],
                    ['Target Spot', 'Bercak Sasaran', 'Jamur Corynespora cassiicola', 'Bercak bundar tegas dengan batas gelap di sekitar jaringan mati.', 'cyan'],
                    ['Tomato Yellow Leaf Curl Virus (TYLCV)', 'Virus Kuning Keriting', 'Begomovirus via Kutu Kebul', 'Tepi daun melengkung ke atas, mengecil, menguning, dan kerdil.', 'yellow'],
                ];
            @endphp

            @foreach($classes as $c)
            <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50/60 hover:bg-white hover:border-gray-300 transition-all space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full
                        @if($c[4] === 'emerald') bg-emerald-100 text-emerald-800
                        @elseif($c[4] === 'red') bg-red-100 text-red-800
                        @elseif($c[4] === 'orange') bg-orange-100 text-orange-800
                        @elseif($c[4] === 'amber') bg-amber-100 text-amber-800
                        @elseif($c[4] === 'lime') bg-lime-100 text-lime-800
                        @elseif($c[4] === 'pink') bg-pink-100 text-pink-800
                        @elseif($c[4] === 'purple') bg-purple-100 text-purple-800
                        @elseif($c[4] === 'stone') bg-stone-200 text-stone-800
                        @elseif($c[4] === 'cyan') bg-cyan-100 text-cyan-800
                        @else bg-yellow-100 text-yellow-800
                        @endif">
                        {{ $c[0] }}
                    </span>
                    <span class="text-xs text-gray-400 font-mono">1/10</span>
                </div>
                <h4 class="font-bold text-gray-900 font-heading text-base">{{ $c[1] }}</h4>
                <p class="text-xs text-emerald-700 font-medium italic">{{ $c[2] }}</p>
                <p class="text-xs text-gray-600 leading-relaxed">{{ $c[3] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Agriculture Disclaimer -->
    <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-2">
        <h4 class="font-bold text-sm font-heading flex items-center gap-2">
            <span>⚠️</span> Batasan & Disclaimer Agrikultur
        </h4>
        <p class="text-xs leading-relaxed text-amber-800">
            Sistem klasifikasi ini dirancang sebagai instrumen bantu diagnosa awal (*first-line screening*) dan bukan merupakan pengganti pemeriksaan patologi tanaman di laboratorium secara mutlak. Akurasi inferensi dipengaruhi oleh pencahayaan foto, ketajaman fokus kamera, serta sudut pengambilan gambar daun. Untuk penanganan tanaman skala komersial, disarankan mengonfirmasi hasil kepada tenaga penyuluh pertanian bersertifikasi.
        </p>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Deteksi Penyakit Daun — TomatCare')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
    <header class="max-w-2xl">
        <p class="text-sm font-semibold text-emerald-700">Deteksi penyakit daun</p>
        <h1 class="mt-2 font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Periksa kesehatan daun tomat Anda
        </h1>
        <p class="mt-3 text-base leading-relaxed text-slate-600">
            Unggah foto daun atau ambil gambar langsung melalui kamera. Sistem akan mengidentifikasi kemungkinan penyakit berdasarkan gejala visual.
        </p>
    </header>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
        {{-- Kolom input --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-5">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <h2 class="font-heading text-base font-semibold text-slate-900">Foto daun</h2>

                <div class="flex rounded-lg bg-slate-100 p-0.5 text-xs font-medium" role="tablist" aria-label="Sumber foto">
                    <button id="tabUploadBtn"
                            type="button"
                            class="segmented-button is-active rounded-md px-3 py-1.5"
                            role="tab"
                            aria-selected="true">
                        Unggah
                    </button>
                    <button id="tabCameraBtn"
                            type="button"
                            class="segmented-button rounded-md px-3 py-1.5"
                            role="tab"
                            aria-selected="false">
                        Kamera
                    </button>
                </div>
            </div>

            <div class="space-y-4 p-5">
                {{-- Tab unggah --}}
                <div id="uploadTabContent">
                    <div id="dropzone"
                         class="group cursor-pointer rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/70 p-6 text-center transition-colors hover:border-emerald-500 hover:bg-emerald-50/40"
                         role="button"
                         tabindex="0"
                         aria-label="Pilih foto daun dari perangkat">
                        <input type="file" id="fileInput" accept="image/jpeg,image/png,image/jpg" class="hidden">

                        <div id="dropzoneIdle" class="space-y-3">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-white text-slate-500 ring-1 ring-slate-200 transition-colors group-hover:text-emerald-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4L8 8"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-700">
                                    Tarik foto ke sini atau <span class="text-emerald-700">pilih file</span>
                                </p>
                                <p class="mt-1 text-xs text-slate-500">JPG atau PNG, maksimal 10 MB</p>
                            </div>
                        </div>

                        <div id="dropzonePreview" class="hidden space-y-3">
                            <div class="relative mx-auto aspect-square w-full max-w-[220px] overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                <img id="imagePreview" src="" alt="Pratinjau foto daun" class="h-full w-full object-cover">
                                <button id="removeImageBtn"
                                        type="button"
                                        class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-slate-900/70 text-white transition-colors hover:bg-slate-900"
                                        aria-label="Hapus foto">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <p id="fileNamePreview" class="mx-auto max-w-[240px] truncate text-xs text-slate-500"></p>
                        </div>
                    </div>
                </div>

                {{-- Tab kamera --}}
                <div id="cameraTabContent" class="hidden space-y-3">
                    <div class="relative mx-auto aspect-square w-full max-w-sm overflow-hidden rounded-xl bg-slate-900 ring-1 ring-slate-200">
                        <video id="cameraVideo" autoplay playsinline class="h-full w-full object-cover"></video>
                        <canvas id="cameraCanvas" class="hidden"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="h-48 w-48 rounded-xl border border-dashed border-white/50"></div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button id="captureBtn"
                                type="button"
                                class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-800">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 0 1 2-2h.93a2 2 0 0 0 1.66-.89l.82-1.22A2 2 0 0 1 10.07 4h3.86a2 2 0 0 1 1.66.89l.82 1.22A2 2 0 0 0 18.07 7H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            Ambil foto
                        </button>
                        <button id="switchCameraBtn"
                                type="button"
                                class="flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                                aria-label="Ganti kamera">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V4m0 3h3M20 17v3m0-3h-3"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.07 9a7 7 0 0 1 11.54-2.6L20 10M4 14l3.39 3.6A7 7 0 0 0 18.93 15"/>
                            </svg>
                            Ganti
                        </button>
                    </div>
                </div>

                {{-- Pesan kesalahan --}}
                <div id="errorBanner" class="hidden items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                    </svg>
                    <span id="errorMessage">Terjadi kesalahan pada sistem.</span>
                </div>

                <button id="detectBtn"
                        type="button"
                        disabled
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                    <svg id="btnIcon" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                    </svg>
                    <svg id="btnSpinner" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.37 0 0 5.37 0 12h4Z"/>
                    </svg>
                    <span id="btnText">Deteksi penyakit</span>
                </button>

                <div class="flex items-start gap-2.5 rounded-lg bg-slate-50 p-3 text-xs leading-relaxed text-slate-600 ring-1 ring-inset ring-slate-200">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <p>
                        Gunakan satu helai daun dengan latar polos dan pencahayaan merata.
                        <a href="#panduan-foto" class="font-medium text-emerald-700 hover:text-emerald-800">Lihat panduan foto</a>
                    </p>
                </div>
            </div>
        </section>

        {{-- Kolom hasil --}}
        <section class="flex min-h-[420px] flex-col rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-7">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-heading text-base font-semibold text-slate-900">Hasil analisis</h2>
            </div>

            <div class="flex flex-1 flex-col p-5 sm:p-6">
                {{-- Kondisi awal --}}
                <div id="resultEmptyState" class="flex flex-1 flex-col items-center justify-center py-10 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 font-heading text-lg font-semibold text-slate-800">Belum ada hasil</h3>
                    <p class="mt-2 max-w-sm text-sm leading-relaxed text-slate-500">
                        Pilih foto daun di sebelah kiri, lalu tekan tombol deteksi untuk melihat hasil analisis.
                    </p>
                </div>

                {{-- Kondisi memuat --}}
                <div id="resultLoadingState" class="hidden flex-1 space-y-5 py-6">
                    <div class="flex items-center gap-4">
                        <div class="skeleton h-16 w-16"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton h-5 w-3/4"></div>
                            <div class="skeleton h-3 w-1/2"></div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="skeleton h-3 w-full"></div>
                        <div class="skeleton h-3 w-5/6"></div>
                    </div>
                    <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="skeleton h-4 w-1/3"></div>
                        <div class="skeleton h-3 w-full"></div>
                        <div class="skeleton h-3 w-4/5"></div>
                    </div>
                    <div class="flex items-center justify-center gap-2 text-xs font-medium text-slate-500">
                        <svg class="h-4 w-4 animate-spin text-emerald-700" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.37 0 0 5.37 0 12h4Z"/>
                        </svg>
                        Menganalisis foto...
                    </div>
                </div>

                {{-- Kondisi hasil --}}
                <div id="resultActiveState" class="hidden space-y-5">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                        <div class="min-w-0">
                            <span id="resBadge" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"></span>
                            <h3 id="resDiseaseName" class="mt-2 font-heading text-2xl font-bold text-slate-900">-</h3>
                            <p id="resDiseaseEn" class="mt-0.5 text-sm text-slate-500">-</p>
                        </div>
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                            <img id="resImage" src="" alt="Foto daun yang dianalisis" class="h-full w-full object-cover">
                        </div>
                    </div>

                    <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-slate-700">Tingkat keyakinan</span>
                            <span id="resConfidenceText" class="font-heading text-lg font-bold text-emerald-700">0%</span>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200">
                            <div id="resConfidenceBar" class="h-2.5 rounded-full bg-emerald-600 transition-all duration-700" style="width: 0%"></div>
                        </div>
                        <p id="resConfidenceCategory" class="text-xs text-slate-500"></p>
                    </div>

                    <div id="resGateWrap" class="hidden items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                        </svg>
                        <div>
                            <p id="resGateTitle" class="text-sm font-semibold"></p>
                            <p id="resGateMessage" class="mt-1 text-xs leading-relaxed"></p>
                        </div>
                    </div>

                    <div id="resAlternativesWrap" class="hidden space-y-3 rounded-xl border border-slate-200 p-4">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">Kemungkinan lain</h4>
                            <p class="mt-0.5 text-xs text-slate-500">Prediksi alternatif dengan gejala yang mirip.</p>
                        </div>
                        <div id="resAlternativesList" class="space-y-2"></div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">Tentang penyakit ini</h4>
                        <p id="resDescription" class="mt-2 rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-700">-</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4 text-xs text-slate-500">
                        <span>Dianalisis pada <span id="resDate" class="font-medium text-slate-600">-</span></span>
                        <a href="{{ route('history.index') }}" class="inline-flex items-center gap-1 font-medium text-emerald-700 hover:text-emerald-800">
                            Lihat riwayat
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5 5 5-5 5"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Panduan foto --}}
    <section id="panduan-foto" class="mt-10 scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-6 sm:px-8">
            <h2 class="font-heading text-xl font-bold text-slate-900 sm:text-2xl">Panduan foto</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">
                Kualitas foto memengaruhi hasil analisis. Ikuti empat panduan berikut agar gejala pada daun lebih mudah terbaca.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white p-5 sm:p-6">
                <span class="font-heading text-xs font-semibold tracking-wider text-emerald-700">01</span>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">Latar polos</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Letakkan daun di atas kertas putih atau permukaan netral tanpa objek lain.
                </p>
            </div>
            <div class="bg-white p-5 sm:p-6">
                <span class="font-heading text-xs font-semibold tracking-wider text-emerald-700">02</span>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">Satu helai daun</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Bentangkan satu daun menghadap kamera tanpa lipatan atau tumpukan.
                </p>
            </div>
            <div class="bg-white p-5 sm:p-6">
                <span class="font-heading text-xs font-semibold tracking-wider text-emerald-700">03</span>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">Cahaya merata</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Hindari bayangan tajam dan pantulan cahaya langsung pada permukaan daun.
                </p>
            </div>
            <div class="bg-white p-5 sm:p-6">
                <span class="font-heading text-xs font-semibold tracking-wider text-emerald-700">04</span>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">Fokus dan framing</h3>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    Pastikan seluruh daun terlihat tajam dan tidak terpotong di tepi foto.
                </p>
            </div>
        </div>

        <div class="border-t border-slate-200 px-5 py-4 text-xs text-slate-500 sm:px-8">
            Foto yang jelas dan terang membantu model membaca gejala dengan lebih baik.
        </div>
    </section>
</div>

<template id="alternativeTemplate">
    <div class="rounded-lg border border-slate-200 bg-white p-3">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <span data-rank class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-600"></span>
                <div class="min-w-0">
                    <p data-name class="truncate text-sm font-medium text-slate-800"></p>
                    <p data-name-en class="truncate text-xs text-slate-400"></p>
                </div>
            </div>
            <span data-confidence class="shrink-0 text-sm font-semibold text-slate-700"></span>
        </div>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
            <div data-bar class="h-full rounded-full transition-all duration-500"></div>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);

    const elements = {
        dropzone: $('dropzone'),
        fileInput: $('fileInput'),
        dropzoneIdle: $('dropzoneIdle'),
        dropzonePreview: $('dropzonePreview'),
        imagePreview: $('imagePreview'),
        fileNamePreview: $('fileNamePreview'),
        removeImageBtn: $('removeImageBtn'),
        detectBtn: $('detectBtn'),
        btnIcon: $('btnIcon'),
        btnSpinner: $('btnSpinner'),
        btnText: $('btnText'),
        errorBanner: $('errorBanner'),
        errorMessage: $('errorMessage'),
        emptyState: $('resultEmptyState'),
        loadingState: $('resultLoadingState'),
        activeState: $('resultActiveState'),
        badge: $('resBadge'),
        diseaseName: $('resDiseaseName'),
        diseaseEn: $('resDiseaseEn'),
        image: $('resImage'),
        confidenceText: $('resConfidenceText'),
        confidenceBar: $('resConfidenceBar'),
        confidenceCategory: $('resConfidenceCategory'),
        description: $('resDescription'),
        date: $('resDate'),
        gateWrap: $('resGateWrap'),
        gateTitle: $('resGateTitle'),
        gateMessage: $('resGateMessage'),
        alternativesWrap: $('resAlternativesWrap'),
        alternativesList: $('resAlternativesList'),
        tabUploadBtn: $('tabUploadBtn'),
        tabCameraBtn: $('tabCameraBtn'),
        uploadTabContent: $('uploadTabContent'),
        cameraTabContent: $('cameraTabContent'),
        cameraVideo: $('cameraVideo'),
        cameraCanvas: $('cameraCanvas'),
        captureBtn: $('captureBtn'),
        switchCameraBtn: $('switchCameraBtn'),
    };

    const colorThemes = {
        emerald: { badge: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', bar: 'bg-emerald-600' },
        orange: { badge: 'bg-orange-50 text-orange-700 ring-orange-600/20', bar: 'bg-orange-500' },
        amber: { badge: 'bg-amber-50 text-amber-800 ring-amber-600/20', bar: 'bg-amber-500' },
        red: { badge: 'bg-red-50 text-red-700 ring-red-600/20', bar: 'bg-red-600' },
        lime: { badge: 'bg-lime-50 text-lime-700 ring-lime-600/20', bar: 'bg-lime-600' },
        pink: { badge: 'bg-pink-50 text-pink-700 ring-pink-600/20', bar: 'bg-pink-500' },
        purple: { badge: 'bg-purple-50 text-purple-700 ring-purple-600/20', bar: 'bg-purple-600' },
        stone: { badge: 'bg-stone-100 text-stone-700 ring-stone-600/20', bar: 'bg-stone-600' },
        cyan: { badge: 'bg-cyan-50 text-cyan-700 ring-cyan-600/20', bar: 'bg-cyan-600' },
        yellow: { badge: 'bg-yellow-50 text-yellow-800 ring-yellow-600/20', bar: 'bg-yellow-500' },
    };

    const gateStyles = {
        amber: 'flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900',
        red: 'flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-900',
    };

    const gateReasonLabels = {
        blurry: 'Foto buram',
        too_dark: 'Foto terlalu gelap',
        too_bright: 'Foto terlalu terang',
        too_small: 'Resolusi foto kecil',
        low_confidence: 'Model kurang yakin',
        ambiguous: 'Gejala antar penyakit mirip',
    };

    let currentFile = null;
    let stream = null;
    let facingMode = 'environment';

    // ---------------------------------------------------------------------
    // Utilitas tampilan
    // ---------------------------------------------------------------------
    function showResultState(state) {
        elements.emptyState.classList.toggle('hidden', state !== 'empty');
        elements.loadingState.classList.toggle('hidden', state !== 'loading');
        elements.activeState.classList.toggle('hidden', state !== 'active');
    }

    function showError(message) {
        elements.errorMessage.textContent = message;
        elements.errorBanner.classList.remove('hidden');
        elements.errorBanner.classList.add('flex');
    }

    function hideError() {
        elements.errorBanner.classList.add('hidden');
        elements.errorBanner.classList.remove('flex');
    }

    function setLoading(isLoading) {
        elements.detectBtn.disabled = isLoading;
        elements.btnText.textContent = isLoading ? 'Menganalisis foto...' : 'Deteksi penyakit';
        elements.btnSpinner.classList.toggle('hidden', !isLoading);
        elements.btnIcon.classList.toggle('hidden', isLoading);
    }

    // ---------------------------------------------------------------------
    // Pemilihan file
    // ---------------------------------------------------------------------
    elements.dropzone.addEventListener('click', () => elements.fileInput.click());

    elements.dropzone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            elements.fileInput.click();
        }
    });

    elements.fileInput.addEventListener('change', (event) => {
        if (event.target.files.length > 0) {
            handleSelectedFile(event.target.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        elements.dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            elements.dropzone.classList.add('border-emerald-500', 'bg-emerald-50/50');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        elements.dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            elements.dropzone.classList.remove('border-emerald-500', 'bg-emerald-50/50');
        });
    });

    elements.dropzone.addEventListener('drop', (event) => {
        const files = event.dataTransfer?.files;

        if (files && files.length > 0) {
            handleSelectedFile(files[0]);
        }
    });

    elements.removeImageBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        resetFileInput();
    });

    function handleSelectedFile(file) {
        hideError();

        const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];

        if (!validTypes.includes(file.type)) {
            showError('Format file tidak didukung. Pilih gambar JPG atau PNG.');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            showError('Ukuran file terlalu besar. Maksimal 10 MB.');
            return;
        }

        currentFile = file;
        elements.fileNamePreview.textContent = file.name;

        const reader = new FileReader();
        reader.onload = (event) => {
            elements.imagePreview.src = event.target.result;
            elements.dropzoneIdle.classList.add('hidden');
            elements.dropzonePreview.classList.remove('hidden');
            elements.detectBtn.disabled = false;
        };
        reader.readAsDataURL(file);
    }

    function resetFileInput() {
        currentFile = null;
        elements.fileInput.value = '';
        elements.dropzonePreview.classList.add('hidden');
        elements.dropzoneIdle.classList.remove('hidden');
        elements.detectBtn.disabled = true;
    }

    // ---------------------------------------------------------------------
    // Proses deteksi
    // ---------------------------------------------------------------------
    elements.detectBtn.addEventListener('click', async () => {
        if (!currentFile) {
            return;
        }

        hideError();
        setLoading(true);
        showResultState('loading');

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
                body: formData,
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal melakukan deteksi penyakit.');
            }

            renderResult(result.data);
        } catch (error) {
            showResultState('empty');
            showError(error.message || 'Koneksi ke server gagal atau terjadi kesalahan.');
        } finally {
            setLoading(false);
        }
    });

    function renderResult(data) {
        showResultState('active');

        elements.diseaseName.textContent = data.disease;
        elements.diseaseEn.textContent = data.diseaseEn;
        elements.image.src = data.image;
        elements.confidenceText.textContent = `${data.confidence}%`;
        elements.confidenceBar.style.width = `${data.confidence}%`;
        elements.description.textContent = data.description;
        elements.date.textContent = data.date || '-';

        const theme = colorThemes[data.color] || colorThemes.emerald;
        elements.badge.className = `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${theme.badge}`;
        elements.badge.textContent = data.diseaseEn;
        elements.confidenceBar.className = `h-2.5 rounded-full transition-all duration-700 ${theme.bar}`;
        elements.confidenceCategory.textContent = getConfidenceLabel(data.confidence);

        renderGateWarning(data);
        renderAlternatives(data.alternatives || []);
    }

    function getConfidenceLabel(confidence) {
        if (confidence >= 90) {
            return 'Keyakinan tinggi';
        }

        if (confidence >= 70) {
            return 'Keyakinan sedang';
        }

        return 'Keyakinan rendah, pertimbangkan foto ulang';
    }

    function renderGateWarning(data) {
        if (data.accepted !== false) {
            elements.gateWrap.classList.add('hidden');
            return;
        }

        const isReject = data.gate_action === 'reject';
        elements.gateWrap.className = isReject ? gateStyles.red : gateStyles.amber;

        elements.gateTitle.textContent = isReject
            ? 'Foto belum dapat dianalisis dengan baik'
            : 'Hasil perlu diverifikasi';

        const reasons = (data.gate_reasons || [])
            .map((reason) => gateReasonLabels[reason] || reason)
            .join(', ');

        const threshold = data.gate_threshold != null
            ? ` Ambang keyakinan: ${(data.gate_threshold * 100).toFixed(0)}%.`
            : '';

        elements.gateMessage.textContent = (data.gate_message || 'Coba ambil foto ulang.')
            + (reasons ? ` (${reasons})` : '')
            + threshold;
    }

    function renderAlternatives(alternatives) {
        elements.alternativesList.replaceChildren();

        if (alternatives.length === 0) {
            elements.alternativesWrap.classList.add('hidden');
            return;
        }

        const template = $('alternativeTemplate');

        alternatives.slice(0, 3).forEach((alternative, index) => {
            const theme = colorThemes[alternative.color] || colorThemes.emerald;
            const item = template.content.cloneNode(true);

            item.querySelector('[data-rank]').textContent = String(index + 2);
            item.querySelector('[data-name]').textContent = alternative.disease;
            item.querySelector('[data-name-en]').textContent = alternative.diseaseEn;
            item.querySelector('[data-confidence]').textContent = `${alternative.confidence}%`;

            const bar = item.querySelector('[data-bar]');
            bar.className = `h-full rounded-full transition-all duration-500 ${theme.bar}`;
            bar.style.width = `${Math.max(alternative.confidence, 2)}%`;

            elements.alternativesList.appendChild(item);
        });

        elements.alternativesWrap.classList.remove('hidden');
    }

    // ---------------------------------------------------------------------
    // Mode kamera
    // ---------------------------------------------------------------------
    function setInputMode(mode) {
        const isUpload = mode === 'upload';

        elements.uploadTabContent.classList.toggle('hidden', !isUpload);
        elements.cameraTabContent.classList.toggle('hidden', isUpload);
        elements.tabUploadBtn.classList.toggle('is-active', isUpload);
        elements.tabCameraBtn.classList.toggle('is-active', !isUpload);
        elements.tabUploadBtn.setAttribute('aria-selected', String(isUpload));
        elements.tabCameraBtn.setAttribute('aria-selected', String(!isUpload));

        if (isUpload) {
            stopCamera();
        } else {
            startCamera();
        }
    }

    elements.tabUploadBtn.addEventListener('click', () => setInputMode('upload'));
    elements.tabCameraBtn.addEventListener('click', () => setInputMode('camera'));

    async function startCamera() {
        stopCamera();

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode,
                    width: { ideal: 1080 },
                    height: { ideal: 1080 },
                },
            });
            elements.cameraVideo.srcObject = stream;
        } catch (error) {
            showError('Tidak dapat mengakses kamera: ' + error.message);
        }
    }

    function stopCamera() {
        if (!stream) {
            return;
        }

        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }

    elements.switchCameraBtn.addEventListener('click', async () => {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        await startCamera();
    });

    elements.captureBtn.addEventListener('click', () => {
        if (!elements.cameraVideo.srcObject) {
            return;
        }

        const context = elements.cameraCanvas.getContext('2d');
        elements.cameraCanvas.width = elements.cameraVideo.videoWidth;
        elements.cameraCanvas.height = elements.cameraVideo.videoHeight;
        context.drawImage(elements.cameraVideo, 0, 0, elements.cameraCanvas.width, elements.cameraCanvas.height);

        elements.cameraCanvas.toBlob((blob) => {
            const capturedFile = new File([blob], 'camera_leaf.jpg', { type: 'image/jpeg' });
            stopCamera();
            setInputMode('upload');
            handleSelectedFile(capturedFile);
        }, 'image/jpeg', 0.95);
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Tentang Sistem — TomatCare')

@section('content')
<div class="mx-auto max-w-6xl space-y-8 px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
    <header class="max-w-2xl">
        <p class="text-sm font-semibold text-emerald-700">Tentang sistem</p>
        <h1 class="mt-2 font-heading text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
            Klasifikasi penyakit daun tomat
        </h1>
        <p class="mt-3 text-base leading-relaxed text-slate-600">
            TomatCare membantu mengenali gejala visual pada daun tomat menggunakan model visi komputer. Sistem ini dirancang sebagai alat bantu awal, bukan pengganti diagnosis ahli.
        </p>
    </header>

    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-4">
        <div class="bg-white p-5 sm:p-6">
            <p class="text-xs font-medium text-slate-500">Model</p>
            <p class="mt-1 font-heading text-xl font-bold text-slate-900 sm:text-2xl">EfficientNet-B0</p>
            <p class="mt-1 text-xs text-slate-500">Transfer learning</p>
        </div>
        <div class="bg-white p-5 sm:p-6">
            <p class="text-xs font-medium text-slate-500">Akurasi uji</p>
            <p class="mt-1 font-heading text-xl font-bold text-slate-900 sm:text-2xl">96,60%</p>
            <p class="mt-1 text-xs text-slate-500">2.000 citra uji</p>
        </div>
        <div class="bg-white p-5 sm:p-6">
            <p class="text-xs font-medium text-slate-500">Dataset</p>
            <p class="mt-1 font-heading text-xl font-bold text-slate-900 sm:text-2xl">20.000</p>
            <p class="mt-1 text-xs text-slate-500">2.000 citra per kelas</p>
        </div>
        <div class="bg-white p-5 sm:p-6">
            <p class="text-xs font-medium text-slate-500">Macro F1</p>
            <p class="mt-1 font-heading text-xl font-bold text-slate-900 sm:text-2xl">96,60%</p>
            <p class="mt-1 text-xs text-slate-500">Sepuluh kelas</p>
        </div>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="max-w-2xl">
            <h2 class="font-heading text-xl font-bold text-slate-900">Katalog penyakit</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                Sepuluh kelas yang dapat dikenali oleh sistem beserta ciri visual utamanya.
            </p>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($diseases as $disease)
                <article class="rounded-xl border border-slate-200 p-4 transition-colors hover:border-slate-300">
                    <x-disease-badge :color="$disease['color']" :label="$disease['name_en']" />
                    <h3 class="mt-3 font-heading text-base font-semibold text-slate-900">
                        {{ $disease['name_id'] }}
                    </h3>
                    <p class="mt-1 text-xs font-medium text-emerald-700">{{ $disease['pathogen'] }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $disease['visual'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900 sm:p-6">
        <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
        </svg>
        <div>
            <h2 class="text-sm font-semibold">Batasan penggunaan</h2>
            <p class="mt-1 text-sm leading-relaxed text-amber-800">
                Hasil analisis dipengaruhi oleh kualitas foto, pencahayaan, dan sudut pengambilan gambar. Untuk penanganan skala luas, konfirmasikan hasil dengan penyuluh pertanian atau ahli agronomi.
            </p>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Riwayat Deteksi — TomatCare')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-emerald-700">Arsip pemeriksaan</p>
            <h1 class="mt-2 font-heading text-3xl font-bold tracking-tight text-slate-900">Riwayat deteksi</h1>
            <p class="mt-2 text-sm text-slate-600">
                Hasil pemeriksaan tersimpan otomatis di peramban ini.
            </p>
        </div>

        <a href="{{ route('detect.index') }}"
           class="inline-flex w-fit items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-800">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
            </svg>
            Deteksi baru
        </a>
    </header>

    @if (empty($records))
        <div class="mx-auto mt-10 max-w-md rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.05 11a9 9 0 1 1 .5 4m-.5-4H7m-3.95 0L2 8"/>
                </svg>
            </div>
            <h2 class="mt-4 font-heading text-lg font-semibold text-slate-800">Belum ada riwayat</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Mulai pemeriksaan pertama Anda untuk melihat hasil deteksi di halaman ini.
            </p>
            <a href="{{ route('detect.index') }}"
               class="mt-5 inline-flex items-center justify-center rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-800">
                Mulai deteksi
            </a>
        </div>
    @else
        <div class="mt-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xs">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                    </svg>
                </span>
                <input id="searchInput"
                       type="search"
                       placeholder="Cari penyakit..."
                       class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between gap-3 sm:justify-end">
                <select id="filterType"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none">
                    <option value="all">Semua status</option>
                    <option value="healthy">Hanya sehat</option>
                    <option value="diseased">Hanya berpenyakit</option>
                </select>
                <span class="whitespace-nowrap text-xs text-slate-500">
                    <strong id="recordCountBadge" class="font-semibold text-slate-700">{{ count($records) }}</strong> hasil
                </span>
            </div>
        </div>

        <div id="recordsGrid" class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($records as $item)
                <article class="history-card group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                         data-id="{{ $item['id'] }}"
                         data-disease="{{ strtolower($item['disease'] . ' ' . $item['diseaseEn']) }}"
                         data-is-healthy="{{ $item['diseaseEn'] === 'Healthy' ? 'true' : 'false' }}">
                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                        <img src="{{ $item['image'] }}"
                             alt="Foto daun tomat"
                             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                             loading="lazy">
                        <div class="absolute left-3 top-3">
                            <x-disease-badge :color="$item['color']" :label="$item['diseaseEn']" />
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-heading text-base font-semibold leading-snug text-slate-900" title="{{ $item['disease'] }}">
                                {{ $item['disease'] }}
                            </h2>
                            <div class="shrink-0 text-right">
                                <p class="font-heading text-base font-bold text-slate-800">{{ $item['confidence'] }}%</p>
                                <p class="text-[10px] text-slate-400">keyakinan</p>
                            </div>
                        </div>

                        <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-500">
                            {{ $item['description'] }}
                        </p>

                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <span class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                {{ $item['date'] }}
                            </span>

                            <div class="flex items-center gap-3">
                                <button type="button"
                                        class="btn-detail text-xs font-medium text-emerald-700 transition-colors hover:text-emerald-800"
                                        data-record="{{ json_encode($item) }}">
                                    Detail
                                </button>
                                <button type="button"
                                        class="btn-delete text-slate-400 transition-colors hover:text-red-600"
                                        data-id="{{ $item['id'] }}"
                                        title="Hapus riwayat"
                                        aria-label="Hapus riwayat">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

{{-- Modal detail --}}
<div id="detailModal" class="hidden fixed inset-0 z-50 items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl">
        <button id="closeModalBtn"
                type="button"
                class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-slate-900/60 text-white transition-colors hover:bg-slate-900"
                aria-label="Tutup detail">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="h-56 w-full bg-slate-100">
            <img id="modalImage" src="" alt="Foto daun tomat" class="h-full w-full object-cover">
        </div>

        <div class="space-y-5 p-6">
            <div>
                <span id="modalBadge" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"></span>
                <h3 id="modalTitle" class="mt-2 font-heading text-2xl font-bold text-slate-900"></h3>
                <p id="modalTitleEn" class="mt-0.5 text-sm text-slate-500"></p>
            </div>

            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <span class="font-medium text-slate-700">Tingkat keyakinan</span>
                <span id="modalConfidence" class="font-heading text-lg font-bold text-emerald-700"></span>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-slate-800">Deskripsi</h4>
                <p id="modalDescription" class="mt-2 text-sm leading-relaxed text-slate-600"></p>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-4 text-xs text-slate-500">
                <span>Diperiksa pada <strong id="modalDate" class="font-medium text-slate-600"></strong></span>
                <button id="modalCloseAction"
                        type="button"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);

    const searchInput = $('searchInput');
    const filterType = $('filterType');
    const countBadge = $('recordCountBadge');
    let cards = Array.from(document.querySelectorAll('.history-card'));

    const modal = $('detailModal');
    const modalElements = {
        image: $('modalImage'),
        badge: $('modalBadge'),
        title: $('modalTitle'),
        titleEn: $('modalTitleEn'),
        confidence: $('modalConfidence'),
        description: $('modalDescription'),
        date: $('modalDate'),
    };

    const badgeThemes = {
        emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        red: 'bg-red-50 text-red-700 ring-red-600/20',
        orange: 'bg-orange-50 text-orange-700 ring-orange-600/20',
        amber: 'bg-amber-50 text-amber-800 ring-amber-600/20',
        lime: 'bg-lime-50 text-lime-700 ring-lime-600/20',
        pink: 'bg-pink-50 text-pink-700 ring-pink-600/20',
        purple: 'bg-purple-50 text-purple-700 ring-purple-600/20',
        stone: 'bg-stone-100 text-stone-700 ring-stone-600/20',
        cyan: 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
        yellow: 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
    };

    // ---------------------------------------------------------------------
    // Pencarian dan filter
    // ---------------------------------------------------------------------
    function filterCards() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const filter = filterType ? filterType.value : 'all';
        let visibleCount = 0;

        cards.forEach((card) => {
            const matchesQuery = (card.dataset.disease || '').includes(query);
            const isHealthy = card.dataset.isHealthy === 'true';
            const matchesFilter = filter === 'healthy'
                ? isHealthy
                : filter === 'diseased'
                    ? !isHealthy
                    : true;

            const isVisible = matchesQuery && matchesFilter;
            card.classList.toggle('hidden', !isVisible);

            if (isVisible) {
                visibleCount++;
            }
        });

        if (countBadge) {
            countBadge.textContent = String(visibleCount);
        }
    }

    searchInput?.addEventListener('input', filterCards);
    filterType?.addEventListener('change', filterCards);

    // ---------------------------------------------------------------------
    // Modal detail
    // ---------------------------------------------------------------------
    function openModal(data) {
        modalElements.image.src = data.image;
        modalElements.title.textContent = data.disease;
        modalElements.titleEn.textContent = data.diseaseEn;
        modalElements.confidence.textContent = `${data.confidence}%`;
        modalElements.description.textContent = data.description;
        modalElements.date.textContent = data.date;

        const theme = badgeThemes[data.color] || badgeThemes.emerald;
        modalElements.badge.className = `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${theme}`;
        modalElements.badge.textContent = data.diseaseEn;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.querySelectorAll('.btn-detail').forEach((button) => {
        button.addEventListener('click', () => {
            openModal(JSON.parse(button.dataset.record));
        });
    });

    $('closeModalBtn')?.addEventListener('click', closeModal);
    $('modalCloseAction')?.addEventListener('click', closeModal);

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // ---------------------------------------------------------------------
    // Hapus riwayat
    // ---------------------------------------------------------------------
    document.querySelectorAll('.btn-delete').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.stopPropagation();

            const id = button.dataset.id;

            if (!confirm('Yakin ingin menghapus catatan riwayat pemeriksaan ini?')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch(`/history/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });

                const result = await response.json();

                if (!result.success) {
                    alert('Gagal menghapus: ' + (result.message || 'Terjadi kesalahan'));
                    return;
                }

                const card = button.closest('.history-card');
                card.remove();
                cards = cards.filter((item) => item !== card);
                filterCards();
            } catch (error) {
                alert('Gagal menghubungi server: ' + error.message);
            }
        });
    });
});
</script>
@endpush

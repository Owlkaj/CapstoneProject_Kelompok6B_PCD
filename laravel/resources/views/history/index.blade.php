@extends('layouts.app')

@section('title', 'Riwayat Deteksi Sesi — TomatCare')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-200 pb-6 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold mb-2 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                Identitas Sesi Browser: <span class="font-mono">{{ substr($sessionId, 0, 8) }}...</span>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 font-heading">
                Riwayat Deteksi
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ url('/detect') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#2E7D32] hover:bg-[#1B5E20] text-white text-sm font-semibold shadow-xs transition-colors">
                <span>➕</span> Deteksi Baru
            </a>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs mb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
            <input type="text" id="searchInput" placeholder="Cari nama penyakit..." class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-emerald-600 focus:bg-white transition-colors">
            <span class="absolute left-3 top-2.5 text-gray-400 text-sm">🔍</span>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select id="filterType" class="w-full sm:w-48 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-emerald-600 font-medium text-gray-700">
                <option value="all">Semua Status</option>
                <option value="healthy">Hanya Sehat (Healthy)</option>
                <option value="diseased">Hanya Berpenyakit</option>
            </select>
            <span class="text-xs text-gray-400 font-medium whitespace-nowrap">
                Total: <strong id="recordCountBadge" class="text-gray-700">{{ count($records) }}</strong>
            </span>
        </div>
    </div>

    <!-- Content: Empty State vs Records Grid -->
    @if(empty($records))
    <div id="emptyView" class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-md mx-auto my-8 space-y-4">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-4xl">
            📋
        </div>
        <h3 class="text-xl font-bold text-gray-800 font-heading">
            Belum Ada Riwayat Deteksi
        </h3>
        <p class="text-sm text-gray-500 leading-relaxed">
            Anda belum melakukan pemeriksaan daun tomat pada sesi peramban ini. Unggah foto daun pertama Anda sekarang.
        </p>
        <a href="{{ url('/detect') }}" class="inline-block mt-2 px-6 py-2.5 rounded-xl bg-[#2E7D32] hover:bg-[#1B5E20] text-white text-sm font-semibold shadow-xs transition-colors">
            Mulai Deteksi Sekarang
        </a>
    </div>
    @else
    <div id="recordsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($records as $item)
        <div class="history-card bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col justify-between hover:shadow-md transition-shadow"
             data-id="{{ $item['id'] }}"
             data-disease="{{ strtolower($item['disease'] . ' ' . $item['diseaseEn']) }}"
             data-is-healthy="{{ $item['diseaseEn'] === 'Healthy' ? 'true' : 'false' }}">
            
            <div class="p-5 space-y-4">
                <!-- Top Row: Thumbnail + Badge -->
                <div class="flex items-start gap-4">
                    <div class="w-20 h-20 rounded-xl overflow-hidden border border-gray-200 flex-shrink-0 bg-gray-50">
                        <img src="{{ $item['image'] }}" alt="Foto Daun" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold mb-1
                            @if($item['diseaseEn'] === 'Healthy') bg-emerald-100 text-emerald-800 border border-emerald-300
                            @elseif($item['diseaseEn'] === 'Late Blight') bg-red-100 text-red-800 border border-red-300
                            @elseif($item['diseaseEn'] === 'Bacterial Spot') bg-orange-100 text-orange-800 border border-orange-300
                            @elseif($item['diseaseEn'] === 'Early Blight') bg-amber-100 text-amber-800 border border-amber-300
                            @elseif($item['diseaseEn'] === 'Leaf Mold') bg-lime-100 text-lime-800 border border-lime-300
                            @elseif($item['diseaseEn'] === 'Tomato Mosaic Virus') bg-pink-100 text-pink-800 border border-pink-300
                            @elseif($item['diseaseEn'] === 'Septoria Leaf Spot') bg-purple-100 text-purple-800 border border-purple-300
                            @elseif($item['diseaseEn'] === 'Spider Mites') bg-stone-200 text-stone-800 border border-stone-300
                            @elseif($item['diseaseEn'] === 'Target Spot') bg-cyan-100 text-cyan-800 border border-cyan-300
                            @else bg-yellow-100 text-yellow-800 border border-yellow-300
                            @endif">
                            {{ $item['diseaseEn'] }}
                        </span>
                        <h4 class="font-heading font-bold text-gray-900 text-base leading-snug truncate" title="{{ $item['disease'] }}">
                            {{ $item['disease'] }}
                        </h4>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                {{ $item['confidence'] }}% Yakin
                            </span>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed">
                    {{ $item['description'] }}
                </p>
            </div>

            <!-- Card Footer -->
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>🕒 {{ $item['date'] }}</span>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-detail font-semibold text-emerald-700 hover:text-emerald-900 transition-colors"
                            data-json="{{ json_encode($item) }}">
                        Detail
                    </button>
                    <span class="text-gray-300">•</span>
                    <button type="button" class="btn-delete text-red-500 hover:text-red-700 transition-colors"
                            data-id="{{ $item['id'] }}" title="Hapus catatan">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<!-- Modal Detail Riwayat -->
<div id="detailModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative bg-white rounded-3xl max-w-lg w-full border border-gray-200 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <!-- Close Button -->
        <button id="closeModalBtn" type="button" class="absolute top-4 right-4 z-10 bg-white/80 backdrop-blur rounded-full p-2 text-gray-600 hover:text-gray-900 hover:bg-white shadow-xs">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <!-- Full Image Preview -->
        <div class="w-full h-64 bg-gray-100 relative">
            <img id="modalImage" src="" alt="Citra Daun" class="w-full h-full object-cover">
            <div class="absolute bottom-3 left-3 bg-black/70 backdrop-blur px-3 py-1 rounded-lg text-white text-xs font-mono">
                Model: <span id="modalModel">EfficientNetB0</span>
            </div>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <span id="modalBadge" class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold mb-1"></span>
                <h3 id="modalTitle" class="text-2xl font-extrabold text-gray-900 font-heading"></h3>
                <p id="modalTitleEn" class="text-sm text-gray-500 italic"></p>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100 flex items-center justify-between text-sm">
                <span class="font-semibold text-gray-700">Tingkat Keyakinan:</span>
                <span id="modalConfidence" class="font-heading font-extrabold text-emerald-700 text-base"></span>
            </div>

            <div class="space-y-3 text-sm">
                <div>
                    <h5 class="font-bold text-gray-900 text-xs uppercase tracking-wider text-emerald-800">Gejala Visual:</h5>
                    <p id="modalDescription" class="text-gray-600 mt-1 leading-relaxed"></p>
                </div>
                <div>
                    <h5 class="font-bold text-gray-900 text-xs uppercase tracking-wider text-amber-800">Saran Penanganan:</h5>
                    <p id="modalTreatment" class="text-gray-600 mt-1 leading-relaxed bg-amber-50/50 p-3 rounded-xl border border-amber-100/60"></p>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400">
                <span>Diperiksa: <strong id="modalDate" class="text-gray-600"></strong></span>
                <button type="button" id="modalCloseAction" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const cards = document.querySelectorAll('.history-card');
    const recordCountBadge = document.getElementById('recordCountBadge');

    // Filter & Search Logic
    function filterCards() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const filter = filterType ? filterType.value : 'all';
        let visibleCount = 0;

        cards.forEach(card => {
            const disease = card.getAttribute('data-disease') || '';
            const isHealthy = card.getAttribute('data-is-healthy') === 'true';

            let matchesQuery = disease.includes(query);
            let matchesFilter = true;

            if (filter === 'healthy') {
                matchesFilter = isHealthy;
            } else if (filter === 'diseased') {
                matchesFilter = !isHealthy;
            }

            if (matchesQuery && matchesFilter) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (recordCountBadge) recordCountBadge.textContent = visibleCount;
    }

    searchInput?.addEventListener('input', filterCards);
    filterType?.addEventListener('change', filterCards);

    // Modal Interaction
    const modal = document.getElementById('detailModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const modalCloseAction = document.getElementById('modalCloseAction');

    const modalImage = document.getElementById('modalImage');
    const modalBadge = document.getElementById('modalBadge');
    const modalTitle = document.getElementById('modalTitle');
    const modalTitleEn = document.getElementById('modalTitleEn');
    const modalConfidence = document.getElementById('modalConfidence');
    const modalDescription = document.getElementById('modalDescription');
    const modalTreatment = document.getElementById('modalTreatment');
    const modalModel = document.getElementById('modalModel');
    const modalDate = document.getElementById('modalDate');

    document.querySelectorAll('.btn-detail').forEach(btn => {
        btn.addEventListener('click', () => {
            const data = JSON.parse(btn.getAttribute('data-json'));
            modalImage.src = data.image;
            modalTitle.textContent = data.disease;
            modalTitleEn.textContent = data.diseaseEn;
            modalConfidence.textContent = `${data.confidence}%`;
            modalDescription.textContent = data.description;
            modalTreatment.textContent = data.treatment;
            modalModel.textContent = data.model || 'EfficientNetB0';
            modalDate.textContent = data.date;

            modalBadge.textContent = data.diseaseEn;
            modalBadge.className = `inline-block px-2.5 py-0.5 rounded-full text-xs font-bold mb-1 ${data.diseaseEn === 'Healthy' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`;

            modal.classList.remove('hidden');
        });
    });

    const closeModal = () => modal.classList.add('hidden');
    closeModalBtn?.addEventListener('click', closeModal);
    modalCloseAction?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    // Delete Record
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const id = btn.getAttribute('data-id');
            if (!confirm('Yakin ingin menghapus catatan riwayat pemeriksaan ini?')) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const res = await fetch(`/history/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const json = await res.json();
                if (json.success) {
                    const card = btn.closest('.history-card');
                    card.remove();
                    filterCards();
                } else {
                    alert('Gagal menghapus: ' + (json.message || 'Terjadi kesalahan'));
                }
            } catch (err) {
                alert('Gagal menghubungi server: ' + err.message);
            }
        });
    });
});
</script>
@endpush

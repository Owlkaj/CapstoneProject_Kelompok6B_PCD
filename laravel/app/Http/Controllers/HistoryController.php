<?php

namespace App\Http\Controllers;

use App\Helpers\DiseaseCatalog;
use App\Services\SupabaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __construct(
        private readonly SupabaseService $supabaseService,
    ) {}

    /**
     * Tampilkan riwayat deteksi milik sesi peramban saat ini.
     */
    public function index(Request $request): View|JsonResponse
    {
        $sessionId = $request->attributes->get('tomato_session_id');

        if (! $sessionId) {
            return response()->json(['success' => false, 'message' => 'No session context.'], 400);
        }

        $records = $this->supabaseService->getRiwayatBySession($sessionId);
        $formatted = array_map(
            fn (array $row): array => $this->formatRecord($row),
            $records,
        );

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $formatted,
                'count' => count($formatted),
            ]);
        }

        return view('history.index', [
            'records' => $formatted,
        ]);
    }

    /**
     * Tampilkan detail satu riwayat deteksi.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $sessionId = $request->attributes->get('tomato_session_id');

        if (! $sessionId) {
            return response()->json(['success' => false, 'message' => 'No session context.'], 400);
        }

        $row = $this->supabaseService->getRiwayatDetail($sessionId, $id);

        if (! $row) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRecord($row),
        ]);
    }

    /**
     * Hapus satu riwayat deteksi.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $sessionId = $request->attributes->get('tomato_session_id');

        if (! $sessionId) {
            return response()->json(['success' => false, 'message' => 'No session context.'], 400);
        }

        $deleted = $this->supabaseService->deleteRiwayat($sessionId, $id);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Record deleted successfully.' : 'Failed to delete record.',
        ]);
    }

    /**
     * Ubah baris riwayat dari database menjadi payload untuk tampilan.
     *
     * @return array<string, mixed>
     */
    private function formatRecord(array $row): array
    {
        $diseaseName = $row['penyakit']['nama_penyakit'] ?? 'Unknown';
        $info = DiseaseCatalog::getInfo($diseaseName);
        $confidence = (float) ($row['confidence_score'] ?? 0);

        return [
            'id' => (string) $row['id_riwayat'],
            'image' => $row['citra_input'],
            'disease' => $info['name_id'],
            'diseaseEn' => $diseaseName,
            'confidence' => round($confidence * 100, 1),
            'description' => $info['description'],
            'date' => date('Y-m-d H:i', strtotime($row['tanggal_deteksi'])),
            'model' => $row['model_cnn']['nama_model'] ?? 'EfficientNetB0',
            'color' => $info['color'],
        ];
    }
}

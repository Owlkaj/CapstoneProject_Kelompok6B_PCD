<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SupabaseService;
use App\Helpers\DiseaseCatalog;

class HistoryController extends Controller
{
    protected SupabaseService $supabaseService;

    public function __construct(SupabaseService $supabaseService)
    {
        $this->supabaseService = $supabaseService;
    }

    /**
     * List detection history for current browser session
     */
    public function index(Request $request): \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
    {
        $sessionId = $request->attributes->get('tomato_session_id');

        if (!$sessionId) {
            return response()->json(['success' => false, 'message' => 'No session context.'], 400);
        }

        $records = $this->supabaseService->getRiwayatBySession($sessionId);

        $formatted = array_map(function ($row) {
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
        }, $records);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $formatted,
                'count' => count($formatted),
            ]);
        }

        return view('history.index', [
            'sessionId' => $sessionId,
            'records' => $formatted,
            'count' => count($formatted),
        ]);
    }

    /**
     * Get single detection record detail
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $sessionId = $request->attributes->get('tomato_session_id');

        if (!$sessionId) {
            return response()->json(['success' => false, 'message' => 'No session context.'], 400);
        }

        $row = $this->supabaseService->getRiwayatDetail($sessionId, $id);

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Record not found or unauthorized.'], 404);
        }

        $diseaseName = $row['penyakit']['nama_penyakit'] ?? 'Unknown';
        $info = DiseaseCatalog::getInfo($diseaseName);
        $confidence = (float) ($row['confidence_score'] ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $row['id_riwayat'],
                'image' => $row['citra_input'],
                'disease' => $info['name_id'],
                'diseaseEn' => $diseaseName,
                'confidence' => round($confidence * 100, 1),
                'description' => $info['description'],
                'date' => date('Y-m-d H:i', strtotime($row['tanggal_deteksi'])),
                'model' => $row['model_cnn']['nama_model'] ?? 'EfficientNetB0',
                'color' => $info['color'],
            ]
        ]);
    }

    /**
     * Delete history record
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $sessionId = $request->attributes->get('tomato_session_id');

        if (!$sessionId) {
            return response()->json(['success' => false, 'message' => 'No session context.'], 400);
        }

        $deleted = $this->supabaseService->deleteRiwayat($sessionId, $id);

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Record deleted successfully.' : 'Failed to delete record.'
        ]);
    }
}

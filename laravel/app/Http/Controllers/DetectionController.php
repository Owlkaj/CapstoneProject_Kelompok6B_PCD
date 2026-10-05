<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FastApiService;
use App\Services\SupabaseService;
use App\Helpers\DiseaseCatalog;

class DetectionController extends Controller
{
    protected FastApiService $fastApiService;
    protected SupabaseService $supabaseService;

    public function __construct(FastApiService $fastApiService, SupabaseService $supabaseService)
    {
        $this->fastApiService = $fastApiService;
        $this->supabaseService = $supabaseService;
    }

    /**
     * Show main TomatCare dashboard
     */
    public function index(Request $request)
    {
        $sessionId = $request->attributes->get('tomato_session_id');
        return view('detection.index', [
            'sessionId' => $sessionId,
        ]);
    }

    /**
     * Perform detection inference and record history
     */
    public function predict(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        $file = $request->file('image');
        $sessionId = $request->attributes->get('tomato_session_id');

        if (!$sessionId) {
            return response()->json(['message' => 'Invalid session context.'], 400);
        }

        try {
            // 1. Call FastAPI REST endpoint
            $prediction = $this->fastApiService->predict($file);
            $className = $prediction['class'];
            $confidence = (float) $prediction['confidence'];
            $inferenceTimeMs = (float) $prediction['inference_time_ms'];

            // 2. Upload image to Supabase Storage bucket detection-images/{sessionId}/{uuid}.jpg
            $publicUrl = $this->supabaseService->uploadImage($file, $sessionId);

            // 3. Lookup disease in master penyakit table
            $penyakit = $this->supabaseService->getPenyakitByNama($className);
            if (!$penyakit || !isset($penyakit['id_penyakit'])) {
                \Log::error("Disease lookup failed in master table for class: '{$className}'");
                throw new \RuntimeException("Data penyakit '{$className}' tidak ditemukan dalam master data sistem.", 422);
            }
            $idPenyakit = (int) $penyakit['id_penyakit'];

            // 4. Lookup active model in master model_cnn table
            $activeModel = $this->supabaseService->getActiveModel();
            if (!$activeModel || !isset($activeModel['id_model'])) {
                \Log::error("Active CNN model lookup failed in master model_cnn table.");
                throw new \RuntimeException("Model CNN aktif tidak ditemukan dalam konfigurasi sistem.", 500);
            }
            $idModel = (int) $activeModel['id_model'];
            $modelName = $activeModel['nama_model'] ?? 'EfficientNetB0';

            // 5. Insert history record into riwayat_deteksi
            $riwayat = $this->supabaseService->createRiwayat(
                sessionId: $sessionId,
                idPenyakit: $idPenyakit,
                idModel: $idModel,
                citraInput: $publicUrl,
                confidenceScore: $confidence
            );

            // 6. Enrich with Indonesian agronomy advice from catalog
            $info = DiseaseCatalog::getInfo($className);

            return response()->json([
                'success' => true,
                'data' => [
                    'id_riwayat' => $riwayat['id_riwayat'] ?? null,
                    'disease' => $info['name_id'],
                    'diseaseEn' => $info['name_en'],
                    'confidence' => round($confidence * 100, 2),
                    'confidence_raw' => $confidence,
                    'inference_time_ms' => $inferenceTimeMs,
                    'image' => $publicUrl,
                    'description' => $info['description'],
                    'treatment' => $info['treatment'],
                    'color' => $info['color'],
                    'model' => $modelName,
                    'date' => now()->format('Y-m-d H:i'),
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error("Prediction pipeline error: " . $e->getMessage(), [
                'exception' => $e
            ]);

            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? (int) $e->getCode() : 500;
            $clientMessage = config('app.debug')
                ? $e->getMessage()
                : ($statusCode < 500 ? $e->getMessage() : 'Terjadi kendala saat memproses deteksi. Silakan coba beberapa saat lagi.');

            return response()->json([
                'success' => false,
                'message' => $clientMessage
            ], $statusCode);
        }
    }
}

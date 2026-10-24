<?php

namespace App\Http\Controllers;

use App\Helpers\DiseaseCatalog;
use App\Services\FastApiService;
use App\Services\SupabaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DetectionController extends Controller
{
    public function __construct(
        private readonly FastApiService $fastApiService,
        private readonly SupabaseService $supabaseService,
    ) {}

    /**
     * Tampilkan halaman utama deteksi.
     */
    public function index(): View
    {
        return view('detection.index');
    }

    /**
     * Jalankan inferensi dan simpan hasil deteksi ke riwayat.
     */
    public function predict(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        $sessionId = $request->attributes->get('tomato_session_id');

        if (! $sessionId) {
            return response()->json(['message' => 'Invalid session context.'], 400);
        }

        try {
            // 1. Inferensi melalui microservice FastAPI.
            $prediction = $this->fastApiService->predict($request->file('image'));
            $className = $prediction['class'];
            $confidence = (float) $prediction['confidence'];
            $inferenceTimeMs = (float) $prediction['inference_time_ms'];

            // 2. Unggah citra ke Supabase Storage.
            $publicUrl = $this->supabaseService->uploadImage($request->file('image'), $sessionId);

            // 3. Validasi penyakit dan model aktif pada master data.
            $diseaseId = $this->resolveDiseaseId($className);
            $activeModel = $this->resolveActiveModel();

            // 4. Simpan riwayat deteksi.
            $riwayat = $this->supabaseService->createRiwayat(
                sessionId: $sessionId,
                idPenyakit: $diseaseId,
                idModel: $activeModel['id'],
                citraInput: $publicUrl,
                confidenceScore: $confidence,
            );

            // 5. Susun hasil diagnosis untuk tampilan.
            $info = DiseaseCatalog::getInfo($className);
            $topPredictions = $this->buildTopPredictions($prediction, $info);
            $gate = is_array($prediction['confidence_gate'] ?? null)
                ? $prediction['confidence_gate']
                : [];

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
                    'color' => $info['color'],
                    'model' => $activeModel['name'],
                    'date' => now()->format('Y-m-d H:i'),
                    'top_predictions' => $topPredictions,
                    'alternatives' => array_slice($topPredictions, 1),
                    'accepted' => (bool) ($gate['passed'] ?? true),
                    'gate_action' => $gate['action'] ?? 'accept',
                    'gate_reasons' => $gate['reasons'] ?? [],
                    'gate_message' => $gate['message'] ?? null,
                    'gate_threshold' => $gate['threshold'] ?? null,
                    'gate_quality' => $gate['quality'] ?? null,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Prediction pipeline error: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600)
                ? (int) $e->getCode()
                : 500;

            $clientMessage = config('app.debug')
                ? $e->getMessage()
                : ($statusCode < 500
                    ? $e->getMessage()
                    : 'Terjadi kendala saat memproses deteksi. Silakan coba beberapa saat lagi.');

            return response()->json([
                'success' => false,
                'message' => $clientMessage,
            ], $statusCode);
        }
    }

    /**
     * Cari ID penyakit pada master data berdasarkan nama kelas model.
     */
    private function resolveDiseaseId(string $className): int
    {
        $penyakit = $this->supabaseService->getPenyakitByNama($className);

        if (! $penyakit || ! isset($penyakit['id_penyakit'])) {
            Log::error("Disease lookup failed in master table for class: '{$className}'");
            throw new RuntimeException(
                "Data penyakit '{$className}' tidak ditemukan dalam master data sistem.",
                422,
            );
        }

        return (int) $penyakit['id_penyakit'];
    }

    /**
     * Ambil model CNN yang sedang aktif.
     *
     * @return array{id: int, name: string}
     */
    private function resolveActiveModel(): array
    {
        $model = $this->supabaseService->getActiveModel();

        if (! $model || ! isset($model['id_model'])) {
            Log::error('Active CNN model lookup failed in master model_cnn table.');
            throw new RuntimeException(
                'Model CNN aktif tidak ditemukan dalam konfigurasi sistem.',
                500,
            );
        }

        return [
            'id' => (int) $model['id_model'],
            'name' => $model['nama_model'] ?? 'EfficientNetB0',
        ];
    }

    /**
     * Susun daftar prediksi teratas untuk diagnosis banding.
     *
     * Prioritas sumber data:
     * 1. top_predictions dari FastAPI
     * 2. probabilities mentah dari FastAPI
     * 3. hasil prediksi utama saja
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTopPredictions(array $prediction, array $primaryInfo): array
    {
        if (! empty($prediction['top_predictions']) && is_array($prediction['top_predictions'])) {
            return array_map(function (array $item): array {
                $info = DiseaseCatalog::getInfo($item['class']);
                $confidence = (float) $item['confidence'];

                return [
                    'disease' => $info['name_id'],
                    'diseaseEn' => $item['class'],
                    'class_id' => $item['class_id'] ?? null,
                    'confidence' => round($confidence * 100, 2),
                    'confidence_raw' => $confidence,
                    'color' => $info['color'] ?? 'emerald',
                    'description' => $info['description'] ?? '',
                ];
            }, $prediction['top_predictions']);
        }

        if (! empty($prediction['probabilities']) && is_array($prediction['probabilities'])) {
            $probabilities = $prediction['probabilities'];
            arsort($probabilities);

            $predictions = [];

            foreach (array_slice($probabilities, 0, 5, true) as $className => $probability) {
                $info = DiseaseCatalog::getInfo($className);
                $confidence = (float) $probability;

                $predictions[] = [
                    'disease' => $info['name_id'],
                    'diseaseEn' => $className,
                    'confidence' => round($confidence * 100, 2),
                    'confidence_raw' => $confidence,
                    'color' => $info['color'] ?? 'emerald',
                    'description' => $info['description'] ?? '',
                ];
            }

            return $predictions;
        }

        $confidence = (float) $prediction['confidence'];

        return [[
            'disease' => $primaryInfo['name_id'],
            'diseaseEn' => $prediction['class'],
            'class_id' => $prediction['class_id'] ?? null,
            'confidence' => round($confidence * 100, 2),
            'confidence_raw' => $confidence,
            'color' => $primaryInfo['color'],
            'description' => $primaryInfo['description'],
        ]];
    }
}

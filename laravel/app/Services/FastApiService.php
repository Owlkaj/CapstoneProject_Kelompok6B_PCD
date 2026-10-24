<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FastApiService
{
    /**
     * Field wajib pada respons /predict.
     */
    private const REQUIRED_RESPONSE_FIELDS = [
        'class',
        'confidence',
        'class_id',
        'inference_time_ms',
    ];

    private string $baseUrl;

    private int $connectTimeout;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8001'), '/');
        $this->connectTimeout = (int) config('services.fastapi.connect_timeout', 5);
        $this->timeout = (int) config('services.fastapi.timeout', 60);
    }

    /**
     * Periksa status dan kesiapan model pada layanan inferensi.
     */
    public function checkHealth(): array
    {
        try {
            $response = Http::timeout($this->connectTimeout)->get("{$this->baseUrl}/health");

            return [
                'available' => $response->successful(),
                'status' => $response->json('status', 'unknown'),
                'model_loaded' => $response->json('model_loaded', false),
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'status' => 'offline',
                'model_loaded' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim citra ke endpoint /predict.
     *
     * @return array{class: string, confidence: float, class_id: int, inference_time_ms: float}
     */
    public function predict(UploadedFile $file): array
    {
        $endpoint = "{$this->baseUrl}/predict";

        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->attach(
                    'image',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName(),
                )
                ->post($endpoint);

            if ($response->successful()) {
                $data = $response->json();

                foreach (self::REQUIRED_RESPONSE_FIELDS as $field) {
                    if (! isset($data[$field])) {
                        throw new RuntimeException(
                            'FastAPI response violates contract specification.',
                        );
                    }
                }

                return $data;
            }

            $statusCode = $response->status();
            $errorDetail = $response->json('detail') ?? $response->body();
            Log::error("FastAPI error [{$statusCode}]: {$errorDetail}");

            throw new RuntimeException("Inference service error: {$errorDetail}", $statusCode);
        } catch (ConnectionException $e) {
            Log::error(
                "Failed to connect to FastAPI service at {$endpoint}: ".$e->getMessage(),
            );

            throw new RuntimeException(
                'Inference service connection timed out or unreachable.',
                503,
            );
        }
    }
}

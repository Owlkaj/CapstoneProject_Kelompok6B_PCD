<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class FastApiService
{
    protected string $baseUrl;
    protected int $connectTimeout;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8001'), '/');
        $this->connectTimeout = (int) config('services.fastapi.connect_timeout', 5);
        $this->timeout = (int) config('services.fastapi.timeout', 60);
    }

    /**
     * Check health of FastAPI service
     */
    public function checkHealth(): array
    {
        try {
            $response = Http::timeout($this->connectTimeout)->get("{$this->baseUrl}/health");
            return [
                'available' => $response->successful(),
                'status' => $response->json('status', 'unknown'),
                'model_loaded' => $response->json('model_loaded', false)
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'status' => 'offline',
                'model_loaded' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sends image to FastAPI /predict
     * 
     * @return array [class, confidence, class_id, inference_time_ms]
     */
    public function predict(UploadedFile $file): array
    {
        $endpoint = "{$this->baseUrl}/predict";

        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->attach('image', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                
                // Validate 4 contract fields
                if (!isset($data['class'], $data['confidence'], $data['class_id'], $data['inference_time_ms'])) {
                    throw new RuntimeException("FastAPI response violates contract specification.");
                }

                return $data;
            }

            $statusCode = $response->status();
            $errorDetail = $response->json('detail') ?? $response->body();
            \Log::error("FastAPI error [{$statusCode}]: {$errorDetail}");

            throw new RuntimeException("Inference service error: {$errorDetail}", $statusCode);
        } catch (ConnectionException $e) {
            \Log::error("Failed to connect to FastAPI service at {$endpoint}: " . $e->getMessage());
            throw new RuntimeException("Inference service connection timed out or unreachable.", 503);
        }
    }
}

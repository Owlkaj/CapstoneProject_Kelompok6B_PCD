<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseService
{
    protected string $url;
    protected string $anonKey;
    protected string $bucket;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url'), '/');
        $this->anonKey = (string) config('services.supabase.anon_key');
        $this->bucket = (string) config('services.supabase.bucket', 'detection-images');
    }

    /**
     * Standard headers for Supabase API requests
     */
    protected function headers(): array
    {
        return [
            'apikey' => $this->anonKey,
            'Authorization' => "Bearer {$this->anonKey}",
        ];
    }

    /**
     * Upsert session into user_sessions table
     */
    public function upsertSession(string $sessionId): void
    {
        $endpoint = "{$this->url}/rest/v1/user_sessions";
        
        Http::withHeaders(array_merge($this->headers(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'resolution=merge-duplicates',
        ]))->post($endpoint, [
            'session_id' => $sessionId,
            'last_accessed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Upload image to Supabase Storage bucket detection-images/{sessionId}/{uuid}.jpg
     * Returns Public URL
     */
    public function uploadImage(UploadedFile $file, string $sessionId): string
    {
        $fileUuid = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $path = "{$sessionId}/{$fileUuid}.{$extension}";

        $uploadEndpoint = "{$this->url}/storage/v1/object/{$this->bucket}/{$path}";
        $mimeType = $file->getMimeType() ?: 'image/jpeg';

        $response = Http::withHeaders(array_merge($this->headers(), [
            'Content-Type' => $mimeType,
        ]))->withBody(file_get_contents($file->getRealPath()), $mimeType)
          ->post($uploadEndpoint);

        if (!$response->successful()) {
            \Log::error("Supabase Storage upload failed: " . $response->body());
            throw new RuntimeException("Failed to upload image to storage.");
        }

        // Form public URL as per PRD Bab 23.3
        return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$path}";
    }

    /**
     * Retrieve penyakit record by name
     */
    public function getPenyakitByNama(string $nama): ?array
    {
        $endpoint = "{$this->url}/rest/v1/penyakit?nama_penyakit=eq." . urlencode($nama) . "&select=*";
        $response = Http::withHeaders($this->headers())->get($endpoint);

        if ($response->successful()) {
            $data = $response->json();
            return !empty($data) ? $data[0] : null;
        }

        return null;
    }

    /**
     * Retrieve active model metadata from model_cnn
     */
    public function getActiveModel(): ?array
    {
        $endpoint = "{$this->url}/rest/v1/model_cnn?status=eq.active&select=*&limit=1";
        $response = Http::withHeaders($this->headers())->get($endpoint);

        if ($response->successful()) {
            $data = $response->json();
            return !empty($data) ? $data[0] : null;
        }

        return null;
    }

    /**
     * Insert new riwayat_deteksi record
     */
    public function createRiwayat(
        string $sessionId,
        int $idPenyakit,
        int $idModel,
        string $citraInput,
        float $confidenceScore
    ): array {
        $endpoint = "{$this->url}/rest/v1/riwayat_deteksi";

        $payload = [
            'session_id' => $sessionId,
            'id_penyakit' => $idPenyakit,
            'id_model' => $idModel,
            'citra_input' => $citraInput,
            'confidence_score' => $confidenceScore,
            'tanggal_deteksi' => now()->toIso8601String(),
        ];

        $response = Http::withHeaders(array_merge($this->headers(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->post($endpoint, $payload);

        if ($response->successful()) {
            $result = $response->json();
            return !empty($result) ? $result[0] : $payload;
        }

        \Log::error("Failed to insert riwayat_deteksi: " . $response->body());
        throw new RuntimeException("Failed to record detection history.");
    }

    /**
     * Query detection history for active session with joined tables
     */
    public function getRiwayatBySession(string $sessionId): array
    {
        $endpoint = "{$this->url}/rest/v1/riwayat_deteksi?" . http_build_query([
            'session_id' => 'eq.' . $sessionId,
            'select' => 'id_riwayat,citra_input,confidence_score,tanggal_deteksi,penyakit(id_penyakit,nama_penyakit,deskripsi),model_cnn(id_model,nama_model,arsitektur)',
            'order' => 'tanggal_deteksi.desc',
        ]);

        $response = Http::withHeaders($this->headers())->get($endpoint);

        if ($response->successful()) {
            return $response->json();
        }

        \Log::error("Failed to fetch riwayat for session {$sessionId}: " . $response->body());
        return [];
    }

    /**
     * Query single history record strictly verified by session
     */
    public function getRiwayatDetail(string $sessionId, int $idRiwayat): ?array
    {
        $endpoint = "{$this->url}/rest/v1/riwayat_deteksi?" . http_build_query([
            'session_id' => 'eq.' . $sessionId,
            'id_riwayat' => 'eq.' . $idRiwayat,
            'select' => 'id_riwayat,citra_input,confidence_score,tanggal_deteksi,penyakit(id_penyakit,nama_penyakit,deskripsi),model_cnn(id_model,nama_model,arsitektur)',
            'limit' => 1,
        ]);

        $response = Http::withHeaders($this->headers())->get($endpoint);

        if ($response->successful()) {
            $data = $response->json();
            return !empty($data) ? $data[0] : null;
        }

        return null;
    }

    /**
     * Delete detection record strictly verified by session
     */
    public function deleteRiwayat(string $sessionId, int $idRiwayat): bool
    {
        $endpoint = "{$this->url}/rest/v1/riwayat_deteksi?" . http_build_query([
            'session_id' => 'eq.' . $sessionId,
            'id_riwayat' => 'eq.' . $idRiwayat,
        ]);

        $response = Http::withHeaders($this->headers())->delete($endpoint);
        return $response->successful();
    }
}

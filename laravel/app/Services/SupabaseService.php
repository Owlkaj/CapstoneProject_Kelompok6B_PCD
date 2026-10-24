<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SupabaseService
{
    private const RIWAYAT_COLUMNS = 'id_riwayat,citra_input,confidence_score,tanggal_deteksi,'
        .'penyakit(id_penyakit,nama_penyakit,deskripsi),'
        .'model_cnn(id_model,nama_model,arsitektur)';

    private string $url;

    private string $anonKey;

    private string $bucket;

    public function __construct()
    {
        $this->url = rtrim((string) config('services.supabase.url'), '/');
        $this->anonKey = (string) config('services.supabase.anon_key');
        $this->bucket = (string) config('services.supabase.bucket', 'detection-images');
    }

    /**
     * Simpan atau perbarui sesi peramban.
     */
    public function upsertSession(string $sessionId): void
    {
        $this->http([
            'Content-Type' => 'application/json',
            'Prefer' => 'resolution=merge-duplicates',
        ])->post("{$this->url}/rest/v1/user_sessions", [
            'session_id' => $sessionId,
            'last_accessed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Unggah citra ke bucket penyimpanan dan kembalikan URL publiknya.
     */
    public function uploadImage(UploadedFile $file, string $sessionId): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $path = "{$sessionId}/".Str::uuid().".{$extension}";
        $mimeType = $file->getMimeType() ?: 'image/jpeg';

        $response = $this->http(['Content-Type' => $mimeType])
            ->withBody(file_get_contents($file->getRealPath()), $mimeType)
            ->post("{$this->url}/storage/v1/object/{$this->bucket}/{$path}");

        if (! $response->successful()) {
            \Log::error('Supabase Storage upload failed: '.$response->body());
            throw new RuntimeException('Failed to upload image to storage.');
        }

        return "{$this->url}/storage/v1/object/public/{$this->bucket}/{$path}";
    }

    /**
     * Cari data penyakit berdasarkan nama kelas model.
     */
    public function getPenyakitByNama(string $nama): ?array
    {
        $response = $this->http()->get(
            "{$this->url}/rest/v1/penyakit?nama_penyakit=eq.".urlencode($nama).'&select=*',
        );

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return ! empty($data) ? $data[0] : null;
    }

    /**
     * Ambil metadata model CNN yang berstatus aktif.
     */
    public function getActiveModel(): ?array
    {
        $response = $this->http()->get(
            "{$this->url}/rest/v1/model_cnn?status=eq.active&select=*&limit=1",
        );

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return ! empty($data) ? $data[0] : null;
    }

    /**
     * Simpan satu baris riwayat deteksi.
     */
    public function createRiwayat(
        string $sessionId,
        int $idPenyakit,
        int $idModel,
        string $citraInput,
        float $confidenceScore,
    ): array {
        $payload = [
            'session_id' => $sessionId,
            'id_penyakit' => $idPenyakit,
            'id_model' => $idModel,
            'citra_input' => $citraInput,
            'confidence_score' => $confidenceScore,
            'tanggal_deteksi' => now()->toIso8601String(),
        ];

        $response = $this->http([
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->post("{$this->url}/rest/v1/riwayat_deteksi", $payload);

        if (! $response->successful()) {
            \Log::error('Failed to insert riwayat_deteksi: '.$response->body());
            throw new RuntimeException('Failed to record detection history.');
        }

        $result = $response->json();

        return ! empty($result) ? $result[0] : $payload;
    }

    /**
     * Ambil seluruh riwayat deteksi untuk satu sesi.
     */
    public function getRiwayatBySession(string $sessionId): array
    {
        $response = $this->http()->get(
            "{$this->url}/rest/v1/riwayat_deteksi?".http_build_query([
                'session_id' => 'eq.'.$sessionId,
                'select' => self::RIWAYAT_COLUMNS,
                'order' => 'tanggal_deteksi.desc',
            ]),
        );

        if (! $response->successful()) {
            \Log::error("Failed to fetch riwayat for session {$sessionId}: ".$response->body());

            return [];
        }

        return $response->json();
    }

    /**
     * Ambil satu riwayat deteksi yang dipastikan milik sesi terkait.
     */
    public function getRiwayatDetail(string $sessionId, int $idRiwayat): ?array
    {
        $response = $this->http()->get(
            "{$this->url}/rest/v1/riwayat_deteksi?".http_build_query([
                'session_id' => 'eq.'.$sessionId,
                'id_riwayat' => 'eq.'.$idRiwayat,
                'select' => self::RIWAYAT_COLUMNS,
                'limit' => 1,
            ]),
        );

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return ! empty($data) ? $data[0] : null;
    }

    /**
     * Hapus satu riwayat deteksi milik sesi terkait.
     */
    public function deleteRiwayat(string $sessionId, int $idRiwayat): bool
    {
        $response = $this->http()->delete(
            "{$this->url}/rest/v1/riwayat_deteksi?".http_build_query([
                'session_id' => 'eq.'.$sessionId,
                'id_riwayat' => 'eq.'.$idRiwayat,
            ]),
        );

        return $response->successful();
    }

    /**
     * Header dasar untuk seluruh permintaan ke Supabase.
     */
    private function headers(): array
    {
        return [
            'apikey' => $this->anonKey,
            'Authorization' => "Bearer {$this->anonKey}",
        ];
    }

    /**
     * Buat HTTP client Supabase dengan header tambahan bila diperlukan.
     */
    private function http(array $headers = []): PendingRequest
    {
        return Http::withHeaders(array_merge($this->headers(), $headers));
    }
}

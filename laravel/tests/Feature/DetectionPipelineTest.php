<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\FastApiService;
use App\Services\SupabaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Mockery;

class DetectionPipelineTest extends TestCase
{
    protected string $sessionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionId = (string) Str::uuid();
    }

    protected function createFakeImage(): UploadedFile
    {
        // 1x1 valid transparent PNG without requiring PHP-GD extension
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $tempPath = tempnam(sys_get_temp_dir(), 'leaf_') . '.png';
        file_put_contents($tempPath, base64_decode($pngBase64));

        return new UploadedFile(
            path: $tempPath,
            originalName: 'test_leaf.png',
            mimeType: 'image/png',
            error: null,
            test: true
        );
    }

    public function test_rejects_empty_or_missing_image(): void
    {
        $response = $this->withUnencryptedCookie('tomato_session_id', $this->sessionId)
            ->postJson('/detect', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_rejects_non_image_file(): void
    {
        $file = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        $response = $this->withUnencryptedCookie('tomato_session_id', $this->sessionId)
            ->postJson('/detect', ['image' => $file]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_successful_detection_pipeline_with_exact_contract(): void
    {
        $file = $this->createFakeImage();

        // 1. Mock FastAPI Service returning valid 4-field contract (with integer class_id)
        $mockFastApi = Mockery::mock(FastApiService::class);
        $mockFastApi->shouldReceive('predict')
            ->once()
            ->andReturn([
                'class' => 'Bacterial Spot',
                'confidence' => 0.9876,
                'class_id' => 0,
                'inference_time_ms' => 24.5,
            ]);
        $this->app->instance(FastApiService::class, $mockFastApi);

        // 2. Mock Supabase Service
        $mockSupabase = Mockery::mock(SupabaseService::class);
        $mockSupabase->shouldReceive('upsertSession')->byDefault()->andReturnNull();
        $mockSupabase->shouldReceive('uploadImage')
            ->once()
            ->andReturn('https://supabase.co/storage/v1/object/public/detection-images/test.jpg');
        $mockSupabase->shouldReceive('getPenyakitByNama')
            ->with('Bacterial Spot')
            ->once()
            ->andReturn([
                'id_penyakit' => 1,
                'nama_penyakit' => 'Bacterial Spot',
                'deskripsi' => 'Infeksi bakteri Xanthomonas',
            ]);
        $mockSupabase->shouldReceive('getActiveModel')
            ->once()
            ->andReturn([
                'id_model' => 1,
                'nama_model' => 'EfficientNetB0_V3',
                'arsitektur' => 'EfficientNetB0',
            ]);
        $mockSupabase->shouldReceive('createRiwayat')
            ->once()
            ->withArgs(function ($sessionId, $idPenyakit, $idModel, $citraInput, $confidenceScore) {
                return $sessionId === $this->sessionId
                    && $idPenyakit === 1
                    && $idModel === 1
                    && $confidenceScore === 0.9876;
            })
            ->andReturn([
                'id_riwayat' => 101,
            ]);
        $this->app->instance(SupabaseService::class, $mockSupabase);

        // 3. Perform request
        $response = $this->withUnencryptedCookie('tomato_session_id', $this->sessionId)
            ->post('/detect', ['image' => $file]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id_riwayat' => 101,
                'disease' => 'Bercak Bakteri (Bacterial Spot)',
                'diseaseEn' => 'Bacterial Spot',
                'confidence' => 98.76,
                'model' => 'EfficientNetB0_V3',
            ]
        ]);
    }

    public function test_disease_lookup_failure_throws_422_without_silent_id1_fallback(): void
    {
        $file = $this->createFakeImage();

        // FastAPI detects a disease that is missing from master database table
        $mockFastApi = Mockery::mock(FastApiService::class);
        $mockFastApi->shouldReceive('predict')
            ->once()
            ->andReturn([
                'class' => 'NonExistentDiseaseClass',
                'confidence' => 0.95,
                'class_id' => 99,
                'inference_time_ms' => 30.0,
            ]);
        $this->app->instance(FastApiService::class, $mockFastApi);

        $mockSupabase = Mockery::mock(SupabaseService::class);
        $mockSupabase->shouldReceive('upsertSession')->byDefault()->andReturnNull();
        $mockSupabase->shouldReceive('uploadImage')
            ->once()
            ->andReturn('https://supabase.co/storage/v1/object/public/detection-images/test.jpg');
        // Return null simulating unmapped disease name
        $mockSupabase->shouldReceive('getPenyakitByNama')
            ->with('NonExistentDiseaseClass')
            ->once()
            ->andReturnNull();

        // CRITICAL ASSERTION: createRiwayat MUST NEVER be called with false fallback id 1!
        $mockSupabase->shouldNotReceive('createRiwayat');

        $this->app->instance(SupabaseService::class, $mockSupabase);

        $response = $this->withUnencryptedCookie('tomato_session_id', $this->sessionId)
            ->post('/detect', ['image' => $file]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Data penyakit', $response->json('message'));
    }

    public function test_active_model_missing_fails_cleanly_without_corrupting_history(): void
    {
        $file = $this->createFakeImage();

        $mockFastApi = Mockery::mock(FastApiService::class);
        $mockFastApi->shouldReceive('predict')
            ->once()
            ->andReturn([
                'class' => 'Healthy',
                'confidence' => 0.99,
                'class_id' => 2,
                'inference_time_ms' => 25.0,
            ]);
        $this->app->instance(FastApiService::class, $mockFastApi);

        $mockSupabase = Mockery::mock(SupabaseService::class);
        $mockSupabase->shouldReceive('upsertSession')->byDefault()->andReturnNull();
        $mockSupabase->shouldReceive('uploadImage')->once()->andReturn('https://supabase.co/img.jpg');
        $mockSupabase->shouldReceive('getPenyakitByNama')->once()->andReturn(['id_penyakit' => 3]);
        // Simulate no active model configured in DB
        $mockSupabase->shouldReceive('getActiveModel')->once()->andReturnNull();

        // createRiwayat MUST NOT be called!
        $mockSupabase->shouldNotReceive('createRiwayat');

        $this->app->instance(SupabaseService::class, $mockSupabase);

        $response = $this->withUnencryptedCookie('tomato_session_id', $this->sessionId)
            ->post('/detect', ['image' => $file]);

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
    }
}

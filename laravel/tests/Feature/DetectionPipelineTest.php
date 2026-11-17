<?php

namespace Tests\Feature;

use App\Helpers\DiseaseCatalog;
use App\Services\FastApiService;
use App\Services\SupabaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

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

    public function test_all_catalog_classes_have_complete_visual_explanation_metadata(): void
    {
        $catalog = DiseaseCatalog::all();
        $this->assertCount(10, $catalog);

        foreach ($catalog as $className => $entry) {
            $this->assertNotEmpty($entry['visual'], "Missing visual for {$className}");
            $this->assertNotEmpty($entry['model_cue'], "Missing model_cue for {$className}");
            $this->assertNotEmpty($entry['symptom_group'], "Missing symptom_group for {$className}");
            $this->assertNotEmpty($entry['key_differentiator'], "Missing key_differentiator for {$className}");
        }

        // Pastikan fallback untuk kelas yang tidak dikenal tetap lengkap
        $unknown = DiseaseCatalog::getInfo('Unknown Future Disease');
        $this->assertNotEmpty($unknown['visual']);
        $this->assertNotEmpty($unknown['model_cue']);
        $this->assertNotEmpty($unknown['symptom_group']);
        $this->assertNotEmpty($unknown['key_differentiator']);
    }

    public function test_all_45_pairwise_combinations_and_top5_produce_complete_explanations(): void
    {
        $classes = array_keys(DiseaseCatalog::all());
        $pairCount = 0;

        for ($i = 0; $i < count($classes); $i++) {
            for ($j = $i + 1; $j < count($classes); $j++) {
                $pairCount++;
                $remaining = array_values(array_diff($classes, [$classes[$i], $classes[$j]]));
                $top5 = [
                    ['diseaseEn' => $classes[$i], 'confidence' => 42.50],
                    ['diseaseEn' => $classes[$j], 'confidence' => 33.50],
                    ['diseaseEn' => $remaining[0], 'confidence' => 11.00],
                    ['diseaseEn' => $remaining[1], 'confidence' => 8.00],
                    ['diseaseEn' => $remaining[2], 'confidence' => 5.00],
                ];

                $analysis = DiseaseCatalog::analyzeTopPredictions($top5, [
                    'reasons' => ['ambiguous', 'blurry'],
                ]);

                $this->assertSame('duel_top2', $analysis['status']);
                $this->assertTrue($analysis['is_fierce']);
                $this->assertNotEmpty($analysis['headline']);
                $this->assertNotEmpty($analysis['pairwise_reason']);
                $this->assertNotEmpty($analysis['cluster_reason']);
                $this->assertNotEmpty($analysis['quality_factor']);
                $this->assertNotEmpty($analysis['top5_chain_reasons']);
                $this->assertCount(3, $analysis['top5_chain_reasons']);
                $this->assertArrayHasKey('m15', $analysis['step_margins']);
                $this->assertCount(5, $analysis['candidates']);

                foreach ($analysis['candidates'] as $candidate) {
                    $this->assertNotEmpty($candidate['role_label']);
                    $this->assertNotEmpty($candidate['gap_badge']);
                    $this->assertNotEmpty($candidate['why_predicted']);
                    $this->assertNotEmpty($candidate['overlap_with_prev']);
                    $this->assertNotEmpty($candidate['differentiator']);
                }
            }
        }

        $this->assertSame(45, $pairCount);
    }

    public function test_all_seven_competition_distribution_conditions_are_covered(): void
    {
        $scenarios = [
            'single_only' => [
                'expected_fierce' => false,
                'expected_chain_count' => 1,
                'items' => [
                    ['diseaseEn' => 'Early Blight', 'confidence' => 91.0],
                ],
                'gate' => [],
            ],
            'dominant' => [
                'expected_fierce' => false,
                'expected_chain_count' => 3,
                'items' => [
                    ['diseaseEn' => 'Healthy', 'confidence' => 94.0],
                    ['diseaseEn' => 'Leaf Mold', 'confidence' => 2.5],
                    ['diseaseEn' => 'Early Blight', 'confidence' => 1.5],
                    ['diseaseEn' => 'Target Spot', 'confidence' => 1.2],
                    ['diseaseEn' => 'Bacterial Spot', 'confidence' => 0.8],
                ],
                'gate' => ['reasons' => []],
            ],
            'lead_with_fierce_tail' => [
                'expected_fierce' => true,
                'expected_chain_count' => 3,
                'items' => [
                    ['diseaseEn' => 'Healthy', 'confidence' => 65.0],
                    ['diseaseEn' => 'Early Blight', 'confidence' => 11.0],
                    ['diseaseEn' => 'Target Spot', 'confidence' => 10.0],
                    ['diseaseEn' => 'Septoria Leaf Spot', 'confidence' => 8.0],
                    ['diseaseEn' => 'Bacterial Spot', 'confidence' => 6.0],
                ],
                'gate' => ['reasons' => ['blurry']],
            ],
            'clear_lead' => [
                'expected_fierce' => false,
                'expected_chain_count' => 3,
                'items' => [
                    ['diseaseEn' => 'Early Blight', 'confidence' => 68.0],
                    ['diseaseEn' => 'Target Spot', 'confidence' => 22.0],
                    ['diseaseEn' => 'Septoria Leaf Spot', 'confidence' => 5.0],
                    ['diseaseEn' => 'Late Blight', 'confidence' => 3.0],
                    ['diseaseEn' => 'Bacterial Spot', 'confidence' => 2.0],
                ],
                'gate' => ['reasons' => ['too_dark']],
            ],
            'duel_top2' => [
                'expected_fierce' => true,
                'expected_chain_count' => 3,
                'items' => [
                    ['diseaseEn' => 'Leaf Mold', 'confidence' => 46.0],
                    ['diseaseEn' => 'Tomato Yellow Leaf Curl Virus (TYLCV)', 'confidence' => 38.0],
                    ['diseaseEn' => 'Tomato Mosaic Virus', 'confidence' => 9.0],
                    ['diseaseEn' => 'Spider Mites', 'confidence' => 4.0],
                    ['diseaseEn' => 'Bacterial Spot', 'confidence' => 3.0],
                ],
                'gate' => ['reasons' => ['too_bright', 'ambiguous']],
            ],
            'fierce_top3' => [
                'expected_fierce' => true,
                'expected_chain_count' => 3,
                'items' => [
                    ['diseaseEn' => 'Early Blight', 'confidence' => 39.0],
                    ['diseaseEn' => 'Target Spot', 'confidence' => 31.0],
                    ['diseaseEn' => 'Septoria Leaf Spot', 'confidence' => 22.0],
                    ['diseaseEn' => 'Late Blight', 'confidence' => 5.0],
                    ['diseaseEn' => 'Bacterial Spot', 'confidence' => 3.0],
                ],
                'gate' => ['reasons' => ['low_confidence', 'too_small']],
            ],
            'fierce_top5' => [
                'expected_fierce' => true,
                'expected_chain_count' => 3,
                'items' => [
                    ['diseaseEn' => 'Target Spot', 'confidence' => 26.0],
                    ['diseaseEn' => 'Early Blight', 'confidence' => 23.0],
                    ['diseaseEn' => 'Septoria Leaf Spot', 'confidence' => 20.0],
                    ['diseaseEn' => 'Late Blight', 'confidence' => 17.0],
                    ['diseaseEn' => 'Bacterial Spot', 'confidence' => 14.0],
                ],
                'gate' => ['reasons' => ['unknown_custom_flag']],
            ],
        ];

        foreach ($scenarios as $expectedStatus => $scenario) {
            $analysis = DiseaseCatalog::analyzeTopPredictions($scenario['items'], $scenario['gate']);

            $this->assertSame($expectedStatus, $analysis['status'], "Failed asserting status {$expectedStatus}");
            $this->assertSame($scenario['expected_fierce'], $analysis['is_fierce']);
            $this->assertNotEmpty($analysis['badge_label']);
            $this->assertNotEmpty($analysis['headline']);
            $this->assertNotEmpty($analysis['pairwise_reason']);
            $this->assertNotEmpty($analysis['cluster_reason']);
            $this->assertNotEmpty($analysis['quality_factor']);
            $this->assertCount($scenario['expected_chain_count'], $analysis['top5_chain_reasons']);
            $this->assertArrayHasKey('m15', $analysis['step_margins']);
            $this->assertCount(count($scenario['items']), $analysis['candidates']);

            foreach ($analysis['candidates'] as $candidate) {
                $this->assertNotEmpty($candidate['gap_badge']);
                $this->assertNotEmpty($candidate['overlap_with_prev']);
                $this->assertNotEmpty($candidate['why_predicted']);
                $this->assertNotEmpty($candidate['differentiator']);
            }
        }
    }

    public function test_detect_endpoint_returns_top5_competition_analysis_payload(): void
    {
        $file = $this->createFakeImage();

        $mockFastApi = Mockery::mock(FastApiService::class);
        $mockFastApi->shouldReceive('predict')
            ->once()
            ->andReturn([
                'class' => 'Early Blight',
                'confidence' => 0.44,
                'class_id' => 1,
                'inference_time_ms' => 31.2,
                'top_predictions' => [
                    ['class' => 'Early Blight', 'class_id' => 1, 'confidence' => 0.44],
                    ['class' => 'Target Spot', 'class_id' => 8, 'confidence' => 0.36],
                    ['class' => 'Septoria Leaf Spot', 'class_id' => 6, 'confidence' => 0.11],
                    ['class' => 'Late Blight', 'class_id' => 3, 'confidence' => 0.05],
                    ['class' => 'Bacterial Spot', 'class_id' => 0, 'confidence' => 0.04],
                ],
                'confidence_gate' => [
                    'passed' => false,
                    'action' => 'warn',
                    'reasons' => ['low_confidence', 'ambiguous'],
                    'message' => 'Selisih antar kelas terlalu rapat.',
                    'threshold' => 0.60,
                ],
            ]);
        $this->app->instance(FastApiService::class, $mockFastApi);

        $mockSupabase = Mockery::mock(SupabaseService::class);
        $mockSupabase->shouldReceive('upsertSession')->byDefault()->andReturnNull();
        $mockSupabase->shouldReceive('uploadImage')->once()->andReturn('https://supabase.co/storage/v1/object/public/detection-images/duel.jpg');
        $mockSupabase->shouldReceive('getPenyakitByNama')->with('Early Blight')->once()->andReturn(['id_penyakit' => 2]);
        $mockSupabase->shouldReceive('getActiveModel')->once()->andReturn(['id_model' => 1, 'nama_model' => 'EfficientNetB0_V3']);
        $mockSupabase->shouldReceive('createRiwayat')->once()->andReturn(['id_riwayat' => 202]);
        $this->app->instance(SupabaseService::class, $mockSupabase);

        $response = $this->withUnencryptedCookie('tomato_session_id', $this->sessionId)
            ->post('/detect', ['image' => $file]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.competition_analysis.status', 'duel_top2');
        $response->assertJsonPath('data.competition_analysis.is_fierce', true);
        $response->assertJsonCount(3, 'data.competition_analysis.top5_chain_reasons');
        $response->assertJsonCount(5, 'data.competition_analysis.candidates');
        $this->assertNotEmpty($response->json('data.competition_analysis.headline'));
        $this->assertNotEmpty($response->json('data.competition_analysis.pairwise_reason'));
        $this->assertNotEmpty($response->json('data.competition_analysis.cluster_reason'));
        $this->assertNotEmpty($response->json('data.competition_analysis.quality_factor'));
    }
}

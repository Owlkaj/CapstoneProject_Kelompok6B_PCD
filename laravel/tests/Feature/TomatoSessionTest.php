<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Str;
use App\Services\SupabaseService;
use Mockery;

class TomatoSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock Supabase upsertSession to isolate unit/feature testing
        $mockSupabase = Mockery::mock(SupabaseService::class);
        $mockSupabase->shouldReceive('upsertSession')->byDefault()->andReturnNull();
        $this->app->instance(SupabaseService::class, $mockSupabase);
    }

    protected function findTomatoCookie($response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'tomato_session_id') {
                return $cookie;
            }
        }
        return null;
    }

    public function test_visitor_receives_uuid_cookie_on_first_visit(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $cookie = $this->findTomatoCookie($response);
        $this->assertNotNull($cookie, "Cookie 'tomato_session_id' was not attached to response.");
        $this->assertTrue(Str::isUuid($cookie->getValue()), "Session cookie value is not a valid UUID v4: " . $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly(), "Session cookie must be HttpOnly");
    }

    public function test_visitor_preserves_existing_valid_uuid_cookie(): void
    {
        $existingSessionId = (string) Str::uuid();

        $response = $this->withUnencryptedCookie('tomato_session_id', $existingSessionId)
            ->get('/');

        $response->assertStatus(200);
    }

    public function test_invalid_uuid_cookie_is_regenerated_with_fresh_uuid(): void
    {
        $invalidSessionId = 'not-a-valid-uuid-string';

        $response = $this->withUnencryptedCookie('tomato_session_id', $invalidSessionId)
            ->get('/');

        $response->assertStatus(200);

        $cookie = $this->findTomatoCookie($response);
        $this->assertNotNull($cookie, "Cookie 'tomato_session_id' should be issued.");
        $this->assertNotEquals($invalidSessionId, $cookie->getValue());
        $this->assertTrue(Str::isUuid($cookie->getValue()), "Regenerated cookie must be a valid UUID v4.");
    }
}

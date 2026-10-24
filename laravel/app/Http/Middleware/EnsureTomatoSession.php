<?php

namespace App\Http\Middleware;

use App\Services\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureTomatoSession
{
    private const COOKIE_NAME = 'tomato_session_id';

    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 30; // 30 hari

    public function __construct(
        private readonly SupabaseService $supabase,
    ) {}

    /**
     * Pastikan setiap pengunjung memiliki identitas sesi pseudonim.
     */
    public function handle(Request $request, Closure $next): Response
    {
        [$sessionId, $isNew] = $this->resolveSessionId($request);

        $request->attributes->set('tomato_session_id', $sessionId);

        try {
            $this->supabase->upsertSession($sessionId);
        } catch (\Throwable $e) {
            Log::warning('Failed to upsert Supabase user_sessions: '.$e->getMessage());
        }

        $response = $next($request);

        if ($isNew || ! $request->hasCookie(self::COOKIE_NAME)) {
            $response->cookie($this->buildSessionCookie($request, $sessionId));
        }

        return $response;
    }

    /**
     * Ambil UUID yang valid dari cookie, atau terbitkan UUID baru.
     *
     * @return array{0: string, 1: bool}
     */
    private function resolveSessionId(Request $request): array
    {
        $sessionId = $request->cookie(self::COOKIE_NAME);

        if ($sessionId && Str::isUuid($sessionId)) {
            return [$sessionId, false];
        }

        return [(string) Str::uuid(), true];
    }

    /**
     * Buat cookie sesi berumur 30 hari.
     */
    private function buildSessionCookie(Request $request, string $sessionId): Cookie
    {
        return cookie(
            name: self::COOKIE_NAME,
            value: $sessionId,
            minutes: self::COOKIE_LIFETIME_MINUTES,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'Lax',
        );
    }
}

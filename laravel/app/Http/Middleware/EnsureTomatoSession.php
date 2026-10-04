<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SupabaseService;

class EnsureTomatoSession
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = 'tomato_session_id';
        $sessionId = $request->cookie($cookieName);

        // Validate UUID format, if absent or invalid, generate new UUID v4
        $isNew = false;
        if (!$sessionId || !Str::isUuid($sessionId)) {
            $sessionId = (string) Str::uuid();
            $isNew = true;
        }

        // Set session ID in request attributes so controllers can safely access server context
        $request->attributes->set('tomato_session_id', $sessionId);

        // Upsert session in Supabase user_sessions table
        try {
            $this->supabase->upsertSession($sessionId);
        } catch (\Throwable $e) {
            \Log::warning("Failed to upsert Supabase user_sessions: " . $e->getMessage());
        }

        /** @var Response $response */
        $response = $next($request);

        // Attach cookie to response (30 days lifetime, HttpOnly=true, SameSite=Lax)
        if ($isNew || !$request->hasCookie($cookieName)) {
            $response->cookie(
                cookie(
                    name: $cookieName,
                    value: $sessionId,
                    minutes: 60 * 24 * 30, // 30 days
                    path: '/',
                    domain: null,
                    secure: $request->isSecure(),
                    httpOnly: true,
                    raw: false,
                    sameSite: 'Lax'
                )
            );
        }

        return $response;
    }
}

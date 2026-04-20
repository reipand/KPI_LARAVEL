<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $isLocal = app()->environment('local', 'development');

        $scriptSrc = ["'self'"];
        $styleSrc = ["'self'", 'https://fonts.googleapis.com'];
        $fontSrc = ["'self'", 'https://fonts.gstatic.com', 'data:'];
        $imgSrc = ["'self'", 'data:', 'blob:'];
        $connectSrc = ["'self'"];

        $pusherCluster = config('broadcasting.connections.pusher.options.cluster', 'mt1');
        $pusherKey = config('broadcasting.connections.pusher.key');

        // Pusher WebSocket & API endpoints
        $connectSrc[] = "wss://ws-{$pusherCluster}.pusher.com";
        $connectSrc[] = "https://sockjs-{$pusherCluster}.pusher.com";
        $connectSrc[] = "https://stats.pusher.com";
        if ($pusherKey) {
            $connectSrc[] = "wss://ws-{$pusherCluster}.pusher.com/app/{$pusherKey}";
        }

        if ($isLocal) {
            $scriptSrc[] = "'unsafe-inline'";
            $scriptSrc[] = 'http://localhost:5173';
            $scriptSrc[] = 'http://127.0.0.1:5173';

            $styleSrc[] = "'unsafe-inline'";
            $styleSrc[] = 'http://localhost:5173';
            $styleSrc[] = 'http://127.0.0.1:5173';

            $fontSrc[] = 'http://localhost:5173';
            $fontSrc[] = 'http://127.0.0.1:5173';

            $connectSrc[] = 'http://localhost:5173';
            $connectSrc[] = 'http://127.0.0.1:5173';
            $connectSrc[] = 'ws://localhost:5173';
            $connectSrc[] = 'ws://127.0.0.1:5173';
            $connectSrc[] = 'http://127.0.0.1:8000';
            $connectSrc[] = 'ws://127.0.0.1:8000';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            'base-uri \'self\'',
            'frame-ancestors \'none\'',
            'object-src \'none\'',
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            'font-src '.implode(' ', $fontSrc),
            'img-src '.implode(' ', $imgSrc),
            'connect-src '.implode(' ', $connectSrc),
        ]);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}

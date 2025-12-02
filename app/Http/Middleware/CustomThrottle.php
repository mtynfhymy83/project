<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CustomThrottle
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limit = '60'): Response
    {
        // استخراج تعداد درخواست از پارامتر (مثلاً: 60:1 = 60 درخواست در 1 دقیقه)
        [$maxAttempts, $decayMinutes] = explode(':', $limit . ':1', 2);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        // کلید rate limit بر اساس user یا IP
        $key = $this->resolveRequestSignature($request);

        // بررسی تعداد درخواست‌ها
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // اضافه کردن headers
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * تولید کلید یکتا برای rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return 'throttle:user:' . $user->id . ':' . $request->path();
        }

        return 'throttle:ip:' . $request->ip() . ':' . $request->path();
    }

    /**
     * محاسبه تعداد درخواست‌های باقی‌مانده
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - RateLimiter::attempts($key));
    }

    /**
     * ساخت response برای حالت rate limit exceeded
     */
    protected function buildRateLimitResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'تعداد درخواست‌های شما از حد مجاز گذشته است',
            'retry_after' => $retryAfter,
        ], 429)
            ->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
    }

    /**
     * اضافه کردن rate limit headers به response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
    }
}

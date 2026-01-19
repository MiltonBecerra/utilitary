<?php

namespace App\Modules\Utilities\SupermarketComparator\Services;

use Illuminate\Support\Facades\Cache;

class RequestGuard
{
    public function allowRequest(string $storeCode): bool
    {
        $limit = (int) config('services.supermarket_comparator.rate_limit_per_minute', 12);
        if ($limit <= 0) {
            return true;
        }

        $window = now()->format('YmdHi'); // por minuto
        $key = "smc:rl:{$storeCode}:{$window}";

        try {
            if (!Cache::has($key)) {
                Cache::put($key, 0, now()->addMinutes(2));
            }
            $count = Cache::increment($key);
            return $count <= $limit;
        } catch (\Throwable $e) {
            // Best-effort: si el driver no soporta increment, no bloqueamos.
            return true;
        }
    }

    public function isCircuitOpen(string $storeCode): bool
    {
        $key = $this->openUntilKey($storeCode);
        $until = Cache::get($key);
        if (!is_string($until) || trim($until) === '') {
            return false;
        }

        try {
            return now()->lessThan(\Carbon\Carbon::parse($until));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function onSuccess(string $storeCode): void
    {
        Cache::forget($this->failsKey($storeCode));
        Cache::forget($this->openUntilKey($storeCode));
    }

    public function onFailure(string $storeCode, string $message): void
    {
        $threshold = (int) config('services.supermarket_comparator.circuit_failure_threshold', 3);
        $cooldownMin = (int) config('services.supermarket_comparator.circuit_cooldown_minutes', 10);
        if ($threshold <= 0 || $cooldownMin <= 0) {
            return;
        }

        $failsKey = $this->failsKey($storeCode);
        $fails = 0;
        try {
            if (!Cache::has($failsKey)) {
                Cache::put($failsKey, 0, now()->addMinutes(max(5, $cooldownMin)));
            }
            $fails = (int) Cache::increment($failsKey);
        } catch (\Throwable $e) {
            return;
        }

        if ($fails < $threshold) {
            return;
        }

        if (!$this->isSevereFailure($message)) {
            return;
        }

        Cache::put($this->openUntilKey($storeCode), now()->addMinutes($cooldownMin)->toDateTimeString(), now()->addMinutes($cooldownMin + 5));
    }

    public function backoffSleepIfNeeded(string $message): void
    {
        // Backoff pequeño para 429/503 típicos.
        $m = mb_strtolower($message);
        if (str_contains($m, 'http 429') || str_contains($m, 'too many') || str_contains($m, 'rate')) {
            usleep(600000); // 0.6s
        } elseif (str_contains($m, 'http 503') || str_contains($m, 'no healthy upstream')) {
            usleep(400000); // 0.4s
        }
    }

    private function isSevereFailure(string $message): bool
    {
        $m = mb_strtolower($message);
        return str_contains($m, 'http 429')
            || str_contains($m, 'http 403')
            || str_contains($m, 'access denied')
            || str_contains($m, 'captcha')
            || str_contains($m, 'no healthy upstream');
    }

    private function failsKey(string $storeCode): string
    {
        return "smc:cb:{$storeCode}:fails";
    }

    private function openUntilKey(string $storeCode): string
    {
        return "smc:cb:{$storeCode}:open_until";
    }
}



<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Store;

use Illuminate\Cache\Repository as Cache;

class CacheCircuitBreakerStore implements CircuitBreakerStoreInterface
{
    const KEY_BASE = 'circuit_breaker_';

    /** @var Cache */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function isAvailable(string $identifier): bool
    {
        return !($this->cache->has(self::KEY_BASE . $identifier . '_failed'));
    }

    public function reportFailure(string $identifier, int $attemptsThreshold, int $attemptsTtl, int $failureTtl): void
    {
        $key = self::KEY_BASE . $identifier . '_remaining_attempts';

        if (!$this->cache->has($key)) {
            $this->cache->set($key, $attemptsThreshold, $attemptsTtl);
            return;
        }

        $remainingAttempts = $this->cache->decrement($key);
        if ($remainingAttempts === 0) {
            $this->cache->set(self::KEY_BASE . $identifier . '_failed', true, $failureTtl);
        }
    }

    public function reportSuccess(string $identifier): void
    {
        $this->cache->forget(self::KEY_BASE . $identifier . '_remaining_attempts');
        $this->cache->forget(self::KEY_BASE . $identifier . '_failed');
    }
}

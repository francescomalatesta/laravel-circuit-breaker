<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Store;

use Illuminate\Cache\Repository as Cache;

class CacheCircuitBreakerStore implements CircuitBreakerStoreInterface
{
    const KEY_BASE = 'circuit_breaker_';

    const ATTEMPTS_THRESHOLD = 3;
    const ATTEMPTS_TTL = 500;
    const FAILURE_TTL = 5000;

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

    public function reportFailure(string $identifier): void
    {
        $key = self::KEY_BASE . $identifier . '_remaining_attempts';

        if(!$this->cache->has($key)) {
            $this->cache->set($key, self::ATTEMPTS_THRESHOLD, self::ATTEMPTS_TTL);
            return;
        }

        $remainingAttempts = $this->cache->decrement($key);
        if($remainingAttempts === 0) {
            $this->cache->set(self::KEY_BASE . $identifier . '_failed', true, self::FAILURE_TTL);
        }
    }

    public function reportSuccess(string $identifier): void
    {
        $this->cache->forget(self::KEY_BASE . $identifier . '_remaining_attempts');
        $this->cache->forget(self::KEY_BASE . $identifier . '_failed');
    }
}

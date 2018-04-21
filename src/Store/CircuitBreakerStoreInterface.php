<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Store;

interface CircuitBreakerStoreInterface
{
    public function isAvailable(string $identifier) : bool;
    public function reportFailure(string $identifier, int $attemptsThreshold, int $attemptsTtl, int $failureTtl) : void;
    public function reportSuccess(string $identifier) : void;
}

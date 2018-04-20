<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Manager;

use FrancescoMalatesta\LaravelCircuitBreaker\Events\AttemptFailed;
use FrancescoMalatesta\LaravelCircuitBreaker\Events\ServiceFailed;
use FrancescoMalatesta\LaravelCircuitBreaker\Events\ServiceRestored;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use FrancescoMalatesta\LaravelCircuitBreaker\Store\CircuitBreakerStoreInterface;
use Illuminate\Config\Repository as Config;

class CircuitBreakerManager
{
    /** @var  CircuitBreakerStoreInterface */
    private $store;

    /** @var EventDispatcher */
    private $dispatcher;

    /** @var Config */
    private $config;

    /**
     * CircuitBreakerManager constructor.
     *
     * @param CircuitBreakerStoreInterface $store
     * @param EventDispatcher $dispatcher
     * @param Config $config
     */
    public function __construct(CircuitBreakerStoreInterface $store, EventDispatcher $dispatcher, Config $config)
    {
        $this->store = $store;
        $this->dispatcher = $dispatcher;
        $this->config = $config;
    }

    public function isAvailable(string $identifier) : bool
    {
        return $this->store->isAvailable($identifier);
    }

    public function reportFailure(string $identifier) : void
    {
        $wasAvailable = $this->isAvailable($identifier);

        $this->store->reportFailure(
            $identifier,
            $this->config->get('circuit_breaker.defaults.attempts_threshold'),
            $this->config->get('circuit_breaker.defaults.attempts_ttl'),
            $this->config->get('circuit_breaker.defaults.failure_ttl')
        );

        $this->dispatcher->dispatch(new AttemptFailed($identifier));

        if($wasAvailable && !$this->isAvailable($identifier)) {
            $this->dispatcher->dispatch(new ServiceFailed($identifier));
        }
    }

    public function reportSuccess(string $identifier) : void
    {
        $this->store->reportSuccess($identifier);
        $this->dispatcher->dispatch(new ServiceRestored($identifier));
    }
}

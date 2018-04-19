<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Manager;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use FrancescoMalatesta\LaravelCircuitBreaker\Store\CircuitBreakerStoreInterface;

class CircuitBreakerManager
{
    /** @var  CircuitBreakerStoreInterface */
    private $store;

    /** @var EventDispatcher */
    private $dispatcher;

    /**
     * CircuitBreakerManager constructor.
     *
     * @param CircuitBreakerStoreInterface $store
     * @param EventDispatcher $dispatcher
     */
    public function __construct(CircuitBreakerStoreInterface $store, EventDispatcher $dispatcher)
    {
        $this->store = $store;
        $this->dispatcher = $dispatcher;
    }

    public function isAvailable(string $identifier) : bool
    {
        return $this->store->isAvailable($identifier);
    }

    public function reportFailure(string $identifier) : void
    {
        $wasAvailable = $this->isAvailable($identifier);

        $this->store->reportFailure($identifier);

        $isAvailable = $this->isAvailable($identifier);

        if($wasAvailable && !$isAvailable) {

        }
    }

    public function reportSuccess(string $identifier) : void
    {
        $this->store->reportSuccess($identifier);
    }
}

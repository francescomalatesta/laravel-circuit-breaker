<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Events;

class ServiceRestored
{
    /** @var string */
    private $identifier;

    /**
     * ServiceRestored constructor.
     * @param string $identifier
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}

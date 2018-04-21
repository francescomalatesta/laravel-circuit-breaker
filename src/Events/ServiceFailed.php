<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Events;

class ServiceFailed
{
    /** @var string */
    private $identifier;

    /**
     * ServiceFailed constructor.
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

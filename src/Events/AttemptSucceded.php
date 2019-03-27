<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Events;

class AttemptSucceded
{
    /** @var string */
    private $identifier;

    /**
     * AttemptFailed constructor.
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

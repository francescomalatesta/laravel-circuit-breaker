<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Events;

class AttemptSucceeded
{
    /** @var string */
    private $identifier;

    /**
     * AttemptSucceeded constructor.
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

<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Service;

final class ServiceOptions
{
    /** @var int */
    private $attemptsThreshold;

    /** @var int */
    private $attemptsTtl;

    /** @var int */
    private $failureTtl;

    /**
     * ServiceOptions constructor.
     *
     * @param $attemptsThreshold
     * @param $attemptsTtl
     * @param $failureTtl
     */
    private function __construct(int $attemptsThreshold, int $attemptsTtl, int $failureTtl)
    {
        $this->attemptsThreshold = $attemptsThreshold;
        $this->attemptsTtl = $attemptsTtl;
        $this->failureTtl = $failureTtl;
    }

    public static function createFromOptions($attemptsThreshold, $attemptsTtl, $failureTtl) : ServiceOptions
    {
        return new self($attemptsThreshold, $attemptsTtl, $failureTtl);
    }

    /**
     * @return int
     */
    public function getAttemptsThreshold(): int
    {
        return $this->attemptsThreshold;
    }

    /**
     * @return int
     */
    public function getAttemptsTtl(): int
    {
        return $this->attemptsTtl;
    }

    /**
     * @return int
     */
    public function getFailureTtl(): int
    {
        return $this->failureTtl;
    }
}

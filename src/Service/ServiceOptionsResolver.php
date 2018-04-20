<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Service;


use Illuminate\Config\Repository as Config;

class ServiceOptionsResolver
{
    /** @var Config */
    private $config;

    /**
     * ServiceOptionsResolver constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $identifier
     *
     * @return ServiceOptions
     */
    public function getOptionsFor(string $identifier)
    {
        $defaultOptions = $this->config->get('circuit_breaker.defaults');
        $serviceOptionsMap = $this->config->get('circuit_breaker.services');

        if(array_key_exists($identifier, $serviceOptionsMap)) {
            return ServiceOptions::createFromOptions(
                isset($serviceOptionsMap[$identifier]['attempts_threshold']) ? $serviceOptionsMap[$identifier]['attempts_threshold'] : $defaultOptions['attempts_threshold'],
                isset($serviceOptionsMap[$identifier]['attempts_ttl']) ? $serviceOptionsMap[$identifier]['attempts_ttl'] : $defaultOptions['attempts_ttl'],
                isset($serviceOptionsMap[$identifier]['failure_ttl']) ? $serviceOptionsMap[$identifier]['failure_ttl'] : $defaultOptions['failure_ttl']
            );
        }

        return ServiceOptions::createFromOptions(
            $defaultOptions['attempts_threshold'],
            $defaultOptions['attempts_ttl'],
            $defaultOptions['failure_ttl']
        );
    }
}

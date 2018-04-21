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

        if (array_key_exists($identifier, $serviceOptionsMap)) {
            $serviceOptions = array_merge($defaultOptions, $serviceOptionsMap[$identifier]);

            return ServiceOptions::createFromOptions(
                $serviceOptions['attempts_threshold'],
                $serviceOptions['attempts_ttl'],
                $serviceOptions['failure_ttl']
            );
        }

        return ServiceOptions::createFromOptions(
            $defaultOptions['attempts_threshold'],
            $defaultOptions['attempts_ttl'],
            $defaultOptions['failure_ttl']
        );
    }
}

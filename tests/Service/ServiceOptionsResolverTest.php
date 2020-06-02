<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Tests\Service;

use FrancescoMalatesta\LaravelCircuitBreaker\Service\ServiceOptionsResolver;
use Illuminate\Config\Repository as Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServiceOptionsResolverTest extends TestCase
{
    /** @var Config | MockObject */
    private $configMock;

    /** @var ServiceOptionsResolver */
    private $resolver;

    public function setUp():void
    {
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['circuit_breaker.defaults', null, [
                    'attempts_threshold' => 3,
                    'attempts_ttl' => 1000,
                    'failure_ttl' => 5000
                ]],
                ['circuit_breaker.services', null, [
                    'service' => [
                        'attempts_threshold' => 2,
                        'attempts_ttl' => 500,
                        'failure_ttl' => 1000
                    ],
                    'service2' =>  [
                        'attempts_threshold' => 2
                    ]
                ]]
            ]));

        $this->resolver = new ServiceOptionsResolver($this->configMock);
    }

    public function testItShouldReturnDefaultOptions()
    {
        $options = $this->resolver->getOptionsFor('another_service');

        $this->assertEquals(3, $options->getAttemptsThreshold());
        $this->assertEquals(1000, $options->getAttemptsTtl());
        $this->assertEquals(5000, $options->getFailureTtl());
    }

    public function testItShouldReturnSpecificServiceOptions()
    {
        $options = $this->resolver->getOptionsFor('service');

        $this->assertEquals(2, $options->getAttemptsThreshold());
        $this->assertEquals(500, $options->getAttemptsTtl());
        $this->assertEquals(1000, $options->getFailureTtl());
    }

    public function testItShouldMergeDefaultOptionsCorrectly()
    {
        $options = $this->resolver->getOptionsFor('service2');

        $this->assertEquals(2, $options->getAttemptsThreshold());
        $this->assertEquals(1000, $options->getAttemptsTtl());
        $this->assertEquals(5000, $options->getFailureTtl());
    }
}

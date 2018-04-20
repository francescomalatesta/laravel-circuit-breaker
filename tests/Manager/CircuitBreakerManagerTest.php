<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Tests\Manager;

use Illuminate\Config\Repository as Config;
use FrancescoMalatesta\LaravelCircuitBreaker\Events\AttemptFailed;
use FrancescoMalatesta\LaravelCircuitBreaker\Events\ServiceFailed;
use FrancescoMalatesta\LaravelCircuitBreaker\Events\ServiceRestored;
use FrancescoMalatesta\LaravelCircuitBreaker\Manager\CircuitBreakerManager;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use FrancescoMalatesta\LaravelCircuitBreaker\Store\CircuitBreakerStoreInterface;

class CircuitBreakerManagerTest extends TestCase
{
    /** @var CircuitBreakerStoreInterface | MockObject */
    private $storeMock;

    /** @var Dispatcher | MockObject */
    private $dispatcherMock;

    /** @var Config | MockObject */
    private $configMock;

    /** @var CircuitBreakerManager */
    private $manager;

    public function setUp()
    {
        parent::setUp();

        $this->storeMock = $this->getMockBuilder(CircuitBreakerStoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcherMock = $this->getMockBuilder(Dispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['circuit_breaker.defaults.attempts_threshold', null, 3],
                ['circuit_breaker.defaults.attempts_ttl', null, 1000],
                ['circuit_breaker.defaults.failure_ttl', null, 5000],
            ]));

        $this->manager = new CircuitBreakerManager($this->storeMock, $this->dispatcherMock, $this->configMock);
    }

    public function testItShouldGetServiceAvailability()
    {
        $this->storeMock->expects($this->at(0))
            ->method('isAvailable')
            ->with('service')
            ->willReturn(false);

        $this->storeMock->expects($this->at(1))
            ->method('isAvailable')
            ->with('service')
            ->willReturn(true);

        $this->assertFalse($this->manager->isAvailable('service'));
        $this->assertTrue($this->manager->isAvailable('service'));
    }

    public function testItShouldReportFailedAttempt()
    {
        $this->storeMock->expects($this->any())
            ->method('isAvailable')
            ->with('service')
            ->willReturn(false);

        $this->storeMock->expects($this->once())
            ->method('reportFailure')
            ->with('service', 3, 1000, 5000);

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event){
                return $event instanceof AttemptFailed;
            }));

        $this->manager->reportFailure('service');
    }

    public function testItShouldReportSuccessfulAttempt()
    {
        $this->storeMock->expects($this->once())
            ->method('reportSuccess')
            ->with('service');

        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($event){
                return $event instanceof ServiceRestored;
            }));

        $this->manager->reportSuccess('service');
    }

    public function testItShouldReportFailedService()
    {
        $this->storeMock->expects($this->any())
            ->method('isAvailable')
            ->with('service')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->storeMock->expects($this->once())
            ->method('reportFailure')
            ->with('service', 3, 1000, 5000);

        $this->dispatcherMock->expects($this->at(0))
            ->method('dispatch')
            ->with($this->callback(function($event){
                return $event instanceof AttemptFailed;
            }));

        $this->dispatcherMock->expects($this->at(1))
            ->method('dispatch')
            ->with($this->callback(function($event){
                return $event instanceof ServiceFailed;
            }));

        $this->manager->reportFailure('service');
    }
}

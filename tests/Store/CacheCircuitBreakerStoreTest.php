<?php

namespace FrancescoMalatesta\LaravelCircuitBreaker\Tests\Store;

use FrancescoMalatesta\LaravelCircuitBreaker\Store\CacheCircuitBreakerStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheCircuitBreakerStoreTest extends TestCase
{
    /** @var Repository | MockObject */
    private $cacheMock;

    /** @var CacheCircuitBreakerStore */
    private $store;

    public function setUp():void
    {
        parent::setUp();

        $this->cacheMock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->store = new CacheCircuitBreakerStore($this->cacheMock);
    }

    public function testItShouldReturnTrueIfServiceIsAvailable()
    {
        $this->cacheMock->expects($this->once())
            ->method('has')
            ->with('circuit_breaker_service_failed')
            ->willReturn(false);

        $this->assertTrue($this->store->isAvailable('service'));
    }

    public function testItShouldReturnFalseIfServiceIsNotAvailable()
    {
        $this->cacheMock->expects($this->once())
            ->method('has')
            ->with('circuit_breaker_service_failed')
            ->willReturn(true);

        $this->assertFalse($this->store->isAvailable('service'));
    }

    public function testItShouldStartAttemptsCountdown()
    {
        $this->cacheMock->expects($this->once())
            ->method('has')
            ->with('circuit_breaker_service_remaining_attempts')
            ->willReturn(false);

        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('circuit_breaker_service_remaining_attempts', 3, 500);

        $this->cacheMock->expects($this->never())
            ->method('decrement');

        $this->store->reportFailure('service', 3, 500, 5000);
    }

    public function testItShouldDecrementAttemptsCountdown()
    {
        $this->cacheMock->expects($this->once())
            ->method('has')
            ->with('circuit_breaker_service_remaining_attempts')
            ->willReturn(true);

        $this->cacheMock->expects($this->once())
            ->method('decrement')
            ->with('circuit_breaker_service_remaining_attempts')
            ->willReturn(2);

        $this->cacheMock->expects($this->never())
            ->method('set');

        $this->store->reportFailure('service', 3, 500, 5000);
    }

    public function testItShouldMarkServiceAsUnavailableAtZeroRemainingAttempts()
    {
        $this->cacheMock->expects($this->once())
            ->method('has')
            ->with('circuit_breaker_service_remaining_attempts')
            ->willReturn(true);

        $this->cacheMock->expects($this->once())
            ->method('decrement')
            ->with('circuit_breaker_service_remaining_attempts')
            ->willReturn(0);

        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('circuit_breaker_service_failed', 1, 5000);

        $this->store->reportFailure('service', 3, 500, 5000);
    }
}
